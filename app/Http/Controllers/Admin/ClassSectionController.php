<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Class sections: one delivery of a course in a term, taught by one lecturer.
 *
 * This is the join between the modules. Attendance is taken against a section's
 * roster and assignments are issued to the same roster, so the enrolment list
 * managed here is the single list both modules read.
 */
class ClassSectionController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.sections.index', [
            'sections' => ClassSection::with(['course', 'lecturer'])
                ->withCount([
                    'enrollments as roster_count' => fn ($q) => $q->where('status', EnrollmentStatus::Enrolled),
                    'sessions',
                    'assignments',
                ])
                ->when($request->integer('course_id'), fn ($q, $id) => $q->where('course_id', $id))
                ->when($request->string('term')->toString(), fn ($q, $t) => $q->where('term', $t))
                // Ordering by a related column uses a subquery, not a join: a join
                // would force select('class_sections.*') and silently discard the
                // withCount subqueries above.
                ->orderBy(Course::select('code')->whereColumn('courses.id', 'class_sections.course_id'))
                ->orderBy('section_code')
                ->paginate(15)
                ->withQueryString(),
            'courses' => Course::orderBy('code')->get(),
            'terms' => ClassSection::query()->distinct()->orderBy('term')->pluck('term'),
        ]);
    }

    public function create(): View
    {
        return view('admin.sections.create', $this->formData() + ['section' => new ClassSection]);
    }

    public function store(Request $request): RedirectResponse
    {
        $section = ClassSection::create($this->validated($request));

        return redirect()->route('admin.sections.show', $section)
            ->with('success', 'Class section created. Add its timetable and roster below.');
    }

    /**
     * The section in full: timetable, roster, and what each module has recorded.
     */
    public function show(ClassSection $section): View
    {
        $section->load(['course.department', 'lecturer', 'schedules']);

        $enrolled = $section->enrollments()->pluck('student_id');

        return view('admin.sections.show', [
            'section' => $section,
            'roster' => $section->enrollments()
                ->with('student.program')
                ->get()
                ->sortBy('student.last_name')
                ->values(),
            // Only students not already on this roster can be added.
            'candidates' => Student::with('program')
                ->whereNotIn('id', $enrolled)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'sessionCount' => $section->sessions()->count(),
            'assignmentCount' => $section->assignments()->count(),
            'days' => ClassSchedule::DAYS,
        ]);
    }

    public function edit(ClassSection $section): View
    {
        return view('admin.sections.edit', $this->formData() + ['section' => $section]);
    }

    public function update(Request $request, ClassSection $section): RedirectResponse
    {
        $section->update($this->validated($request, $section->id));

        return redirect()->route('admin.sections.show', $section)->with('success', 'Class section updated.');
    }

    public function destroy(ClassSection $section): RedirectResponse
    {
        if ($section->sessions()->exists() || $section->assignments()->exists()) {
            return back()->with('error', 'This section has attendance or assignment history and cannot be deleted.');
        }

        $section->enrollments()->delete();
        $section->schedules()->delete();
        $section->delete();

        return redirect()->route('admin.sections.index')->with('success', 'Class section removed.');
    }

    // ------------------------------------------------------------- timetable

    public function storeSchedule(Request $request, ClassSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:50'],
        ]);

        // Normalise before both the check and the write: the form sends H:i, the
        // column holds H:i:s, and comparing the two forms would miss a duplicate
        // and let the unique constraint fail with a 500 instead of a message.
        $validated['start_time'] .= ':00';
        $validated['end_time'] .= ':00';

        $exists = $section->schedules()
            ->where('day_of_week', $validated['day_of_week'])
            ->where('start_time', $validated['start_time'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'This class already meets at that time on that day.');
        }

        $section->schedules()->create($validated);

        return back()->with('success', 'Timetable slot added. Sessions can now be generated from it.');
    }

    public function destroySchedule(ClassSection $section, ClassSchedule $schedule): RedirectResponse
    {
        abort_unless($schedule->class_section_id === $section->id, 404);

        $schedule->delete();

        return back()->with('success', 'Timetable slot removed.');
    }

    // -------------------------------------------------------------- roster

    public function enroll(Request $request, ClassSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        $existing = $section->enrollments()->where('student_id', $validated['student_id'])->first();

        if ($existing) {
            // Re-enrolling somebody who dropped reactivates the original row, so
            // their attendance history for this class is not split in two.
            $existing->update(['status' => EnrollmentStatus::Enrolled]);

            return back()->with('success', 'Student re-enrolled; their existing history is preserved.');
        }

        $section->enrollments()->create([
            'student_id' => $validated['student_id'],
            'status' => EnrollmentStatus::Enrolled,
            'enrolled_at' => now()->toDateString(),
        ]);

        return back()->with('success', 'Student enrolled.');
    }

    /**
     * Drop rather than delete once anything has been recorded, so the register
     * and the submissions the student already made survive.
     */
    public function unenroll(ClassSection $section, Enrollment $enrollment): RedirectResponse
    {
        abort_unless($enrollment->class_section_id === $section->id, 404);

        $hasHistory = $section->sessions()
            ->whereHas('records', fn ($q) => $q->where('student_id', $enrollment->student_id))
            ->exists();

        if ($hasHistory) {
            $enrollment->update(['status' => EnrollmentStatus::Dropped]);

            return back()->with('success', 'Marked as dropped. Their attendance history for this class is retained.');
        }

        $enrollment->delete();

        return back()->with('success', 'Enrolment removed.');
    }

    // --------------------------------------------------------------- shared

    protected function formData(): array
    {
        return [
            'courses' => Course::orderBy('code')->get(),
            'lecturers' => User::where('role', UserRole::Faculty)->orderBy('name')->get(),
        ];
    }

    protected function validated(Request $request, ?int $ignore = null): array
    {
        return $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'lecturer_id' => ['required', 'exists:users,id'],
            'term' => ['required', 'string', 'max:30'],
            'section_code' => [
                'required', 'string', 'max:10',
                Rule::unique('class_sections')
                    ->where(fn ($q) => $q
                        ->where('course_id', $request->integer('course_id'))
                        ->where('term', $request->string('term')->toString()))
                    ->ignore($ignore),
            ],
            'room' => ['nullable', 'string', 'max:50'],
        ], [
            'section_code.unique' => 'That section code is already used for this course in this term.',
        ]);
    }
}
