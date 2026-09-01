<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsMis;
use Tests\TestCase;

/**
 * CRUD and validation for the Student Profile module, the core of the MIS.
 */
class StudentProfileTest extends TestCase
{
    use RefreshDatabase, SeedsMis;

    public function test_an_administrator_creates_a_profile(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.students.store'), $this->studentPayload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('students', [
            'student_id_no' => 'EAMU-2026-0001',
            'first_name' => 'Sophea',
            'email' => 'sophea.chea@student.eamu.edu',
        ]);
    }

    public function test_a_profile_is_created_with_its_address_and_guardian(): void
    {
        $type = $this->makeAddressType();

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.students.store'), $this->studentPayload([
                'addresses' => [[
                    'address_type_id' => $type->id,
                    'line1' => 'House 12, Street 240',
                    'city' => 'Phnom Penh',
                    'country' => 'Cambodia',
                ]],
                'guardians' => [[
                    'full_name' => 'Sokha Chea',
                    'relationship' => 'mother',
                    'phone' => '012 111 222',
                ]],
            ]))
            ->assertSessionHasNoErrors();

        $student = Student::firstWhere('student_id_no', 'EAMU-2026-0001');

        $this->assertDatabaseHas('student_addresses', [
            'student_id' => $student->id,
            'city' => 'Phnom Penh',
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('guardians', [
            'student_id' => $student->id,
            'full_name' => 'Sokha Chea',
            'is_emergency_contact' => true,
        ]);
    }

    public function test_blank_address_and_guardian_rows_are_discarded(): void
    {
        $type = $this->makeAddressType();

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.students.store'), $this->studentPayload([
                'addresses' => [
                    ['address_type_id' => $type->id, 'line1' => 'House 12', 'city' => 'Phnom Penh'],
                    ['address_type_id' => '', 'line1' => '', 'city' => ''],
                ],
                'guardians' => [
                    ['full_name' => 'Sokha Chea', 'relationship' => 'mother', 'phone' => '012 111 222'],
                    ['full_name' => '', 'relationship' => 'guardian', 'phone' => ''],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, StudentAddress::count());
        $this->assertSame(1, Guardian::count());
    }

    public function test_a_duplicate_student_number_is_rejected(): void
    {
        $this->makeStudent(['student_id_no' => 'EAMU-2026-0001']);

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.students.store'), $this->studentPayload())
            ->assertSessionHasErrors('student_id_no');
    }

    public function test_a_duplicate_email_is_rejected(): void
    {
        $this->makeStudent(['email' => 'sophea.chea@student.eamu.edu']);

        $this->actingAs($this->makeAdmin())
            ->post(route('admin.students.store'), $this->studentPayload())
            ->assertSessionHasErrors('email');
    }

    public function test_an_implausible_date_of_birth_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.students.store'), $this->studentPayload([
                'date_of_birth' => now()->subYears(3)->toDateString(),
            ]))
            ->assertSessionHasErrors('date_of_birth');
    }

    public function test_a_malformed_phone_number_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.students.store'), $this->studentPayload([
                'phone' => 'call me maybe',
            ]))
            ->assertSessionHasErrors('phone');
    }

    public function test_the_student_number_and_email_are_normalised(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('admin.students.store'), $this->studentPayload([
                'student_id_no' => 'eamu-2026-0009',
                'email' => 'UPPER.Case@Student.EAMU.edu',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('students', [
            'student_id_no' => 'EAMU-2026-0009',
            'email' => 'upper.case@student.eamu.edu',
        ]);
    }

    public function test_a_profile_is_updated(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.students.update', $student), $this->studentPayload([
                'student_id_no' => $student->student_id_no,
                'email' => $student->email,
                'first_name' => 'Renamed',
            ]))
            ->assertRedirect(route('admin.students.show', $student));

        $this->assertSame('Renamed', $student->refresh()->first_name);
    }

    public function test_re_saving_an_unchanged_profile_passes_the_unique_rules(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.students.update', $student), $this->studentPayload([
                'student_id_no' => $student->student_id_no,
                'email' => $student->email,
                'national_id' => $student->national_id,
            ]))
            ->assertSessionHasNoErrors();
    }

    /**
     * Archiving rather than deleting keeps the attendance, submission and
     * complaint history that references this student intact.
     */
    public function test_archiving_soft_deletes_the_profile(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->makeAdmin())
            ->delete(route('admin.students.destroy', $student))
            ->assertRedirect(route('admin.students.index'));

        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }

    public function test_an_archived_profile_can_be_restored(): void
    {
        $student = $this->makeStudent();
        $student->delete();

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.students.restore', $student->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('students', ['id' => $student->id, 'deleted_at' => null]);
    }
}
