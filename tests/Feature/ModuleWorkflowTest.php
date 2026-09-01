<?php

namespace Tests\Feature;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use App\Enums\SubmissionStatus;
use App\Mail\ComplaintFiled;
use App\Models\Assignment;
use App\Models\AttendanceSession;
use App\Models\Complaint;
use App\Models\Submission;
use App\Services\ComplaintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Support\SeedsMis;
use Tests\TestCase;

/**
 * End-to-end workflows for the three integrated modules: taking a register,
 * submitting an assignment, and handling a complaint.
 */
class ModuleWorkflowTest extends TestCase
{
    use RefreshDatabase, SeedsMis;

    // ------------------------------------------------- attendance module ---

    public function test_a_lecturer_creates_a_session_and_marks_the_register(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);
        $a = $this->makeStudent();
        $b = $this->makeStudent();
        $this->enroll($a, $section);
        $this->enroll($b, $section);

        $this->actingAs($lecturer)->post(route('faculty.attendance.store'), [
            'class_section_id' => $section->id,
            'session_date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'topic' => 'Week 1',
        ])->assertSessionHasNoErrors();

        $session = AttendanceSession::firstWhere('class_section_id', $section->id);
        $this->assertNotNull($session);

        $this->actingAs($lecturer)->put(route('faculty.attendance.mark.store', $session), [
            'marks' => [$a->id => 'present', $b->id => 'absent'],
        ])->assertRedirect(route('faculty.attendance.index'));

        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id, 'student_id' => $a->id, 'status' => 'present',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $b->id, 'status' => 'absent',
        ]);
    }

    public function test_re_marking_a_register_updates_rather_than_duplicates(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);
        $student = $this->makeStudent();
        $this->enroll($student, $section);
        $session = AttendanceSession::factory()->create(['class_section_id' => $section->id]);

        foreach (['absent', 'present'] as $status) {
            $this->actingAs($lecturer)->put(route('faculty.attendance.mark.store', $session), [
                'marks' => [$student->id => $status],
            ]);
        }

        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertDatabaseHas('attendance_records', ['student_id' => $student->id, 'status' => 'present']);
    }

    /** A tampered form must not create a record against another class's student. */
    public function test_marks_for_students_outside_the_roster_are_discarded(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);
        $enrolled = $this->makeStudent();
        $outsider = $this->makeStudent();
        $this->enroll($enrolled, $section);
        $session = AttendanceSession::factory()->create(['class_section_id' => $section->id]);

        $this->actingAs($lecturer)->put(route('faculty.attendance.mark.store', $session), [
            'marks' => [$enrolled->id => 'present', $outsider->id => 'present'],
        ]);

        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertDatabaseMissing('attendance_records', ['student_id' => $outsider->id]);
    }

    public function test_a_lecturer_cannot_mark_another_lecturers_register(): void
    {
        $owner = $this->makeFaculty();
        $session = AttendanceSession::factory()->create([
            'class_section_id' => $this->makeSection($owner)->id,
        ]);

        $this->actingAs($this->makeFaculty())
            ->get(route('faculty.attendance.mark', $session))
            ->assertForbidden();
    }

    public function test_a_clashing_session_is_refused(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);
        AttendanceSession::factory()->create([
            'class_section_id' => $section->id,
            'session_date' => '2026-09-10',
            'start_time' => '08:00:00',
        ]);

        $this->actingAs($lecturer)->post(route('faculty.attendance.store'), [
            'class_section_id' => $section->id,
            'session_date' => '2026-09-10',
            'start_time' => '08:00',
            'end_time' => '10:00',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('attendance_sessions', 1);
    }

    // ------------------------------------------------- submission module ---

    public function test_a_lecturer_issues_an_assignment(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);

        $this->actingAs($lecturer)->post(route('faculty.assignments.store'), [
            'class_section_id' => $section->id,
            'title' => 'Assignment 1: Database Design',
            'description' => 'Submit an ERD as a PDF.',
            'deadline' => now()->addWeek()->format('Y-m-d H:i:s'),
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assignments', [
            'class_section_id' => $section->id,
            'title' => 'Assignment 1: Database Design',
        ]);
    }

    public function test_a_student_submits_a_file_before_the_deadline(): void
    {
        Storage::fake('local');

        $section = $this->makeSection();
        $student = $this->makeStudentWithAccount();
        $this->enroll($student, $section);

        $assignment = Assignment::factory()->create([
            'class_section_id' => $section->id,
            'deadline' => now()->addDays(3),
        ]);

        $this->actingAs($student->user)
            ->post(route('student.assignments.submit', $assignment), [
                'file' => UploadedFile::fake()->create('report.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $submission = Submission::firstWhere('assignment_id', $assignment->id);

        $this->assertNotNull($submission);
        $this->assertSame(SubmissionStatus::OnTime, $submission->status);
        $this->assertSame('report.pdf', $submission->original_filename);
        Storage::disk('local')->assertExists($submission->file_path);
    }

    /** On-time or late is decided by the server clock, never by the request. */
    public function test_a_submission_after_the_deadline_is_recorded_as_late(): void
    {
        Storage::fake('local');

        $section = $this->makeSection();
        $student = $this->makeStudentWithAccount();
        $this->enroll($student, $section);

        $assignment = Assignment::factory()->create([
            'class_section_id' => $section->id,
            'deadline' => now()->subDay(),
        ]);

        $this->actingAs($student->user)->post(route('student.assignments.submit', $assignment), [
            'file' => UploadedFile::fake()->create('late.pdf', 100, 'application/pdf'),
        ]);

        $this->assertSame(
            SubmissionStatus::Late,
            Submission::firstWhere('assignment_id', $assignment->id)->status,
        );
    }

    public function test_resubmitting_replaces_the_file_rather_than_adding_a_second(): void
    {
        Storage::fake('local');

        $section = $this->makeSection();
        $student = $this->makeStudentWithAccount();
        $this->enroll($student, $section);
        $assignment = Assignment::factory()->create(['class_section_id' => $section->id, 'deadline' => now()->addWeek()]);

        $this->actingAs($student->user)->post(route('student.assignments.submit', $assignment), [
            'file' => UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'),
        ]);
        $original = Submission::firstWhere('assignment_id', $assignment->id)->file_path;

        $this->actingAs($student->user)->post(route('student.assignments.submit', $assignment), [
            'file' => UploadedFile::fake()->create('second.pdf', 100, 'application/pdf'),
        ]);

        $this->assertDatabaseCount('submissions', 1);

        $submission = Submission::firstWhere('assignment_id', $assignment->id);
        $this->assertSame('second.pdf', $submission->original_filename);
        Storage::disk('local')->assertMissing($original);
    }

    public function test_a_student_cannot_submit_to_a_class_they_are_not_enrolled_in(): void
    {
        Storage::fake('local');

        $assignment = Assignment::factory()->create(['class_section_id' => $this->makeSection()->id]);

        $this->actingAs($this->makeStudentWithAccount()->user)
            ->post(route('student.assignments.submit', $assignment), [
                'file' => UploadedFile::fake()->create('sneaky.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_an_executable_upload_is_rejected(): void
    {
        Storage::fake('local');

        $section = $this->makeSection();
        $student = $this->makeStudentWithAccount();
        $this->enroll($student, $section);
        $assignment = Assignment::factory()->create(['class_section_id' => $section->id]);

        $this->actingAs($student->user)
            ->post(route('student.assignments.submit', $assignment), [
                'file' => UploadedFile::fake()->create('payload.exe', 100, 'application/x-msdownload'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('submissions', 0);
    }

    // -------------------------------------------------- complaint module ---

    public function test_a_student_files_a_complaint_and_gets_a_reference(): void
    {
        $student = $this->makeStudentWithAccount();

        $this->actingAs($student->user)->post(route('student.complaints.store'), [
            'category' => 'facility',
            'title' => 'Air conditioning not working in B-204',
            'description' => 'The room has been uncomfortably hot for the last two weeks of lectures.',
        ])->assertRedirect(route('student.complaints.index'));

        $complaint = Complaint::firstWhere('student_id', $student->id);

        $this->assertNotNull($complaint);
        $this->assertSame(ComplaintStatus::Pending, $complaint->status);
        $this->assertMatchesRegularExpression('/^CMP-\d{5}$/', $complaint->reference);
    }

    public function test_references_are_sequential(): void
    {
        $student = $this->makeStudentWithAccount();

        foreach (['First complaint here', 'Second complaint here'] as $title) {
            $this->actingAs($student->user)->post(route('student.complaints.store'), [
                'category' => 'academic',
                'title' => $title,
                'description' => 'A description long enough to satisfy the minimum length rule.',
            ]);
        }

        $this->assertSame(
            ['CMP-00001', 'CMP-00002'],
            Complaint::orderBy('id')->pluck('reference')->all(),
        );
    }

    public function test_a_very_short_description_is_rejected(): void
    {
        $this->actingAs($this->makeStudentWithAccount()->user)
            ->post(route('student.complaints.store'), [
                'category' => 'other',
                'title' => 'Problem',
                'description' => 'broken',
            ])
            ->assertSessionHasErrors('description');

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_a_student_cannot_read_another_students_complaint(): void
    {
        $complaint = Complaint::factory()->create(['student_id' => $this->makeStudent()->id]);

        $this->actingAs($this->makeStudentWithAccount()->user)
            ->get(route('student.complaints.show', $complaint))
            ->assertForbidden();
    }

    public function test_the_registry_responds_and_resolves_a_case(): void
    {
        $admin = $this->makeAdmin();
        $complaint = Complaint::factory()->create([
            'student_id' => $this->makeStudent()->id,
            'status' => ComplaintStatus::Pending,
        ]);

        $this->actingAs($admin)->put(route('admin.complaints.respond', $complaint), [
            'status' => 'resolved',
            'admin_response' => 'The unit was serviced on Monday.',
        ])->assertSessionHas('success');

        $complaint->refresh();

        $this->assertSame(ComplaintStatus::Resolved, $complaint->status);
        $this->assertSame($admin->id, $complaint->handled_by);
        $this->assertNotNull($complaint->resolved_at);
    }

    /** Reopening must clear the resolution date rather than leave a stale one. */
    public function test_reopening_a_case_clears_the_resolved_date(): void
    {
        $admin = $this->makeAdmin();
        $complaint = Complaint::factory()->resolved()->create(['student_id' => $this->makeStudent()->id]);

        $this->actingAs($admin)->put(route('admin.complaints.respond', $complaint), [
            'status' => 'in_progress',
            'admin_response' => 'Reopened; the fault has recurred.',
        ]);

        $complaint->refresh();

        $this->assertSame(ComplaintStatus::InProgress, $complaint->status);
        $this->assertNull($complaint->resolved_at);
    }

    public function test_a_student_cannot_reach_the_registry_complaint_queue(): void
    {
        $this->actingAs($this->makeStudentWithAccount()->user)
            ->get(route('admin.complaints.index'))
            ->assertForbidden();
    }

    // --------------------------------------------- assignment lifecycle ----

    public function test_a_lecturer_edits_an_assignment(): void
    {
        $lecturer = $this->makeFaculty();
        $assignment = Assignment::factory()->create([
            'class_section_id' => $this->makeSection($lecturer)->id,
        ]);

        $this->actingAs($lecturer)->put(route('faculty.assignments.update', $assignment), [
            'title' => 'Assignment 1 (revised)',
            'description' => 'Submit a single PDF of no more than ten pages.',
            'deadline' => now()->addWeeks(2)->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('faculty.assignments.show', $assignment));

        $this->assertSame('Assignment 1 (revised)', $assignment->refresh()->title);
    }

    public function test_an_assignment_with_no_submissions_can_be_withdrawn(): void
    {
        $lecturer = $this->makeFaculty();
        $assignment = Assignment::factory()->create([
            'class_section_id' => $this->makeSection($lecturer)->id,
        ]);

        $this->actingAs($lecturer)
            ->delete(route('faculty.assignments.destroy', $assignment))
            ->assertRedirect(route('faculty.assignments.index'));

        $this->assertDatabaseCount('assignments', 0);
    }

    /** Deleting would cascade to the submissions and orphan the stored files. */
    public function test_an_assignment_with_submissions_cannot_be_withdrawn(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);
        $student = $this->makeStudent();
        $this->enroll($student, $section);

        $assignment = Assignment::factory()->create(['class_section_id' => $section->id]);
        Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($lecturer)
            ->delete(route('faculty.assignments.destroy', $assignment))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('assignments', 1);
    }

    public function test_a_lecturer_cannot_edit_another_lecturers_assignment(): void
    {
        $assignment = Assignment::factory()->create([
            'class_section_id' => $this->makeSection()->id,
        ]);

        $this->actingAs($this->makeFaculty())->put(route('faculty.assignments.update', $assignment), [
            'title' => 'Hijacked',
            'deadline' => now()->addWeek()->format('Y-m-d H:i:s'),
        ])->assertForbidden();
    }

    public function test_a_student_reads_one_assignment_but_not_another_class(): void
    {
        $student = $this->makeStudentWithAccount();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $mine = Assignment::factory()->create(['class_section_id' => $section->id]);
        $theirs = Assignment::factory()->create(['class_section_id' => $this->makeSection()->id]);

        $this->actingAs($student->user)->get(route('student.assignments.show', $mine))->assertOk();
        $this->actingAs($student->user)->get(route('student.assignments.show', $theirs))->assertForbidden();
    }

    // ----------------------------------- complaint reporting and deletion ---

    public function test_filing_a_complaint_notifies_the_registry(): void
    {
        Mail::fake();

        $admin = $this->makeAdmin();
        $student = $this->makeStudentWithAccount();

        $this->actingAs($student->user)->post(route('student.complaints.store'), [
            'category' => 'facility',
            'title' => 'Broken window in the library',
            'description' => 'The window on the second floor has been broken since last week.',
        ]);

        Mail::assertSent(ComplaintFiled::class, fn ($mail) => $mail->hasTo($admin->email));
    }

    /** A mail server that is down must not lose a complaint that was saved. */
    public function test_a_failing_mailer_does_not_lose_the_complaint(): void
    {
        $this->makeAdmin();
        $student = $this->makeStudentWithAccount();

        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP unreachable'));

        $this->actingAs($student->user)->post(route('student.complaints.store'), [
            'category' => 'other',
            'title' => 'A case filed while the mail server is down',
            'description' => 'The registry should still receive this in the queue.',
        ])->assertRedirect(route('student.complaints.index'));

        $this->assertDatabaseCount('complaints', 1);
    }

    public function test_the_registry_withdraws_a_case(): void
    {
        $complaint = Complaint::factory()->create(['student_id' => $this->makeStudent()->id]);

        $this->actingAs($this->makeAdmin())
            ->delete(route('admin.complaints.destroy', $complaint))
            ->assertRedirect(route('admin.complaints.index'));

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_a_student_cannot_withdraw_their_own_case(): void
    {
        $student = $this->makeStudentWithAccount();
        $complaint = Complaint::factory()->create(['student_id' => $student->id]);

        $this->actingAs($student->user)
            ->delete(route('admin.complaints.destroy', $complaint))
            ->assertForbidden();

        $this->assertDatabaseCount('complaints', 1);
    }

    public function test_the_complaint_summary_counts_by_status_and_category(): void
    {
        $student = $this->makeStudent();

        Complaint::factory()->count(2)->create([
            'student_id' => $student->id,
            'status' => ComplaintStatus::Pending,
            'category' => ComplaintCategory::Facility,
        ]);
        Complaint::factory()->resolved()->create([
            'student_id' => $student->id,
            'category' => ComplaintCategory::Academic,
        ]);

        $summary = app(ComplaintService::class)->summary();

        $this->assertSame(3, $summary['total']);
        $this->assertSame(2, $summary['by_status'][ComplaintStatus::Pending->value]);
        $this->assertSame(1, $summary['by_status'][ComplaintStatus::Resolved->value]);
        $this->assertSame(2, $summary['by_category'][ComplaintCategory::Facility->value]);
        $this->assertSame(33.3, $summary['resolved_rate']);
    }

    public function test_the_complaint_summary_respects_a_date_range(): void
    {
        $student = $this->makeStudent();

        Complaint::factory()->create([
            'student_id' => $student->id,
            'created_at' => now()->subMonths(3),
        ]);
        Complaint::factory()->create(['student_id' => $student->id]);

        $summary = app(ComplaintService::class)->summary(now()->subWeek()->toDateString(), null);

        $this->assertSame(1, $summary['total'], 'The three-month-old case is outside the range.');
    }

    public function test_the_complaint_report_screen_renders_and_exports(): void
    {
        $admin = $this->makeAdmin();
        Complaint::factory()->count(3)->create(['student_id' => $this->makeStudent()->id]);

        $this->actingAs($admin)->get(route('admin.complaints.report'))->assertOk();

        $response = $this->actingAs($admin)->get(route('admin.complaints.report.export'));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Grouping', $response->streamedContent());
    }

    public function test_a_reversed_date_range_is_refused(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.complaints.report', ['from' => '2026-06-01', 'to' => '2026-01-01']))
            ->assertSessionHasErrors('to');
    }
}
