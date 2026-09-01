<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    /** The registry has blanket access; other roles fall through. */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * A student may read only their own profile. A faculty member may read the
     * profile of anyone enrolled in a section they teach - route middleware
     * alone would let them open any profile by guessing an id.
     */
    public function view(User $user, Student $student): bool
    {
        if ($user->isStudent()) {
            return $user->student?->id === $student->id;
        }

        if ($user->isFaculty()) {
            return $student->enrollments()
                ->whereHas('classSection', fn ($q) => $q->where('lecturer_id', $user->id))
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return false;   // registry only, granted by before()
    }

    public function update(User $user, Student $student): bool
    {
        return false;
    }

    public function delete(User $user, Student $student): bool
    {
        return false;
    }
}
