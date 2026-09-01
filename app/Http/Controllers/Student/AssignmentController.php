<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The project submission module, student side: see what has been issued to the
 * classes you are enrolled in, and upload your work.
 */
class AssignmentController extends Controller
{
    public function __construct(protected SubmissionService $submissions) {}

    public function index(): View
    {
        $student = $this->student();

        return view('student.assignments.index', [
            'student' => $student,
            'assignments' => $this->submissions->assignmentsFor($student),
        ]);
    }

    /**
     * One assignment on its own, with this student's submission history.
     *
     * The list view is a quick overview; this is where a student reads the
     * full instructions and confirms exactly what the server recorded.
     */
    public function show(Assignment $assignment): View
    {
        $student = $this->student();

        $this->authorizeEnrolment($student, $assignment);

        $assignment->load('classSection.course', 'classSection.lecturer');

        return view('student.assignments.show', [
            'student' => $student,
            'assignment' => $assignment,
            'submission' => $assignment->submissions()->where('student_id', $student->id)->first(),
        ]);
    }

    public function store(Request $request, Assignment $assignment): RedirectResponse
    {
        $student = $this->student();

        $this->authorizeEnrolment($student, $assignment);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,zip,txt', 'max:10240'],
        ], [], ['file' => 'submission file']);

        $submission = $this->submissions->submit($assignment, $student, $request->file('file'));

        return back()->with(
            'success',
            "Submitted for {$assignment->title}, recorded as {$submission->status->label()}.",
        );
    }

    /**
     * Enrolment is what grants access; nothing in the request decides it.
     */
    protected function authorizeEnrolment($student, Assignment $assignment): void
    {
        abort_unless(
            $student->enrollments()->active()->where('class_section_id', $assignment->class_section_id)->exists(),
            403,
            'You are not enrolled in the class this assignment belongs to.',
        );
    }

    /**
     * A student may download only their own submission.
     */
    public function download(Assignment $assignment): StreamedResponse
    {
        $student = $this->student();

        $submission = $assignment->submissions()
            ->where('student_id', $student->id)
            ->firstOrFail();

        abort_unless(Storage::disk('local')->exists($submission->file_path), 404, 'The file is no longer available.');

        return Storage::disk('local')->download(
            $submission->file_path,
            $submission->original_filename ?: 'submission',
        );
    }

    protected function student()
    {
        $student = auth()->user()->student;

        abort_unless($student, 403, 'Your account is not linked to a student record.');

        return $student;
    }
}
