<?php

namespace App\Enums;

enum ProgramLevel: string
{
    case Certificate = 'certificate';
    case Diploma = 'diploma';
    case Bachelor = 'bachelor';
    case Master = 'master';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
