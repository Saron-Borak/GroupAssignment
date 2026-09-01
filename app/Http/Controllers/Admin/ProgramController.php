<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProgramLevel;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        return view('admin.programs.index', [
            'programs' => Program::with('department')->withCount('students')->orderBy('code')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.programs.create', [
            'program' => new Program(['duration_years' => 4]),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Program::create($this->validated($request));

        return redirect()->route('admin.programs.index')->with('success', 'Program created.');
    }

    public function edit(Program $program): View
    {
        return view('admin.programs.edit', [
            'program' => $program,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $program->update($this->validated($request, $program));

        return redirect()->route('admin.programs.index')->with('success', 'Program updated.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        if ($program->students()->exists()) {
            return back()->with('error', 'This program still has students enrolled.');
        }

        $program->delete();

        return redirect()->route('admin.programs.index')->with('success', 'Program deleted.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?Program $program = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        return $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('programs', 'code')->ignore($program?->id)],
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', new Enum(ProgramLevel::class)],
            'duration_years' => ['required', 'integer', 'min:1', 'max:8'],
        ]);
    }
}
