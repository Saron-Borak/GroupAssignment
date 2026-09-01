<?php

namespace App\Http\Controllers\Student;

use App\Enums\ComplaintCategory;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

/**
 * The complaint module, student side.
 */
class ComplaintController extends Controller
{
    public function __construct(protected ComplaintService $complaints) {}

    public function index(): View
    {
        $student = $this->student();

        return view('student.complaints.index', [
            'complaints' => $student->complaints()->latest()->paginate(10),
            'categories' => ComplaintCategory::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', new Enum(ComplaintCategory::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
        ], [
            'description.min' => 'Please describe the issue in at least 20 characters so it can be investigated.',
        ]);

        $complaint = $this->complaints->file($this->student(), $validated);

        return redirect()
            ->route('student.complaints.index')
            ->with('success', "Complaint {$complaint->reference} has been submitted.");
    }

    public function show(Complaint $complaint): View
    {
        // A student may read only their own case.
        abort_unless($complaint->student_id === $this->student()->id, 403);

        $complaint->load('handler');

        return view('student.complaints.show', compact('complaint'));
    }

    protected function student()
    {
        $student = auth()->user()->student;

        abort_unless($student, 403, 'Your account is not linked to a student record.');

        return $student;
    }
}
