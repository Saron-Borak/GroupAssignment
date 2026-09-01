<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSection;
use App\Models\Student;
use App\Services\AttendanceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMis;
use Tests\TestCase;

/**
 * The attendance arithmetic, against hand-computed fixtures.
 *
 * The rules under test: late counts as attended, an excused absence leaves the
 * denominator rather than counting against the student, and only closed
 * sessions count at all.
 */
class AttendanceReportTest extends TestCase
{
    use RefreshDatabase, SeedsMis;

    protected AttendanceReportService $reports;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reports = app(AttendanceReportService::class);
    }

    /** @var array<string, AttendanceSession> */
    protected array $sessions = [];

    /**
     * Marks a student against a run of closed sessions.
     *
     * The sessions are shared across the roster rather than created per student:
     * a class cannot meet twice at the same moment, and the unique constraint on
     * (section, date, start_time) enforces exactly that.
     *
     * @param  list<AttendanceStatus>  $statuses
     */
    protected function record(Student $student, ClassSection $section, array $statuses): void
    {
        foreach ($statuses as $i => $status) {
            $key = $section->id.':'.$i;

            $session = $this->sessions[$key] ??= AttendanceSession::factory()->create([
                'class_section_id' => $section->id,
                'session_date' => now()->subWeeks($i + 1)->toDateString(),
                'start_time' => '08:00:00',
                'status' => SessionStatus::Closed,
            ]);

            AttendanceRecord::factory()->create([
                'attendance_session_id' => $session->id,
                'student_id' => $student->id,
                'status' => $status,
            ]);
        }
    }

    public function test_late_counts_as_attended(): void
    {
        $student = $this->makeStudent();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $this->record($student, $section, [
            AttendanceStatus::Present,
            AttendanceStatus::Late,
            AttendanceStatus::Present,
            AttendanceStatus::Absent,
        ]);

        $row = $this->reports->classSectionStats($section)->firstWhere('student_id', $student->id);

        $this->assertSame(4, (int) $row->held);
        $this->assertSame(3, $row->attended);
        $this->assertSame(75.0, $row->percentage);
        $this->assertFalse($row->at_risk);
    }

    /** An excused absence leaves the denominator, it does not count against. */
    public function test_an_excused_absence_is_removed_from_the_total(): void
    {
        $student = $this->makeStudent();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $this->record($student, $section, [
            AttendanceStatus::Present,
            AttendanceStatus::Present,
            AttendanceStatus::Excused,
            AttendanceStatus::Absent,
        ]);

        $row = $this->reports->classSectionStats($section)->firstWhere('student_id', $student->id);

        $this->assertSame(3, $row->countable, 'Four sessions, one excused, so three count.');
        $this->assertSame(66.7, $row->percentage);
    }

    public function test_an_open_session_does_not_drag_the_percentage_down(): void
    {
        $student = $this->makeStudent();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $this->record($student, $section, [AttendanceStatus::Present, AttendanceStatus::Present]);

        AttendanceSession::factory()->open()->create(['class_section_id' => $section->id]);

        $row = $this->reports->classSectionStats($section)->firstWhere('student_id', $student->id);

        $this->assertSame(2, (int) $row->held, 'The open session must not be counted.');
        $this->assertSame(100.0, $row->percentage);
    }

    /**
     * The date filter belongs in the JOIN, not the WHERE clause. In the WHERE it
     * would discard the null side of the LEFT JOIN, so a student with nothing in
     * the range would vanish from the report rather than showing zero.
     */
    public function test_a_student_with_nothing_in_the_range_still_appears(): void
    {
        $student = $this->makeStudent();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $this->record($student, $section, [AttendanceStatus::Present]);

        $rows = $this->reports->classSectionStats(
            $section,
            now()->addYear()->toDateString(),
            now()->addYear()->addMonth()->toDateString(),
        );

        $this->assertCount(1, $rows);
        $this->assertSame(0, (int) $rows->first()->held);
        $this->assertSame(0.0, $rows->first()->percentage);
    }

    public function test_a_student_with_no_records_is_not_flagged_at_risk(): void
    {
        $student = $this->makeStudent();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        $row = $this->reports->classSectionStats($section)->firstWhere('student_id', $student->id);

        $this->assertSame(0.0, $row->percentage);
        $this->assertFalse($row->at_risk, 'Zero of zero is not a failing student.');
    }

    public function test_the_at_risk_list_holds_only_students_below_the_minimum(): void
    {
        $section = $this->makeSection();

        $failing = $this->makeStudent();
        $this->enroll($failing, $section);
        $this->record($failing, $section, [
            AttendanceStatus::Present, AttendanceStatus::Absent,
            AttendanceStatus::Absent, AttendanceStatus::Absent,
        ]);

        $passing = $this->makeStudent();
        $this->enroll($passing, $section);
        $this->record($passing, $section, [AttendanceStatus::Present, AttendanceStatus::Present]);

        $rows = $this->reports->atRisk();

        $this->assertCount(1, $rows);
        $this->assertSame($failing->id, (int) $rows->first()->student_id);
        $this->assertSame(25.0, $rows->first()->percentage);
    }

    public function test_the_at_risk_list_is_worst_first(): void
    {
        $section = $this->makeSection();

        $worse = $this->makeStudent();
        $this->enroll($worse, $section);
        $this->record($worse, $section, [AttendanceStatus::Absent, AttendanceStatus::Absent]);

        $bad = $this->makeStudent();
        $this->enroll($bad, $section);
        $this->record($bad, $section, [AttendanceStatus::Present, AttendanceStatus::Absent]);

        $rows = $this->reports->atRisk();

        $this->assertSame([0.0, 50.0], $rows->pluck('percentage')->all());
    }

    /**
     * A roster summary must be one grouped query however large the class, so
     * the report does not degrade as the university grows.
     */
    public function test_a_roster_summary_costs_one_query(): void
    {
        $section = $this->makeSection();

        foreach (range(1, 8) as $i) {
            $student = $this->makeStudent();
            $this->enroll($student, $section);
            $this->record($student, $section, [AttendanceStatus::Present]);
        }

        DB::enableQueryLog();
        $rows = $this->reports->classSectionStats($section);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(8, $rows);
        $this->assertSame(1, $queries, 'Eight students must still cost exactly one query.');
    }

    public function test_the_registry_at_risk_screen_renders_and_exports(): void
    {
        $admin = $this->makeAdmin();
        $section = $this->makeSection();
        $student = $this->makeStudent();
        $this->enroll($student, $section);
        $this->record($student, $section, [AttendanceStatus::Absent, AttendanceStatus::Absent]);

        $this->actingAs($admin)->get(route('admin.reports.at-risk'))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports.attendance'))->assertOk();

        $response = $this->actingAs($admin)->get(route('admin.reports.at-risk.export'));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString($student->student_id_no, $response->streamedContent());
    }

    public function test_a_student_sees_their_own_history_but_not_another_class(): void
    {
        $student = $this->makeStudentWithAccount();
        $section = $this->makeSection();
        $this->enroll($student, $section);
        $this->record($student, $section, [AttendanceStatus::Present]);

        $this->actingAs($student->user)->get(route('student.attendance.index'))->assertOk();
        $this->actingAs($student->user)->get(route('student.attendance.show', $section))->assertOk();

        $this->actingAs($student->user)
            ->get(route('student.attendance.show', $this->makeSection()))
            ->assertForbidden();
    }
}
