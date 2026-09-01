<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['class_section_id', 'title', 'description', 'deadline'])]
class Assignment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['deadline' => 'datetime'];
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * One student's submission for this assignment, attached by
     * SubmissionService::assignmentsFor() so the student view needs no
     * per-assignment lookup.
     */
    public function studentSubmission(): HasOne
    {
        return $this->hasOne(Submission::class);
    }

    public function isOverdue(): bool
    {
        return $this->deadline->isPast();
    }
}
