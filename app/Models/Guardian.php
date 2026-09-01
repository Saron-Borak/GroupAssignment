<?php

namespace App\Models;

use App\Enums\GuardianRelationship;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id', 'full_name', 'relationship', 'phone',
    'email', 'occupation', 'is_emergency_contact',
])]
class Guardian extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'relationship' => GuardianRelationship::class,
            'is_emergency_contact' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
