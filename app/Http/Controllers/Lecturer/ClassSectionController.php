<?php

namespace App\Http\Controllers\Lecturer;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Services\AttendanceReportService;
use Illuminate\View\View;

/**
 * A lecturer's own classes, and everything the three modules hold about one of
 * them: the timetable, the roster, the register history and the assignments.
 *
 * This is the screen that only exists because the systems were merged. Before,
 * a lecturer needed the attendance application to see the register and the
 * submission application to see the coursework, and neither could tell them
 * which students were on both lists.
 */
class ClassSectionController extends Controller
{
    public function __construct(protected AttendanceReportService $reports) {}

    public function index(): View
    {
        return view('lecturer.sections.index', [
            'sections' => ClassSection::with(['course', 'schedules'])
                ->withCount([
                    'enrollments as roster_count' => fn ($q) => $q->where('status', EnrollmentStatus::Enrolled),
                    'sessions',
                    'assignments',
                ])
                ->where('lecturer_id', auth()->id())
                ->orderBy('term')
                ->get(),
        ]);
    }

    public function show(ClassSection $section): View
    {
        $this->authorizeSection($section);

        $section->load(['course.department', 'schedules']);

        return view('lecturer.sections.show', [
            'section' => $section,
            'roster' => $section->enrollments()
                ->active()
                ->with('student.program')
                ->get()
                ->sortBy('student.last_name')
                ->values(),
            'sessions' => $section->sessions()
                ->withCount([
                    'records',
                    'records as attended_count' => fn ($q) => $q->whereIn('status', ['present', 'late']),
                ])
                ->orderByDesc('session_date')
                ->orderByDesc('start_time')
                ->limit(10)
                ->get(),
            'assignments' => $section->assignments()
                ->withCount([
                    'submissions',
                    'submissions as late_count' => fn ($q) => $q->where('status', 'late'),
                ])
                ->orderByDesc('deadline')
                ->get(),
            // One grouped query for the whole roster, not one per student.
            'stats' => $this->reports->classSectionStats($section)->keyBy('student_id'),
            'threshold' => (float) config('mis.attendance_min_percentage'),
        ]);
    }

    protected function authorizeSection(ClassSection $section): void
    {
        abort_unless($section->lecturer_id === auth()->id(), 403, 'This is not one of your classes.');
    }
}
