<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentInsightService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * A student's read-only view of their own profile, with the same three module
 * summaries the registry sees.
 */
class ProfileController extends Controller
{
    public function show(StudentInsightService $insights): View|RedirectResponse
    {
        $student = auth()->user()->student;

        if (! $student) {
            return redirect()->route('login')
                ->with('error', 'Your account is not linked to a student record. Please contact the registry.');
        }

        $student->load(['program.department', 'addresses.addressType', 'guardians']);

        return view('student.profile', [
            'student' => $student,
            'insight' => $insights->forStudent($student),
            'recentAttendance' => $student->attendanceRecords()
                ->with('session.classSection.course')
                ->latest('marked_at')->limit(10)->get(),
            'recentSubmissions' => $student->submissions()
                ->with('assignment.classSection.course')
                ->latest('submitted_at')->limit(10)->get(),
            'complaints' => $student->complaints()->latest()->get(),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return back()->with('success', 'Your password has been changed.');
    }
}
