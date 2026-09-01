<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id', 'address_type_id', 'line1', 'line2',
    'city', 'province', 'postal_code', 'country', 'is_primary',
])]
class StudentAddress extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function addressType(): BelongsTo
    {
        return $this->belongsTo(AddressType::class);
    }

    /** Single-line rendering for tables and reports. */
    public function oneLine(): string
    {
        return collect([$this->line1, $this->line2, $this->city, $this->province, $this->postal_code, $this->country])
            ->filter()
            ->implode(', ');
    }
}
