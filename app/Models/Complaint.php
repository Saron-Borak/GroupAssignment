<?php

namespace App\Models;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id', 'reference', 'category', 'title', 'description',
    'status', 'admin_response', 'handled_by', 'resolved_at',
])]
class Complaint extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'category' => ComplaintCategory::class,
            'status' => ComplaintStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
