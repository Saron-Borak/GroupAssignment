<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['class_section_id', 'session_date', 'start_time', 'end_time', 'topic', 'status',
    'late_after_minutes', 'qr_token', 'qr_expires_at', 'checkin_code', 'opened_at', 'closed_at'])]
class AttendanceSession extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'status' => SessionStatus::class,
            'qr_expires_at' => 'datetime',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function timeRange(): string
    {
        return substr((string) $this->start_time, 0, 5).' - '.substr((string) $this->end_time, 0, 5);
    }

    /** Seconds left on the current token, floored at zero. */
    public function qrSecondsLeft(): int
    {
        return max(0, (int) now()->diffInSeconds($this->qr_expires_at, false));
    }

    public function scopeOpen($query)
    {
        return $query->where('status', SessionStatus::Open);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', SessionStatus::Closed);
    }
}
