<?php

namespace App\Enums;

enum GuardianRelationship: string
{
    case Mother = 'mother';
    case Father = 'father';
    case Guardian = 'guardian';
    case Sibling = 'sibling';
    case Spouse = 'spouse';
    case Other = 'other';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
