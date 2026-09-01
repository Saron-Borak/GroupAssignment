<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case Absent = 'absent';
    case Excused = 'excused';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Late still means the student was in the room. */
    public function countsAsAttended(): bool
    {
        return in_array($this, [self::Present, self::Late], true);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Present => 'text-bg-success',
            self::Late => 'text-bg-warning',
            self::Absent => 'text-bg-danger',
            self::Excused => 'text-bg-secondary',
        };
    }
}
