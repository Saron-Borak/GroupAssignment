<?php

namespace App\Http\Controllers\Student;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Course;
use App\Services\AttendanceReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The classes a student is on, and the catalogue of ones they could join.
 *
 * Self-enrolment is carried over from the project submission mini project. The
 * registry can still enrol and drop anyone, and it keeps the final say: a
 * student may only leave a class they have no history in, so nobody can erase
 * a register or a submission by un-enrolling.
 */
class ClassSectionController extends Controller
{
    public function __construct(protected AttendanceReportService $reports) {}

    public function index(): View
    {
        $student = $this->student();

        return view('student.sections.index', [
            'student' => $student,
            'enrollments' => $student->enrollments()
                ->with(['classSection.course', 'classSection.lecturer', 'classSection.schedules'])
                ->get()
                ->sortBy('classSection.course.code')
                ->values(),
            'stats' => $this->reports->studentOverall($student)->keyBy('class_section_id'),
            'threshold' => (float) config('mis.attendance_min_percentage'),
        ]);
    }

    /**
     * The catalogue: everything on offer this term that the student is not
     * already enrolled in.
     */
    public function browse(Request $request): View
    {
        $student = $this->student();

        $taken = $student->enrollments()->active()->pluck('class_section_id');

        return view('student.sections.browse', [
            'student' => $student,
            'sections' => ClassSection::with(['course', 'lecturer', 'schedules'])
                ->withCount([
                    'enrollments as roster_count' => fn ($q) => $q->where('status', EnrollmentStatus::Enrolled),
                ])
                ->whereNotIn('id', $taken)
                ->when($request->integer('course_id'), fn ($q, $id) => $q->where('course_id', $id))
                ->when($request->string('q')->toString(), fn ($q, $term) => $q->whereHas(
                    'course',
                    fn ($c) => $c->where('code', 'like', "%{$term}%")->orWhere('title', 'like', "%{$term}%"),
                ))
                ->orderBy('term')
                ->paginate(12)
                ->withQueryString(),
            'courses' => Course::orderBy('code')->get(),
        ]);
    }

    public function enroll(ClassSection $section): RedirectResponse
    {
        $student = $this->student();

        $existing = $section->enrollments()->where('student_id', $student->id)->first();

        if ($existing?->status === EnrollmentStatus::Enrolled) {
            return back()->with('error', 'You are already enrolled in that class.');
        }

        if ($existing) {
            $existing->update(['status' => EnrollmentStatus::Enrolled]);
        } else {
            $section->enrollments()->create([
                'student_id' => $student->id,
                'status' => EnrollmentStatus::Enrolled,
                'enrolled_at' => now()->toDateString(),
            ]);
        }

        return redirect()->route('student.sections.index')
            ->with('success', "You are enrolled in {$section->label()}.");
    }

    /**
     * Leaving a class is only possible while it holds nothing about you. Once a
     * register has been marked or work submitted, the registry has to do it, so
     * that the record is dropped rather than deleted.
     */
    public function unenroll(ClassSection $section): RedirectResponse
    {
        $student = $this->student();

        $enrollment = $section->enrollments()
            ->where('student_id', $student->id)
            ->firstOrFail();

        $hasAttendance = $section->sessions()
            ->whereHas('records', fn ($q) => $q->where('student_id', $student->id))
            ->exists();

        $hasSubmissions = $section->assignments()
            ->whereHas('submissions', fn ($q) => $q->where('student_id', $student->id))
            ->exists();

        if ($hasAttendance || $hasSubmissions) {
            return back()->with(
                'error',
                'This class already holds attendance or submitted work for you. Ask the registry to withdraw you, so that record is kept.',
            );
        }

        $enrollment->delete();

        return redirect()->route('student.sections.index')
            ->with('success', "You have left {$section->label()}.");
    }

    protected function student()
    {
        $student = auth()->user()->student;

        abort_unless($student, 403, 'Your account is not linked to a student record.');

        return $student;
    }
}
