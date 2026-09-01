<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Scheduled = 'scheduled';
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Scheduled => 'text-bg-secondary',
            self::Open => 'text-bg-success',
            self::Closed => 'text-bg-dark',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Scheduled => 'bi-calendar-event',
            self::Open => 'bi-broadcast',
            self::Closed => 'bi-lock',
        };
    }

    /** Only an open session accepts a self check-in. */
    public function acceptsCheckIn(): bool
    {
        return $this === self::Open;
    }
}
