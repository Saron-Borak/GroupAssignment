<?php

namespace App\Enums;

enum ComplaintCategory: string
{
    case Academic = 'academic';
    case Facility = 'facility';
    case Administrative = 'administrative';
    case Other = 'other';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
