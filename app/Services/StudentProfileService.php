<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Every write to a student profile passes through here.
 *
 * The profile spans three tables (students, student_addresses, guardians) plus
 * an optional sign-in account, so creating one is a transaction rather than a
 * single insert. Keeping that in one place stops a half-written profile.
 */
class StudentProfileService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $addresses
     * @param  array<int, array<string, mixed>>  $guardians
     */
    public function create(array $data, array $addresses = [], array $guardians = [], ?UploadedFile $photo = null): Student
    {
        return DB::transaction(function () use ($data, $addresses, $guardians, $photo) {
            if ($photo) {
                $data['photo_path'] = $this->storePhoto($photo);
            }

            $student = Student::create($data);

            $this->syncAddresses($student, $addresses);
            $this->syncGuardians($student, $guardians);

            return $student->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $addresses
     * @param  array<int, array<string, mixed>>  $guardians
     */
    public function update(
        Student $student,
        array $data,
        array $addresses = [],
        array $guardians = [],
        ?UploadedFile $photo = null,
        bool $removePhoto = false,
    ): Student {
        return DB::transaction(function () use ($student, $data, $addresses, $guardians, $photo, $removePhoto) {
            if ($removePhoto && $student->photo_path) {
                $this->deletePhoto($student->photo_path);
                $data['photo_path'] = null;
            }

            if ($photo) {
                // Replace rather than accumulate orphaned files.
                if ($student->photo_path) {
                    $this->deletePhoto($student->photo_path);
                }
                $data['photo_path'] = $this->storePhoto($photo);
            }

            $student->update($data);

            $this->syncAddresses($student, $addresses);
            $this->syncGuardians($student, $guardians);

            return $student->refresh();
        });
    }

    /**
     * Issue a sign-in account so the student can view their own profile.
     */
    public function issueAccount(Student $student, string $password): User
    {
        return DB::transaction(function () use ($student, $password) {
            $user = User::create([
                'name' => $student->fullName(),
                'email' => $student->email,
                'password' => Hash::make($password),
                'role' => UserRole::Student,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $student->update(['user_id' => $user->id]);

            return $user;
        });
    }

    /**
     * Soft delete, so attendance, submission and complaint history that
     * references this student is not silently orphaned.
     */
    public function archive(Student $student): void
    {
        DB::transaction(function () use ($student) {
            $student->user?->update(['is_active' => false]);
            $student->delete();
        });
    }

    public function restore(Student $student): void
    {
        DB::transaction(function () use ($student) {
            $student->restore();
            $student->user?->update(['is_active' => true]);
        });
    }

    /**
     * Next student number in the university format, e.g. EAMU-2026-0042.
     */
    public function nextStudentNumber(int $intakeYear): string
    {
        $prefix = config('mis.student_id_prefix').'-'.$intakeYear.'-';

        $last = Student::withTrashed()
            ->where('student_id_no', 'like', $prefix.'%')
            ->orderByDesc('student_id_no')
            ->value('student_id_no');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    // ------------------------------------------------------------------ parts

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAddresses(Student $student, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        // Replaced wholesale: the list is short, and this handles a row the
        // registry removed without needing to diff.
        $student->addresses()->delete();
        $student->addresses()->createMany($rows);
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncGuardians(Student $student, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $student->guardians()->delete();
        $student->guardians()->createMany($rows);
    }

    protected function storePhoto(UploadedFile $photo): string
    {
        return $photo->storeAs(
            'student-photos',
            Str::uuid()->toString().'.'.$photo->getClientOriginalExtension(),
            'public',
        );
    }

    protected function deletePhoto(string $path): void
    {
        Storage::disk('public')->delete($path);
    }
}
