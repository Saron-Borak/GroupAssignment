<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\MarkedVia;
use App\Enums\SessionStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsMis;
use Tests\TestCase;

/**
 * QR self check-in and the typed-code fallback.
 *
 * These are the cases the feature exists to refuse: an expired code, a session
 * that is not open, somebody who is not on the roster, and a second attempt by
 * a student already marked.
 */
class CheckInTest extends TestCase
{
    use RefreshDatabase, SeedsMis;

    public function test_a_valid_scan_marks_the_student_present(): void
    {
        $student = $this->makeStudentWithAccount();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $session = AttendanceSession::factory()->open()->create(['class_section_id' => $section->id]);

        $this->actingAs($student->user)
            ->get(route('checkin.scan', $session->qr_token))
            ->assertRedirect(route('student.attendance.index'));

        $record = AttendanceRecord::firstWhere('student_id', $student->id);

        $this->assertNotNull($record);
        $this->assertSame(AttendanceStatus::Present, $record->status);
        $this->assertSame(MarkedVia::Qr, $record->marked_via);
    }

    public function test_the_typed_code_produces_the_same_record(): void
    {
        $student = $this->makeStudentWithAccount();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $session = AttendanceSession::factory()->open()->create(['class_section_id' => $section->id]);

        $this->actingAs($student->user)
            ->post(route('student.checkin.submit'), ['code' => $session->checkin_code])
            ->assertRedirect(route('student.attendance.index'));

        $record = AttendanceRecord::firstWhere('student_id', $student->id);

        $this->assertSame(AttendanceStatus::Present, $record->status);
        $this->assertSame(MarkedVia::Code, $record->marked_via);
    }

    public function test_the_code_is_matched_case_insensitively(): void
    {
        $student = $this->makeStudentWithAccount();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $session = AttendanceSession::factory()->open()->create(['class_section_id' => $section->id]);

        $this->actingAs($student->user)
            ->post(route('student.checkin.submit'), ['code' => strtolower($session->checkin_code)])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('attendance_records', 1);
    }

    /** Arriving after the late window is still a check-in, but a late one. */
    public function test_arriving_late_is_recorded_as_late(): void
    {
        $student = $this->makeStudentWithAccount();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $session = AttendanceSession::factory()->openAndLate()->create(['class_section_id' => $section->id]);

        $this->actingAs($student->user)->get(route('checkin.scan', $session->qr_token));

        $this->assertSame(
            AttendanceStatus::Late,
            AttendanceRecord::firstWhere('student_id', $student->id)->status,
        );
    }

    public function test_an_expired_token_is_refused(): void
    {
        $student = $this->makeStudentWithAccount();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $session = AttendanceSession::factory()->openWithExpiredToken()->create([
            'class_section_id' => $section->id,
        ]);

        $this->actingAs($student->user)
            ->get(route('checkin.scan', $session->qr_token))
            ->assertRedirect(route('student.checkin.form'));

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_a_session_that_is_not_open_refuses_check_in(): void
    {
        $student = $this->makeStudentWithAccount();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        // Closed, but carrying a token as though it had been left behind.
        $session = AttendanceSession::factory()->open()->create([
            'class_section_id' => $section->id,
            'status' => SessionStatus::Closed,
        ]);

        $this->actingAs($student->user)
            ->post(route('student.checkin.submit'), ['code' => $session->checkin_code])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_a_student_outside_the_roster_cannot_check_in(): void
    {
        $outsider = $this->makeStudentWithAccount();
        $session = AttendanceSession::factory()->open()->create([
            'class_section_id' => $this->makeSection()->id,
        ]);

        $this->actingAs($outsider->user)
            ->post(route('student.checkin.submit'), ['code' => $session->checkin_code])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_checking_in_twice_does_not_create_a_second_record(): void
    {
        $student = $this->makeStudentWithAccount();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $session = AttendanceSession::factory()->open()->create(['class_section_id' => $section->id]);

        $this->actingAs($student->user)->get(route('checkin.scan', $session->qr_token));
        $this->actingAs($student->user)
            ->get(route('checkin.scan', $session->qr_token))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendance_records', 1);
    }

    /** A phone that has never signed in must land on login, not a 403. */
    public function test_a_signed_out_scan_is_sent_to_login(): void
    {
        $session = AttendanceSession::factory()->open()->create();

        $this->get(route('checkin.scan', $session->qr_token))->assertRedirect(route('login'));
    }

    public function test_opening_a_session_mints_a_token_and_a_code(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);
        $session = AttendanceSession::factory()->scheduled()->create(['class_section_id' => $section->id]);

        $this->actingAs($lecturer)
            ->put(route('faculty.attendance.open', $session))
            ->assertRedirect(route('faculty.attendance.qr', $session));

        $session->refresh();

        $this->assertSame(SessionStatus::Open, $session->status);
        $this->assertNotNull($session->qr_token);
        $this->assertSame(6, strlen($session->checkin_code));
        $this->assertTrue($session->qr_expires_at->isFuture());
    }

    public function test_rotating_the_token_invalidates_the_previous_one(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);
        $session = AttendanceSession::factory()->open()->create(['class_section_id' => $section->id]);

        $old = $session->qr_token;

        app(AttendanceService::class)->rotateQr($session);

        $this->assertNotSame($old, $session->refresh()->qr_token);
        $this->assertNull(app(AttendanceService::class)->resolveByToken($old));
    }

    /**
     * Closing is what turns "nobody marked them" into an absence. Without it a
     * session left open would read as though everyone attended.
     */
    public function test_closing_marks_everyone_who_never_checked_in_as_absent(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);

        $arrived = $this->makeStudentWithAccount();
        $this->enroll($arrived, $section);

        foreach (range(1, 3) as $i) {
            $this->enroll($this->makeStudent(), $section);
        }

        $session = AttendanceSession::factory()->open()->create(['class_section_id' => $section->id]);

        $this->actingAs($arrived->user)->get(route('checkin.scan', $session->qr_token));

        $this->actingAs($lecturer)->put(route('faculty.attendance.close', $session));

        $session->refresh();

        $this->assertSame(SessionStatus::Closed, $session->status);
        $this->assertNull($session->qr_token, 'A closed session must stop accepting scans.');
        $this->assertSame(4, $session->records()->count());
        $this->assertSame(3, $session->records()->where('status', AttendanceStatus::Absent)->count());
        $this->assertSame(
            AttendanceStatus::Present,
            $session->records()->where('student_id', $arrived->id)->first()->status,
            'Closing must not overwrite a mark that already exists.',
        );
    }

    public function test_a_lecturer_cannot_open_another_lecturers_session(): void
    {
        $session = AttendanceSession::factory()->scheduled()->create([
            'class_section_id' => $this->makeSection()->id,
        ]);

        $this->actingAs($this->makeFaculty())
            ->put(route('faculty.attendance.open', $session))
            ->assertForbidden();
    }

    public function test_the_kiosk_refresh_endpoint_returns_the_live_tally(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);
        $student = $this->makeStudentWithAccount();
        $this->enroll($student, $section);

        $session = AttendanceSession::factory()->open()->create(['class_section_id' => $section->id]);

        $this->actingAs($student->user)->get(route('checkin.scan', $session->qr_token));

        $this->actingAs($lecturer)
            ->getJson(route('faculty.attendance.qr.refresh', $session))
            ->assertOk()
            ->assertJson(['open' => true, 'present' => 1, 'late' => 0, 'total' => 1])
            ->assertJsonStructure(['svg', 'code', 'expires_in', 'recent']);
    }

    /**
     * Regression: the kiosk polls on a fixed interval, so a token must be
     * replaced while it can still survive until the next poll. Rotating only
     * when a few seconds remain leaves a window in which the code on the
     * projector has expired but has not yet been replaced - the QR scans
     * cleanly and the check-in is then refused.
     */
    public function test_the_kiosk_rotates_before_the_token_can_expire_between_polls(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);
        $session = AttendanceSession::factory()->open()->create(['class_section_id' => $section->id]);

        // Wind the clock to just after the next poll would land.
        $ttl = (int) config('mis.qr_ttl_seconds');
        $refresh = (int) config('mis.qr_refresh_seconds');

        $session->update(['qr_expires_at' => now()->addSeconds($ttl - $refresh)]);
        $before = $session->qr_token;

        $this->actingAs($lecturer)
            ->getJson(route('faculty.attendance.qr.refresh', $session))
            ->assertOk();

        $session->refresh();

        $this->assertNotSame($before, $session->qr_token, 'The token should have been replaced.');
        $this->assertGreaterThan(
            $refresh,
            $session->qrSecondsLeft(),
            'A freshly minted token must outlive the gap until the next poll.',
        );
    }

    public function test_a_token_with_plenty_of_life_left_is_not_rotated(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);
        $session = AttendanceSession::factory()->open()->create(['class_section_id' => $section->id]);

        $session->update(['qr_expires_at' => now()->addHour()]);
        $before = $session->qr_token;

        $this->actingAs($lecturer)->getJson(route('faculty.attendance.qr.refresh', $session));

        $this->assertSame($before, $session->refresh()->qr_token);
    }
}
