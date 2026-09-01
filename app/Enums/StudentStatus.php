<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Graduated = 'graduated';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::OnLeave => 'On Leave',
            self::Graduated => 'Graduated',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'text-bg-success',
            self::OnLeave => 'text-bg-warning',
            self::Graduated => 'text-bg-primary',
            self::Withdrawn => 'text-bg-secondary',
        };
    }
}
