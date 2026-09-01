<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name'])]
class Department extends Model
{
    use HasFactory;

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
