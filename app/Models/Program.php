<?php

namespace App\Models;

use App\Enums\ProgramLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['department_id', 'code', 'name', 'level', 'duration_years'])]
class Program extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'level' => ProgramLevel::class,
            'duration_years' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function label(): string
    {
        return "{$this->code} - {$this->name}";
    }
}
