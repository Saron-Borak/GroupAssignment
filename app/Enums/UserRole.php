<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Faculty = 'faculty';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Registry Administrator',
            self::Faculty => 'Faculty Member',
            self::Student => 'Student',
        };
    }

    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Faculty => 'faculty.dashboard',
            self::Student => 'student.profile',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Admin => 'bi-shield-lock',
            self::Faculty => 'bi-person-video3',
            self::Student => 'bi-mortarboard',
        };
    }
}
