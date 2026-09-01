<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\Gender;
use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The central entity of the MIS.
 *
 * Attendance, project submission and complaint handling all resolve a student
 * through this record, so the profile is held once and read everywhere.
 */
#[Fillable([
    'user_id', 'program_id', 'student_id_no', 'first_name', 'last_name',
    'gender', 'date_of_birth', 'nationality', 'national_id',
    'email', 'phone', 'photo_path',
    'intake_year', 'admission_date', 'status',
])]
class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'status' => StudentStatus::class,
            'date_of_birth' => 'date',
            'admission_date' => 'date',
            'intake_year' => 'integer',
        ];
    }

    // ---------------------------------------------------------------- profile

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(StudentAddress::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    // ------------------------------------------------------ integrated modules

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    // ------------------------------------------------------------- attributes

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }

    public function primaryAddress(): ?StudentAddress
    {
        return $this->addresses->firstWhere('is_primary', true) ?? $this->addresses->first();
    }

    public function emergencyContact(): ?Guardian
    {
        return $this->guardians->firstWhere('is_emergency_contact', true) ?? $this->guardians->first();
    }

    /**
     * Which parts of the profile have been filled in. Drives the completeness
     * indicator the registry uses to chase missing records.
     *
     * @return array<string, bool>
     */
    public function completeness(): array
    {
        return [
            'personal' => filled($this->first_name) && filled($this->last_name) && $this->date_of_birth !== null,
            'contact' => filled($this->email) && filled($this->phone),
            'address' => $this->addresses->isNotEmpty(),
            'guardian' => $this->guardians->isNotEmpty(),
            'photo' => filled($this->photo_path),
        ];
    }

    public function completenessPercentage(): int
    {
        $checks = $this->completeness();

        return $checks === [] ? 0 : (int) round(count(array_filter($checks)) / count($checks) * 100);
    }

    // ----------------------------------------------------------------- scopes

    /** @param  Builder<Student>  $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when($term, fn (Builder $q) => $q->where(function (Builder $inner) use ($term) {
            $inner->where('student_id_no', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        }));
    }

    /** @param  Builder<Student>  $query */
    public function scopeActiveEnrollments(Builder $query): void
    {
        $query->whereHas('enrollments', fn (Builder $q) => $q->where('status', EnrollmentStatus::Enrolled));
    }
}
