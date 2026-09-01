<?php

namespace App\Enums;

enum Gender: string
{
    case Female = 'female';
    case Male = 'male';
    case Other = 'other';
    case Undisclosed = 'undisclosed';

    public function label(): string
    {
        return match ($this) {
            self::Female => 'Female',
            self::Male => 'Male',
            self::Other => 'Other',
            self::Undisclosed => 'Prefer not to say',
        };
    }
}
