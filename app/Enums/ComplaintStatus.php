<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Resolved => 'Resolved',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'text-bg-danger',
            self::InProgress => 'text-bg-warning',
            self::Resolved => 'text-bg-success',
        };
    }

    /** A case the registry still owes the student an answer on. */
    public function isOpen(): bool
    {
        return $this !== self::Resolved;
    }

    /**
     * The open statuses as raw values, for `whereIn` on the complaints table.
     *
     * @return list<string>
     */
    public static function openValues(): array
    {
        return array_values(array_map(
            fn (self $case) => $case->value,
            array_filter(self::cases(), fn (self $case) => $case->isOpen()),
        ));
    }
}
