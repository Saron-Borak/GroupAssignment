<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\MarkedVia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attendance_session_id', 'student_id', 'status', 'marked_via', 'marked_at', 'marked_by', 'remarks'])]
class AttendanceRecord extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
            'marked_via' => MarkedVia::class,
            'marked_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** The staff account that marked this row; null for a self check-in. */
    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
