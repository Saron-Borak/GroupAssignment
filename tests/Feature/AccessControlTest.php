<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsMis;
use Tests\TestCase;

/**
 * Two layers: role middleware gates the portal, a policy proves ownership of
 * the individual record.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase, SeedsMis;

    public function test_the_login_screen_is_reachable(): void
    {
        $this->get('/login')->assertOk()->assertSee('East Asia Management University');
    }

    public function test_each_role_lands_on_its_own_portal(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'registry@eamu.edu']);
        $this->post('/login', ['email' => 'registry@eamu.edu', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->post('/logout');

        $faculty = User::factory()->faculty()->create(['email' => 'teach@eamu.edu']);
        $this->post('/login', ['email' => 'teach@eamu.edu', 'password' => 'password'])
            ->assertRedirect(route('faculty.dashboard'));
        $this->assertAuthenticatedAs($faculty);
        $this->post('/logout');

        $student = $this->makeStudentWithAccount();
        $this->post('/login', ['email' => $student->user->email, 'password' => 'password'])
            ->assertRedirect(route('student.profile'));
    }

    public function test_a_deactivated_account_cannot_sign_in(): void
    {
        User::factory()->admin()->inactive()->create(['email' => 'former@eamu.edu']);

        $this->post('/login', ['email' => 'former@eamu.edu', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_account_deactivated_mid_session_is_signed_out(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $admin->update(['is_active' => false]);

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.students.index'))->assertRedirect(route('login'));
    }

    public function test_a_student_cannot_reach_the_registry_area(): void
    {
        $this->actingAs($this->makeStudentWithAccount()->user)
            ->get(route('admin.students.index'))
            ->assertForbidden();
    }

    public function test_a_faculty_member_cannot_reach_the_registry_area(): void
    {
        $this->actingAs($this->makeFaculty())
            ->get(route('admin.students.index'))
            ->assertForbidden();
    }

    public function test_an_administrator_cannot_reach_the_student_portal(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('student.profile'))
            ->assertForbidden();
    }

    /**
     * Middleware alone would let a faculty member open any profile by guessing
     * an id, so the policy checks enrolment in one of their own sections.
     */
    public function test_a_faculty_member_cannot_open_a_profile_from_another_section(): void
    {
        $lecturer = $this->makeFaculty();
        $outsider = $this->makeStudent();

        $this->actingAs($lecturer)
            ->get(route('faculty.students.show', $outsider))
            ->assertForbidden();
    }

    public function test_a_faculty_member_can_open_a_profile_from_their_own_section(): void
    {
        $lecturer = $this->makeFaculty();
        $section = $this->makeSection($lecturer);
        $student = $this->makeStudent();
        $this->enroll($student, $section);

        $this->actingAs($lecturer)
            ->get(route('faculty.students.show', $student))
            ->assertOk()
            ->assertSee($student->fullName());
    }

    public function test_a_student_sees_only_their_own_profile(): void
    {
        $mine = $this->makeStudentWithAccount(['first_name' => 'Mine']);
        $other = $this->makeStudent(['first_name' => 'Someone', 'last_name' => 'Else']);

        $this->actingAs($mine->user)
            ->get(route('student.profile'))
            ->assertOk()
            ->assertSee($mine->fullName())
            ->assertDontSee($other->fullName());
    }

    public function test_an_administrator_cannot_deactivate_their_own_account(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->put(route('admin.users.toggle', $admin))
            ->assertSessionHas('error');

        $this->assertTrue($admin->refresh()->is_active);
    }
}
