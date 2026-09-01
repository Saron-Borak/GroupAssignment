<?php

namespace App\Http\Controllers\Student;

use App\Enums\MarkedVia;
use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Student self check-in, by scanning the rotating QR code or by typing the
 * short code shown next to it.
 *
 * Both paths call the same service method, so the roster check, the duplicate
 * check and the lateness rule are applied identically. The only difference
 * that reaches the database is how the student got here, recorded as
 * marked_via so a lecturer can tell a scan from a manual mark.
 */
class CheckInController extends Controller
{
    public function __construct(protected AttendanceService $attendance) {}

    /**
     * The typed-code form. Useful when a phone camera will not focus, and the
     * only way to demonstrate check-in without a second device.
     */
    public function form(): View
    {
        return view('student.checkin.form');
    }

    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $session = $this->attendance->resolveByCode($validated['code']);

        if (! $session) {
            return back()->withInput()->with('error', 'That code is not valid, or it has expired. Ask for the current one.');
        }

        return $this->record($session, MarkedVia::Code);
    }

    /**
     * Landing point for a scanned QR code.
     *
     * The route sits behind auth, so a signed-out student is sent to the login
     * page and returned here afterwards - which is what happens on a phone that
     * has never signed in.
     */
    public function scan(string $token): RedirectResponse
    {
        $session = $this->attendance->resolveByToken($token);

        if (! $session) {
            return redirect()->route('student.checkin.form')
                ->with('error', 'That code has expired. Scan the current one, or type the six-character code.');
        }

        return $this->record($session, MarkedVia::Qr);
    }

    protected function record($session, MarkedVia $via): RedirectResponse
    {
        $student = auth()->user()->student;

        if (! $student) {
            return redirect()->route('student.checkin.form')
                ->with('error', 'This account is not linked to a student profile.');
        }

        $result = $this->attendance->checkIn($session, $student, $via);

        return redirect()
            ->route('student.attendance.index')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
