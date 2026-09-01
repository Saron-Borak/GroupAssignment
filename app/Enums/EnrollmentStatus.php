<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Enrolled = 'enrolled';
    case Dropped = 'dropped';
    case Completed = 'completed';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
