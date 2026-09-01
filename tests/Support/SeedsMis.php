<?php

namespace Tests\Support;

use App\Enums\EnrollmentStatus;
use App\Models\AddressType;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;

/**
 * Builds the smallest complete slice of the MIS a test needs.
 */
trait SeedsMis
{
    protected ?Department $department = null;

    protected ?Program $program = null;

    protected function makeDepartment(): Department
    {
        return $this->department ??= Department::factory()->create(['code' => 'FCIT']);
    }

    protected function makeProgram(): Program
    {
        return $this->program ??= Program::factory()->create([
            'department_id' => $this->makeDepartment()->id,
            'code' => 'BSCS',
        ]);
    }

    protected function makeAddressType(string $code = 'PERM'): AddressType
    {
        return AddressType::firstOrCreate(['code' => $code], ['name' => 'Permanent address']);
    }

    protected function makeAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    protected function makeFaculty(): User
    {
        return User::factory()->faculty()->create();
    }

    protected function makeStudent(array $attributes = []): Student
    {
        return Student::factory()->create(array_merge([
            'program_id' => $this->makeProgram()->id,
        ], $attributes));
    }

    /** A student with a linked sign-in account. */
    protected function makeStudentWithAccount(array $attributes = []): Student
    {
        $user = User::factory()->student()->create();

        return $this->makeStudent(array_merge(['user_id' => $user->id], $attributes));
    }

    protected function makeSection(?User $lecturer = null): ClassSection
    {
        return ClassSection::factory()->create([
            'course_id' => Course::factory()->create(['department_id' => $this->makeDepartment()->id])->id,
            'lecturer_id' => ($lecturer ?? $this->makeFaculty())->id,
        ]);
    }

    protected function enroll(Student $student, ClassSection $section): Enrollment
    {
        return Enrollment::factory()->create([
            'class_section_id' => $section->id,
            'student_id' => $student->id,
            'status' => EnrollmentStatus::Enrolled,
        ]);
    }

    /**
     * A valid payload for the student profile form.
     *
     * @return array<string, mixed>
     */
    protected function studentPayload(array $overrides = []): array
    {
        return array_merge([
            'student_id_no' => 'EAMU-2026-0001',
            'first_name' => 'Sophea',
            'last_name' => 'Chea',
            'gender' => 'female',
            'date_of_birth' => '2004-05-12',
            'nationality' => 'Cambodian',
            'national_id' => '1234567890',
            'email' => 'sophea.chea@student.eamu.edu',
            'phone' => '012 345 678',
            'program_id' => $this->makeProgram()->id,
            'intake_year' => 2026,
            'admission_date' => '2026-09-01',
            'status' => 'active',
        ], $overrides);
    }
}
