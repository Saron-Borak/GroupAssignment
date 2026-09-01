<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Student;
use App\Services\StudentInsightService;
use Illuminate\View\View;

/**
 * Faculty see the profiles of students enrolled in the sections they teach,
 * and nothing else.
 */
class DirectoryController extends Controller
{
    public function dashboard(StudentInsightService $insights): View
    {
        $sections = ClassSection::with('course')
            ->withCount(['enrollments as students_count' => fn ($q) => $q->active()])
            ->where('lecturer_id', auth()->id())
            ->get();

        $students = Student::with('program')
            ->whereHas('enrollments', fn ($q) => $q
                ->active()
                ->whereIn('class_section_id', $sections->pluck('id')))
            ->orderBy('last_name')
            ->get();

        return view('faculty.dashboard', [
            'sections' => $sections,
            'students' => $students,
            'insights' => $insights->forCohort($students),
        ]);
    }

    public function show(Student $student, StudentInsightService $insights): View
    {
        // Middleware proves the user is faculty; the policy proves this student
        // is actually in one of their sections.
        $this->authorize('view', $student);

        $student->load(['program.department', 'addresses.addressType', 'guardians']);

        return view('faculty.student', [
            'student' => $student,
            'insight' => $insights->forStudent($student),
        ]);
    }
}
