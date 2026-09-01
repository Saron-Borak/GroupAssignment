<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ClassSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The project submission module, lecturer side: issue an assignment to a
 * section and review what the enrolled students have submitted.
 */
class AssignmentController extends Controller
{
    public function index(): View
    {
        $sections = ClassSection::where('lecturer_id', auth()->id())->pluck('id');

        return view('lecturer.assignments.index', [
            'assignments' => Assignment::with('classSection.course')
                ->withCount([
                    'submissions',
                    'submissions as late_count' => fn ($q) => $q->where('status', 'late'),
                ])
                ->whereIn('class_section_id', $sections)
                ->orderByDesc('deadline')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('lecturer.assignments.create', [
            'sections' => ClassSection::with('course')->where('lecturer_id', auth()->id())->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_section_id' => ['required', 'exists:class_sections,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'deadline' => ['required', 'date'],
        ]);

        $this->authorizeSection(ClassSection::findOrFail($validated['class_section_id']));

        $assignment = Assignment::create($validated);

        return redirect()
            ->route('faculty.assignments.show', $assignment)
            ->with('success', 'Assignment issued to the class.');
    }

    /**
     * Who has submitted, who has not. The roster comes from the shared
     * enrolment table, so it is the same list attendance is taken against.
     */
    public function show(Assignment $assignment): View
    {
        $this->authorizeSection($assignment->classSection);

        $assignment->load('classSection.course');

        $roster = $assignment->classSection
            ->enrollments()
            ->active()
            ->with('student')
            ->get()
            ->sortBy('student.last_name')
            ->values();

        return view('lecturer.assignments.show', [
            'assignment' => $assignment,
            'roster' => $roster,
            'submissions' => $assignment->submissions()->get()->keyBy('student_id'),
        ]);
    }

    public function edit(Assignment $assignment): View
    {
        $this->authorizeSection($assignment->classSection);

        return view('lecturer.assignments.edit', [
            'assignment' => $assignment->load('classSection.course'),
        ]);
    }

    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorizeSection($assignment->classSection);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'deadline' => ['required', 'date'],
        ]);

        $assignment->update($validated);

        return redirect()->route('faculty.assignments.show', $assignment)
            ->with('success', 'Assignment updated. Any submission already made keeps the status it was given.');
    }

    /**
     * Withdraw an assignment.
     *
     * Refused once work has been submitted: deleting the row would cascade to
     * the submissions and leave the uploaded files orphaned on disk, which is
     * not something a lecturer should be able to do by accident.
     */
    public function destroy(Assignment $assignment): RedirectResponse
    {
        $this->authorizeSection($assignment->classSection);

        if ($assignment->submissions()->exists()) {
            return back()->with('error', 'Work has already been submitted against this assignment, so it cannot be deleted.');
        }

        $assignment->delete();

        return redirect()->route('faculty.assignments.index')->with('success', 'Assignment withdrawn.');
    }

    /**
     * Serve a submitted file. Stored on the private disk, so it can only be
     * reached through this authorised route.
     */
    public function download(Assignment $assignment, int $studentId): StreamedResponse
    {
        $this->authorizeSection($assignment->classSection);

        $submission = $assignment->submissions()->where('student_id', $studentId)->firstOrFail();

        abort_unless(Storage::disk('local')->exists($submission->file_path), 404, 'The submitted file is missing.');

        return Storage::disk('local')->download(
            $submission->file_path,
            $submission->original_filename ?: 'submission',
        );
    }

    protected function authorizeSection(ClassSection $section): void
    {
        abort_unless($section->lecturer_id === auth()->id(), 403, 'This is not one of your classes.');
    }
}
