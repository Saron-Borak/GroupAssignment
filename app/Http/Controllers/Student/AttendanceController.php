<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Services\AttendanceReportService;
use Illuminate\View\View;

/**
 * A student's own attendance: the percentage per class, and the session-by-session
 * history behind it.
 *
 * Nothing here accepts an identifier for another student - the profile is read
 * from the signed-in account, so there is no id to tamper with.
 */
class AttendanceController extends Controller
{
    public function __construct(protected AttendanceReportService $reports) {}

    public function index(): View
    {
        $student = $this->student();

        return view('student.attendance.index', [
            'student' => $student,
            'rows' => $this->reports->studentOverall($student),
            'threshold' => (float) config('mis.attendance_min_percentage'),
        ]);
    }

    /**
     * The history behind one of those percentages.
     */
    public function show(ClassSection $section): View
    {
        $student = $this->student();

        // Enrolment is the authorisation: a student may only open the history of
        // a class they are actually on the roster of.
        abort_unless(
            $section->enrollments()->active()->where('student_id', $student->id)->exists(),
            403,
            'You are not enrolled in that class.',
        );

        $section->load('course', 'lecturer');

        return view('student.attendance.show', [
            'student' => $student,
            'section' => $section,
            'history' => $this->reports->studentClassHistory($student, $section),
            'summary' => $this->reports->studentOverall($student)
                ->firstWhere('class_section_id', $section->id),
            'threshold' => (float) config('mis.attendance_min_percentage'),
        ]);
    }

    protected function student()
    {
        $student = auth()->user()->student;

        abort_unless($student, 403, 'This account is not linked to a student profile.');

        return $student->load('program');
    }
}
