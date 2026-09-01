<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\ComplaintStatus;
use App\Enums\SubmissionStatus;
use App\Models\Assignment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSection;
use App\Models\Complaint;
use App\Models\Student;
use App\Models\Submission;
use App\Services\StudentInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMis;
use Tests\TestCase;

/**
 * The point of the whole system: one profile, three subsystems reading from it.
 */
class ModuleIntegrationTest extends TestCase
{
    use RefreshDatabase, SeedsMis;

    protected StudentInsightService $insights;

    protected function setUp(): void
    {
        parent::setUp();
        $this->insights = app(StudentInsightService::class);
    }

    /** Attach $statuses attendance records to a student in one section. */
    protected function giveAttendance(Student $student, ClassSection $section, array $statuses): void
    {
        foreach ($statuses as $i => $status) {
            $session = AttendanceSession::factory()->create([
                'class_section_id' => $section->id,
                'session_date' => now()->subDays($i + 1)->toDateString(),
                'start_time' => sprintf('%02d:00:00', 7 + $i),
                'end_time' => sprintf('%02d:00:00', 9 + $i),
            ]);

            AttendanceRecord::factory()->create([
                'attendance_session_id' => $session->id,
                'student_id' => $student->id,
                'status' => AttendanceStatus::from($status),
            ]);
        }
    }

    public function test_attendance_percentage_is_read_from_the_attendance_module(): void
    {
        $student = $this->makeStudent();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $this->giveAttendance($student, $section, ['present', 'present', 'present', 'absent']);

        $result = $this->insights->attendance($student);

        $this->assertSame(4, $result['recorded']);
        $this->assertSame(3, $result['attended']);
        $this->assertSame(75.0, $result['percentage']);
        $this->assertFalse($result['at_risk']);
    }

    /**
     * An excused absence leaves the denominator rather than counting against
     * the student - the same rule the attendance mini project applied.
     */
    public function test_an_excused_absence_leaves_the_denominator(): void
    {
        $student = $this->makeStudent();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $this->giveAttendance($student, $section, ['present', 'present', 'excused', 'absent']);

        $result = $this->insights->attendance($student);

        $this->assertSame(4, $result['recorded']);
        $this->assertSame(3, $result['countable']);
        $this->assertSame(66.7, $result['percentage']);
    }

    public function test_a_student_below_the_threshold_is_flagged_at_risk(): void
    {
        $student = $this->makeStudent();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $this->giveAttendance($student, $section, ['present', 'absent', 'absent', 'absent']);

        $result = $this->insights->attendance($student);

        $this->assertSame(25.0, $result['percentage']);
        $this->assertTrue($result['at_risk']);
    }

    public function test_a_student_with_no_records_is_not_flagged_at_risk(): void
    {
        $result = $this->insights->attendance($this->makeStudent());

        $this->assertSame(0, $result['recorded']);
        $this->assertSame(0.0, $result['percentage']);
        $this->assertFalse($result['at_risk']);
    }

    public function test_submission_counts_are_read_from_the_submission_module(): void
    {
        $student = $this->makeStudent();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $issued = Assignment::factory()->count(3)->create(['class_section_id' => $section->id]);

        Submission::factory()->create([
            'assignment_id' => $issued[0]->id,
            'student_id' => $student->id,
            'status' => SubmissionStatus::OnTime,
        ]);
        Submission::factory()->create([
            'assignment_id' => $issued[1]->id,
            'student_id' => $student->id,
            'status' => SubmissionStatus::Late,
        ]);

        $result = $this->insights->submissions($student);

        $this->assertSame(3, $result['issued']);
        $this->assertSame(2, $result['submitted']);
        $this->assertSame(1, $result['on_time']);
        $this->assertSame(1, $result['late']);
        // The third assignment was issued but never submitted.
        $this->assertSame(1, $result['missing']);
    }

    public function test_complaint_counts_are_read_from_the_complaint_module(): void
    {
        $student = $this->makeStudent();

        Complaint::factory()->create(['student_id' => $student->id, 'status' => ComplaintStatus::Pending]);
        Complaint::factory()->create(['student_id' => $student->id, 'status' => ComplaintStatus::InProgress]);
        Complaint::factory()->resolved()->create(['student_id' => $student->id]);

        $result = $this->insights->complaints($student);

        $this->assertSame(3, $result['total']);
        $this->assertSame(1, $result['pending']);
        $this->assertSame(1, $result['in_progress']);
        $this->assertSame(1, $result['resolved']);
        $this->assertSame(2, $result['open']);
    }

    /**
     * One profile page must show all three modules together - this is what the
     * three separate mini projects could not do.
     */
    public function test_one_profile_returns_all_three_modules(): void
    {
        $student = $this->makeStudent();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $this->giveAttendance($student, $section, ['present', 'absent']);
        $assignment = Assignment::factory()->create(['class_section_id' => $section->id]);
        Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id]);
        Complaint::factory()->create(['student_id' => $student->id]);

        $result = $this->insights->forStudent($student);

        $this->assertArrayHasKey('attendance', $result);
        $this->assertArrayHasKey('submissions', $result);
        $this->assertArrayHasKey('complaints', $result);

        $this->assertSame(50.0, $result['attendance']['percentage']);
        $this->assertSame(1, $result['submissions']['submitted']);
        $this->assertSame(1, $result['complaints']['total']);
        $this->assertSame(1, $result['enrollments']);
    }

    /**
     * A cohort report must not cost three queries per student, or a report for
     * a 60-student program would fire 180 queries.
     */
    public function test_a_cohort_is_summarised_in_a_fixed_number_of_queries(): void
    {
        $section = $this->makeSection();
        $assignment = Assignment::factory()->create(['class_section_id' => $section->id]);

        // A section cannot meet twice at the same moment, so the sessions are
        // created once and every student is marked against them.
        $sessions = collect(['present', 'absent'])->map(fn ($status, $i) => [
            'status' => $status,
            'session' => AttendanceSession::factory()->create([
                'class_section_id' => $section->id,
                'session_date' => now()->subDays($i + 1)->toDateString(),
                'start_time' => sprintf('%02d:00:00', 8 + $i),
                'end_time' => sprintf('%02d:00:00', 10 + $i),
            ]),
        ]);

        $students = collect();

        foreach (range(1, 10) as $i) {
            $student = $this->makeStudent();
            $this->enroll($student, $section);

            foreach ($sessions as $row) {
                AttendanceRecord::factory()->create([
                    'attendance_session_id' => $row['session']->id,
                    'student_id' => $student->id,
                    'status' => AttendanceStatus::from($row['status']),
                ]);
            }

            Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id]);
            Complaint::factory()->create(['student_id' => $student->id]);
            $students->push($student);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $result = $this->insights->forCohort($students);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(10, $result);
        $this->assertSame(50.0, $result[$students->first()->id]['attendance_percentage']);
        // One query per module, regardless of how many students there are.
        $this->assertCount(3, $queries, 'A cohort summary should cost three queries, one per module.');
    }

    public function test_archiving_a_profile_preserves_its_module_history(): void
    {
        $student = $this->makeStudent();
        $section = $this->makeSection();
        $this->enroll($student, $section);
        $this->giveAttendance($student, $section, ['present', 'present']);
        Complaint::factory()->create(['student_id' => $student->id]);

        $this->actingAs($this->makeAdmin())->delete(route('admin.students.destroy', $student));

        $this->assertSoftDeleted('students', ['id' => $student->id]);
        // The history must survive, otherwise archiving would destroy the
        // record the university is required to keep.
        $this->assertSame(2, AttendanceRecord::where('student_id', $student->id)->count());
        $this->assertSame(1, Complaint::where('student_id', $student->id)->count());
    }
}
