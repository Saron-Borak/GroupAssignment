<?php

namespace App\Enums;

enum MarkedVia: string
{
    case Manual = 'manual';
    case Qr = 'qr';
    case Code = 'code';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Marked by lecturer',
            self::Qr => 'QR scan',
            self::Code => 'Typed code',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Manual => 'bi-pencil-square',
            self::Qr => 'bi-qr-code-scan',
            self::Code => 'bi-keyboard',
        };
    }

    /** True when the student recorded their own presence. */
    public function isSelfService(): bool
    {
        return $this !== self::Manual;
    }
}
