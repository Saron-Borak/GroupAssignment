<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserAccountController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->string('q')->toString(), fn ($q, $term) => $q->where(
                fn ($inner) => $inner->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")
            ))
            ->when(
                $request->filled('role'),
                fn ($q) => $q->where('role', UserRole::from($request->string('role')->toString()))
            )
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Issue a sign-in account for a student who does not yet have one.
     */
    public function issue(Request $request, Student $student, StudentProfileService $profiles): RedirectResponse
    {
        if ($student->user_id) {
            return back()->with('warning', 'This student already has a sign-in account.');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $profiles->issueAccount($student, $validated['password']);

        return back()->with('success', "Sign-in account issued for {$student->fullName()}.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update(['password' => $validated['password']]);

        return back()->with('success', "Password reset for {$user->name}.");
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active
            ? "{$user->name} can sign in again."
            : "{$user->name} has been deactivated.");
    }
}
