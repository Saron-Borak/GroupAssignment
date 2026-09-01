<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case OnTime = 'on_time';
    case Late = 'late';

    public function label(): string
    {
        return $this === self::OnTime ? 'On time' : 'Late';
    }

    public function badgeClass(): string
    {
        return $this === self::OnTime ? 'text-bg-success' : 'text-bg-warning';
    }
}
