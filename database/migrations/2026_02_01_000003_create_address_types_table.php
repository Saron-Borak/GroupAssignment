<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A lookup table rather than an enum column: the registry can add an
     * address type without a schema change, and it keeps the addresses table
     * in third normal form.
     */
    public function up(): void
    {
        Schema::create('address_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address_types');
    }
};
