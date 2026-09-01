<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The taught-unit catalogue.
 *
 * This table is the merge of two: the attendance mini project called a taught
 * unit a course, the submission mini project called it a subject, and they were
 * the same thing under two names. One catalogue now serves both modules.
 */
class CourseController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.courses.index', [
            'courses' => Course::with('department')
                ->withCount('classSections')
                ->when($request->string('q')->toString(), fn ($q, $term) => $q->where(
                    fn ($inner) => $inner
                        ->where('code', 'like', "%{$term}%")
                        ->orWhere('title', 'like', "%{$term}%")
                ))
                ->when($request->integer('department_id'), fn ($q, $id) => $q->where('department_id', $id))
                ->orderBy('code')
                ->paginate(15)
                ->withQueryString(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.courses.create', [
            'departments' => Department::orderBy('name')->get(),
            'course' => new Course,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Course::create($this->validated($request));

        return redirect()->route('admin.courses.index')->with('success', 'Course added to the catalogue.');
    }

    public function edit(Course $course): View
    {
        return view('admin.courses.edit', [
            'departments' => Department::orderBy('name')->get(),
            'course' => $course,
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $course->update($this->validated($request, $course->id));

        return redirect()->route('admin.courses.index')->with('success', 'Course updated.');
    }

    /**
     * Refused while class sections still hang off it: deleting would cascade to
     * every session, register and assignment recorded against those sections.
     */
    public function destroy(Course $course): RedirectResponse
    {
        if ($course->classSections()->exists()) {
            return back()->with('error', 'This course still has class sections. Remove them first.');
        }

        $course->delete();

        return redirect()->route('admin.courses.index')->with('success', 'Course removed.');
    }

    protected function validated(Request $request, ?int $ignore = null): array
    {
        return $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'code' => [
                'required', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/',
                Rule::unique('courses', 'code')->ignore($ignore),
            ],
            'title' => ['required', 'string', 'max:255'],
            'credit_hours' => ['required', 'integer', 'min:1', 'max:12'],
        ], [
            'code.regex' => 'The course code may contain capital letters, digits and hyphens only.',
        ]);
    }
}
