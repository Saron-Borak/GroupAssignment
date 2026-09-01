<?php

namespace App\Http\Controllers\Lecturer;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\ClassSection;
use App\Services\AttendanceReportService;
use App\Services\AttendanceService;
use App\Support\CsvExporter;
use App\Support\QrRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The attendance module: a lecturer records a register against the shared
 * student profiles enrolled in their own sections.
 *
 * A register can be filled in three ways - by hand, by students scanning the
 * rotating QR code on the projector, or by students typing the short code.
 * All three go through AttendanceService, so they cannot disagree.
 */
class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendance,
        protected AttendanceReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $sections = ClassSection::with('course')
            ->where('lecturer_id', auth()->id())
            ->get();

        $sessions = AttendanceSession::with('classSection.course')
            ->withCount([
                'records',
                'records as present_count' => fn ($q) => $q->whereIn('status', ['present', 'late']),
            ])
            ->whereIn('class_section_id', $sections->pluck('id'))
            ->when($request->integer('section_id'), fn ($q, $id) => $q->where('class_section_id', $id))
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('session_date')
            ->orderByDesc('start_time')
            ->paginate(15)
            ->withQueryString();

        return view('lecturer.attendance.index', [
            'sections' => $sections,
            'sessions' => $sessions,
            'statuses' => SessionStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('lecturer.attendance.create', [
            'sections' => ClassSection::with('course')->where('lecturer_id', auth()->id())->get(),
            'selected' => $request->integer('section_id'),
            'statuses' => SessionStatus::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSession($request);

        $section = ClassSection::findOrFail($validated['class_section_id']);
        $this->authorizeSection($section);

        if ($this->attendance->clashes($section, $validated['session_date'], $validated['start_time'])) {
            return back()->withInput()
                ->with('error', 'This class already has a session at that date and time.');
        }

        $session = $this->attendance->createSession($section, $validated);

        return redirect()
            ->route('faculty.attendance.mark', $session)
            ->with('success', 'Session created. Mark the register below, or open it for check-in.');
    }

    public function edit(AttendanceSession $session): View
    {
        $this->authorizeSection($session->classSection);

        return view('lecturer.attendance.edit', [
            'session' => $session->load('classSection.course'),
        ]);
    }

    public function update(Request $request, AttendanceSession $session): RedirectResponse
    {
        $this->authorizeSection($session->classSection);

        $validated = $request->validate([
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'topic' => ['nullable', 'string', 'max:255'],
            'late_after_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
        ]);

        if ($this->attendance->clashes(
            $session->classSection, $validated['session_date'], $validated['start_time'], $session->id
        )) {
            return back()->withInput()
                ->with('error', 'This class already has another session at that date and time.');
        }

        $this->attendance->updateSession($session, $validated);

        return redirect()->route('faculty.attendance.index')->with('success', 'Session updated.');
    }

    /**
     * Deleting a session takes its records with it, so it is refused once the
     * register has been marked - a lecturer correcting a typo must not silently
     * destroy a term of attendance history.
     */
    public function destroy(AttendanceSession $session): RedirectResponse
    {
        $this->authorizeSection($session->classSection);

        if ($session->records()->exists()) {
            return back()->with('error', 'This session has marks against it and cannot be deleted.');
        }

        $session->delete();

        return redirect()->route('faculty.attendance.index')->with('success', 'Session deleted.');
    }

    /**
     * The register: every enrolled student with their current mark selected.
     */
    public function mark(AttendanceSession $session): View
    {
        $this->authorizeSection($session->classSection);

        $session->load('classSection.course');

        return view('lecturer.attendance.mark', [
            'session' => $session,
            'roster' => $session->classSection
                ->enrollments()
                ->active()
                ->with('student.program')
                ->get()
                ->sortBy('student.last_name')
                ->values(),
            'existing' => $session->records()->get()->keyBy('student_id'),
            'statuses' => AttendanceStatus::cases(),
        ]);
    }

    public function storeMarks(Request $request, AttendanceSession $session): RedirectResponse
    {
        $this->authorizeSection($session->classSection);

        $validated = $request->validate([
            'marks' => ['required', 'array'],
            'marks.*' => ['required', Rule::in(array_column(AttendanceStatus::cases(), 'value'))],
        ]);

        $saved = $this->attendance->saveMarks($session, $validated['marks'], auth()->user());

        return redirect()
            ->route('faculty.attendance.index')
            ->with('success', "Register saved for {$saved} student(s).");
    }

    // ------------------------------------------------------------- lifecycle

    public function open(AttendanceSession $session): RedirectResponse
    {
        $this->authorizeSection($session->classSection);

        $this->attendance->openSession($session);

        return redirect()->route('faculty.attendance.qr', $session);
    }

    public function close(AttendanceSession $session): RedirectResponse
    {
        $this->authorizeSection($session->classSection);

        $absent = $this->attendance->closeSession($session, auth()->user());

        return redirect()
            ->route('faculty.attendance.mark', $session)
            ->with('success', "Session closed. {$absent} student(s) who never checked in were marked absent.");
    }

    // ----------------------------------------------------------------- kiosk

    /**
     * The projector view: a large QR code, the short code, a countdown and a
     * live tally. Deliberately has no navigation - it is meant to be shown to
     * a room, not browsed.
     */
    public function qr(AttendanceSession $session, QrRenderer $qr): View
    {
        $this->authorizeSection($session->classSection);

        abort_unless($session->status->acceptsCheckIn(), 404, 'That session is not open.');

        $session->load('classSection.course');

        return view('lecturer.attendance.qr', [
            'session' => $session,
            'svg' => $qr->svg(route('checkin.scan', $session->qr_token)),
            'refreshSeconds' => (int) config('mis.qr_refresh_seconds'),
        ] + $this->kioskState($session));
    }

    /**
     * Polled by the kiosk. Rotates the token when the current one is close to
     * expiring and returns the counters, so the page never reloads.
     */
    public function qrRefresh(AttendanceSession $session, QrRenderer $qr): JsonResponse
    {
        $this->authorizeSection($session->classSection);

        if (! $session->status->acceptsCheckIn()) {
            return response()->json(['open' => false]);
        }

        // Rotate whenever the current token would die before the next poll.
        // Comparing against a small fixed margin instead leaves a window in
        // which the code on the projector has expired but has not been
        // replaced - the QR scans, and the check-in is refused.
        if ($session->qrSecondsLeft() <= (int) config('mis.qr_refresh_seconds')) {
            $session = $this->attendance->rotateQr($session);
        }

        return response()->json([
            'open' => true,
            'svg' => $qr->svg(route('checkin.scan', $session->qr_token)),
            'code' => $session->checkin_code,
            'expires_in' => $session->qrSecondsLeft(),
        ] + $this->kioskState($session));
    }

    /**
     * @return array{present: int, late: int, total: int, recent: list<array{name: string, status: string, at: ?string}>}
     */
    protected function kioskState(AttendanceSession $session): array
    {
        $records = $session->records()->with('student')->orderByDesc('marked_at')->get();

        return [
            'present' => $records->where('status', AttendanceStatus::Present)->count(),
            'late' => $records->where('status', AttendanceStatus::Late)->count(),
            'total' => $session->classSection->enrollments()->active()->count(),
            'recent' => $records->take(8)->map(fn ($r) => [
                'name' => $r->student->fullName(),
                'status' => $r->status->label(),
                'at' => $r->marked_at?->format('H:i:s'),
            ])->values()->all(),
        ];
    }

    // --------------------------------------------------------------- reports

    /**
     * Per-student attendance across one of the lecturer's own sections.
     */
    public function report(ClassSection $section, Request $request): View
    {
        $this->authorizeSection($section);

        $section->load('course');

        return view('lecturer.attendance.report', [
            'section' => $section,
            'from' => $request->date('from')?->toDateString(),
            'to' => $request->date('to')?->toDateString(),
            'rows' => $this->reports->classSectionStats(
                $section,
                $request->date('from')?->toDateString(),
                $request->date('to')?->toDateString(),
            ),
            'threshold' => config('mis.attendance_min_percentage'),
        ]);
    }

    public function exportReport(ClassSection $section, Request $request, CsvExporter $csv): StreamedResponse
    {
        $this->authorizeSection($section);

        $section->load('course');

        $rows = $this->reports->classSectionStats(
            $section,
            $request->date('from')?->toDateString(),
            $request->date('to')?->toDateString(),
        );

        return $csv->download(
            $csv->filename('attendance', $section->label()),
            ['Student No', 'Name', 'Email', 'Held', 'Present', 'Late', 'Absent', 'Excused', 'Attendance %', 'At Risk'],
            $rows->map(fn ($r) => [
                $r->student_id_no, $r->name, $r->email, $r->held,
                $r->present, $r->late, $r->absent, $r->excused,
                number_format($r->percentage, 1), $r->at_risk ? 'Yes' : 'No',
            ]),
        );
    }

    // ------------------------------------------------------------- timetable

    /**
     * Create a term of meetings from the section's weekly timetable, instead of
     * typing in one session per week by hand.
     */
    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_section_id' => ['required', 'exists:class_sections,id'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $section = ClassSection::findOrFail($validated['class_section_id']);
        $this->authorizeSection($section);

        if ($section->schedules()->doesntExist()) {
            return back()->with('error', 'This class has no weekly timetable yet. The registry adds it on the class section.');
        }

        $made = $this->attendance->generateSessions($section, $validated['from'], $validated['to']);

        return redirect()->route('faculty.attendance.index', ['section_id' => $section->id])
            ->with('success', $made > 0
                ? "{$made} session(s) generated from the timetable."
                : 'No new sessions: every meeting in that range already exists.');
    }

    protected function validateSession(Request $request): array
    {
        return $request->validate([
            'class_section_id' => ['required', 'exists:class_sections,id'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'topic' => ['nullable', 'string', 'max:255'],
            'late_after_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'status' => ['nullable', Rule::enum(SessionStatus::class)],
        ]);
    }

    /**
     * A lecturer may only touch sections they teach. Route middleware proves
     * the role; this proves which lecturer.
     */
    protected function authorizeSection(ClassSection $section): void
    {
        abort_unless($section->lecturer_id === auth()->id(), 403, 'This is not one of your classes.');
    }
}
