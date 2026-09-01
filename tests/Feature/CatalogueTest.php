<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsMis;
use Tests\TestCase;

/**
 * The shared catalogue and roster: courses, class sections, weekly timetables,
 * and the enrolment list both modules read.
 */
class CatalogueTest extends TestCase
{
    use RefreshDatabase, SeedsMis;

    public function test_the_registry_adds_a_course(): void
    {
        $this->actingAs($this->makeAdmin())->post(route('admin.courses.store'), [
            'department_id' => $this->makeDepartment()->id,
            'code' => 'CS404',
            'title' => 'Distributed Systems',
            'credit_hours' => 3,
        ])->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseHas('courses', ['code' => 'CS404']);
    }

    public function test_a_duplicate_course_code_is_refused(): void
    {
        Course::factory()->create(['department_id' => $this->makeDepartment()->id, 'code' => 'CS101']);

        $this->actingAs($this->makeAdmin())->post(route('admin.courses.store'), [
            'department_id' => $this->makeDepartment()->id,
            'code' => 'CS101',
            'title' => 'Another course with the same code',
            'credit_hours' => 3,
        ])->assertSessionHasErrors('code');
    }

    public function test_a_lower_case_course_code_is_refused(): void
    {
        $this->actingAs($this->makeAdmin())->post(route('admin.courses.store'), [
            'department_id' => $this->makeDepartment()->id,
            'code' => 'cs101',
            'title' => 'Introduction to Programming',
            'credit_hours' => 3,
        ])->assertSessionHasErrors('code');
    }

    /** Deleting would cascade to every session and assignment under it. */
    public function test_a_course_with_sections_cannot_be_deleted(): void
    {
        $section = $this->makeSection();

        $this->actingAs($this->makeAdmin())
            ->delete(route('admin.courses.destroy', $section->course))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('courses', ['id' => $section->course_id]);
    }

    public function test_two_sections_of_one_course_cannot_share_a_code_in_a_term(): void
    {
        $section = $this->makeSection();

        $this->actingAs($this->makeAdmin())->post(route('admin.sections.store'), [
            'course_id' => $section->course_id,
            'lecturer_id' => $section->lecturer_id,
            'term' => $section->term,
            'section_code' => $section->section_code,
        ])->assertSessionHasErrors('section_code');
    }

    public function test_the_registry_enrols_a_student(): void
    {
        $section = $this->makeSection();
        $student = $this->makeStudent();

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.sections.enroll', $section), ['student_id' => $student->id])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('enrollments', [
            'class_section_id' => $section->id,
            'student_id' => $student->id,
            'status' => EnrollmentStatus::Enrolled->value,
        ]);
    }

    /**
     * Re-enrolling must revive the original row, not add a second: two rows
     * would split the student's attendance history for the same class in two.
     */
    public function test_re_enrolling_revives_the_existing_row(): void
    {
        $section = $this->makeSection();
        $student = $this->makeStudent();

        $enrollment = Enrollment::factory()->create([
            'class_section_id' => $section->id,
            'student_id' => $student->id,
            'status' => EnrollmentStatus::Dropped,
        ]);

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.sections.enroll', $section), ['student_id' => $student->id]);

        $this->assertDatabaseCount('enrollments', 1);
        $this->assertSame(EnrollmentStatus::Enrolled, $enrollment->refresh()->status);
    }

    public function test_withdrawing_a_student_with_history_drops_rather_than_deletes(): void
    {
        $section = $this->makeSection();
        $student = $this->makeStudent();
        $enrollment = $this->enroll($student, $section);

        $session = AttendanceSession::factory()->create(['class_section_id' => $section->id]);
        AttendanceRecord::factory()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($this->makeAdmin())
            ->delete(route('admin.sections.unenroll', [$section, $enrollment]))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('enrollments', 1);
        $this->assertSame(EnrollmentStatus::Dropped, $enrollment->refresh()->status);
        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_withdrawing_a_student_with_no_history_removes_the_row(): void
    {
        $section = $this->makeSection();
        $enrollment = $this->enroll($this->makeStudent(), $section);

        $this->actingAs($this->makeAdmin())
            ->delete(route('admin.sections.unenroll', [$section, $enrollment]));

        $this->assertDatabaseCount('enrollments', 0);
    }

    // ------------------------------------------------------ student enrolment

    public function test_a_student_enrols_themselves_from_the_catalogue(): void
    {
        $student = $this->makeStudentWithAccount();
        $section = $this->makeSection();

        $this->actingAs($student->user)
            ->post(route('student.sections.enroll', $section))
            ->assertRedirect(route('student.sections.index'));

        $this->assertDatabaseHas('enrollments', [
            'class_section_id' => $section->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_a_student_cannot_enrol_twice(): void
    {
        $student = $this->makeStudentWithAccount();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $this->actingAs($student->user)
            ->post(route('student.sections.enroll', $section))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_a_student_may_leave_a_class_that_holds_nothing_about_them(): void
    {
        $student = $this->makeStudentWithAccount();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $this->actingAs($student->user)
            ->delete(route('student.sections.unenroll', $section))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('enrollments', 0);
    }

    /**
     * A student must not be able to erase a register by leaving the class.
     */
    public function test_a_student_cannot_leave_once_attendance_exists(): void
    {
        $student = $this->makeStudentWithAccount();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $session = AttendanceSession::factory()->create(['class_section_id' => $section->id]);
        AttendanceRecord::factory()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
        ]);

        $this->actingAs($student->user)
            ->delete(route('student.sections.unenroll', $section))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseCount('attendance_records', 1);
    }

    // ------------------------------------------------------------- timetable

    public function test_sessions_are_generated_from_the_weekly_timetable(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);

        ClassSchedule::create([
            'class_section_id' => $section->id,
            'day_of_week' => 1,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        // A four-week window starting on a Monday holds exactly four Mondays.
        $from = now()->startOfWeek();

        $this->actingAs($lecturer)->post(route('faculty.attendance.generate'), [
            'class_section_id' => $section->id,
            'from' => $from->toDateString(),
            'to' => $from->copy()->addWeeks(3)->addDays(6)->toDateString(),
        ])->assertSessionHas('success');

        $this->assertSame(4, $section->sessions()->count());
    }

    public function test_generating_twice_over_the_same_weeks_adds_nothing(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);

        ClassSchedule::create([
            'class_section_id' => $section->id,
            'day_of_week' => 3,
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
        ]);

        $payload = [
            'class_section_id' => $section->id,
            'from' => now()->startOfWeek()->toDateString(),
            'to' => now()->startOfWeek()->addWeeks(2)->toDateString(),
        ];

        $this->actingAs($lecturer)->post(route('faculty.attendance.generate'), $payload);
        $first = $section->sessions()->count();

        $this->actingAs($lecturer)->post(route('faculty.attendance.generate'), $payload);

        $this->assertSame($first, $section->sessions()->count());
    }

    public function test_generating_without_a_timetable_is_reported_not_silent(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);

        $this->actingAs($lecturer)->post(route('faculty.attendance.generate'), [
            'class_section_id' => $section->id,
            'from' => now()->toDateString(),
            'to' => now()->addWeek()->toDateString(),
        ])->assertSessionHas('error');

        $this->assertSame(0, $section->sessions()->count());
    }

    public function test_a_timetable_slot_cannot_be_added_twice(): void
    {
        $section = $this->makeSection();
        $admin = $this->makeAdmin();

        $payload = ['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '11:00'];

        $this->actingAs($admin)->post(route('admin.sections.schedules.store', $section), $payload);
        $this->actingAs($admin)->post(route('admin.sections.schedules.store', $section), $payload)
            ->assertSessionHas('error');

        $this->assertSame(1, $section->schedules()->count());
    }

    public function test_a_lecturer_cannot_generate_for_another_lecturers_class(): void
    {
        $section = $this->makeSection();

        $this->actingAs($this->makeFaculty())->post(route('faculty.attendance.generate'), [
            'class_section_id' => $section->id,
            'from' => now()->toDateString(),
            'to' => now()->addWeek()->toDateString(),
        ])->assertForbidden();
    }

    public function test_a_student_cannot_reach_the_registry_catalogue(): void
    {
        $this->actingAs($this->makeStudentWithAccount()->user)
            ->get(route('admin.courses.index'))
            ->assertForbidden();
    }
}
