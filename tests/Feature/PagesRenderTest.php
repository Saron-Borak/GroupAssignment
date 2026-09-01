<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AttendanceSession;
use App\Models\Complaint;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentAddress;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsMis;
use Tests\TestCase;

/**
 * Walks every screen with realistic data. Runs with lazy loading disabled, so a
 * missing eager-load fails here rather than costing queries in production.
 */
class PagesRenderTest extends TestCase
{
    use RefreshDatabase, SeedsMis;

    protected function fullyPopulatedStudent(): Student
    {
        $student = $this->makeStudentWithAccount();
        $section = $this->makeSection();
        $this->enroll($student, $section);

        StudentAddress::factory()->create([
            'student_id' => $student->id,
            'address_type_id' => $this->makeAddressType()->id,
        ]);
        Guardian::factory()->create(['student_id' => $student->id]);

        $assignment = Assignment::factory()->create(['class_section_id' => $section->id]);
        Submission::factory()->create(['assignment_id' => $assignment->id, 'student_id' => $student->id]);
        Complaint::factory()->create(['student_id' => $student->id]);

        return $student;
    }

    public function test_every_registry_screen_renders(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->fullyPopulatedStudent();

        $routes = [
            route('admin.dashboard'),
            route('admin.students.index'),
            route('admin.students.create'),
            route('admin.students.show', $student),
            route('admin.students.edit', $student),
            route('admin.departments.index'),
            route('admin.programs.index'),
            route('admin.programs.create'),
            route('admin.programs.edit', $this->makeProgram()),
            route('admin.users.index'),
            route('admin.reports.by-program'),
            route('admin.reports.completeness'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($admin)->get($url)->assertOk("Failed rendering {$url}");
        }
    }

    public function test_the_profile_page_shows_all_three_modules(): void
    {
        $student = $this->fullyPopulatedStudent();

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSee('Attendance module')
            ->assertSee('Submission module')
            ->assertSee('Complaint module');
    }

    public function test_the_student_portal_renders(): void
    {
        $student = $this->fullyPopulatedStudent();

        $this->actingAs($student->user)
            ->get(route('student.profile'))
            ->assertOk()
            ->assertSee($student->student_id_no);
    }

    public function test_the_faculty_portal_renders(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);
        $student = $this->makeStudent();
        $this->enroll($student, $section);

        $session = AttendanceSession::factory()->create(['class_section_id' => $section->id]);
        $open = AttendanceSession::factory()->open()->create(['class_section_id' => $section->id]);
        $assignment = Assignment::factory()->create(['class_section_id' => $section->id]);

        foreach ([
            route('faculty.dashboard'),
            route('faculty.students.show', $student),
            route('faculty.sections.index'),
            route('faculty.sections.show', $section),
            route('faculty.attendance.index'),
            route('faculty.attendance.create'),
            route('faculty.attendance.mark', $session),
            route('faculty.attendance.edit', $session),
            route('faculty.attendance.report', $section),
            route('faculty.attendance.qr', $open),
            route('faculty.attendance.qr.refresh', $open),
            route('faculty.assignments.index'),
            route('faculty.assignments.create'),
            route('faculty.assignments.show', $assignment),
            route('faculty.assignments.edit', $assignment),
        ] as $url) {
            $this->actingAs($lecturer)->get($url)->assertOk("Failed rendering {$url}");
        }
    }

    /**
     * The registry screens that manage the shared catalogue and roster, which
     * both the attendance and the submission module read from.
     */
    public function test_the_registry_catalogue_screens_render(): void
    {
        $admin = $this->makeAdmin();
        $section = $this->makeSection();
        $this->enroll($this->makeStudent(), $section);

        foreach ([
            route('admin.courses.index'),
            route('admin.courses.create'),
            route('admin.courses.edit', $section->course),
            route('admin.sections.index'),
            route('admin.sections.create'),
            route('admin.sections.show', $section),
            route('admin.sections.edit', $section),
            route('admin.reports.attendance'),
            route('admin.reports.at-risk'),
            route('admin.complaints.report'),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk("Failed rendering {$url}");
        }
    }

    public function test_the_module_screens_render_for_students_and_the_registry(): void
    {
        $student = $this->fullyPopulatedStudent();
        $complaint = Complaint::where('student_id', $student->id)->first();

        foreach ([
            route('student.assignments.index'),
            route('student.complaints.index'),
            route('student.complaints.show', $complaint),
        ] as $url) {
            $this->actingAs($student->user)->get($url)->assertOk("Failed rendering {$url}");
        }

        $admin = $this->makeAdmin();
        foreach ([
            route('admin.complaints.index'),
            route('admin.complaints.show', $complaint),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk("Failed rendering {$url}");
        }
    }

    /** Every student-facing module screen, on a student with real data. */
    public function test_the_student_module_screens_render(): void
    {
        $student = $this->fullyPopulatedStudent();
        $section = $student->enrollments()->first()->classSection;
        $assignment = Assignment::where('class_section_id', $section->id)->first()
            ?? Assignment::factory()->create(['class_section_id' => $section->id]);

        foreach ([
            route('student.sections.index'),
            route('student.sections.browse'),
            route('student.attendance.index'),
            route('student.attendance.show', $section),
            route('student.checkin.form'),
            route('student.assignments.index'),
            route('student.assignments.show', $assignment),
        ] as $url) {
            $this->actingAs($student->user)->get($url)->assertOk("Failed rendering {$url}");
        }
    }

    public function test_the_root_url_sends_each_role_to_its_own_portal(): void
    {
        $this->actingAs($this->makeAdmin())->get('/')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($this->makeFaculty())->get('/')->assertRedirect(route('faculty.dashboard'));
        $this->actingAs($this->makeStudentWithAccount()->user)->get('/')->assertRedirect(route('student.profile'));
    }

    public function test_the_search_filter_narrows_the_student_list(): void
    {
        $this->makeStudent(['first_name' => 'Findable', 'last_name' => 'Person', 'student_id_no' => 'EAMU-2026-7777']);
        $this->makeStudent(['first_name' => 'Hidden', 'last_name' => 'Other', 'student_id_no' => 'EAMU-2026-8888']);

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.students.index', ['q' => 'Findable']))
            ->assertOk()
            ->assertSee('EAMU-2026-7777')
            ->assertDontSee('EAMU-2026-8888');
    }

    public function test_the_by_program_report_exports_as_csv(): void
    {
        $this->fullyPopulatedStudent();

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.reports.by-program.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        ob_start();
        $response->sendContent();
        $body = ob_get_clean();

        // Excel needs the byte-order mark to read UTF-8 names correctly.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString('Attendance %', $body);
    }
}
