<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        return view('admin.departments.index', [
            'departments' => Department::withCount(['programs', 'courses'])->orderBy('code')->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Department::create($this->validated($request));

        return back()->with('success', 'Department created.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validated($request, $department));

        return back()->with('success', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->programs()->exists() || $department->courses()->exists()) {
            return back()->with('error', 'This department still has programs or courses attached.');
        }

        $department->delete();

        return back()->with('success', 'Department deleted.');
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?Department $department = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        return $request->validate([
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('departments', 'code')->ignore($department?->id)],
            'name' => ['required', 'string', 'max:255'],
        ]);
    }
}
