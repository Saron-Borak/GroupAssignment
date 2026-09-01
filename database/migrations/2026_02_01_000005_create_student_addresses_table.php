<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A student may hold several addresses (permanent, current, mailing), so
     * address is a separate repeating group rather than columns on students.
     */
    public function up(): void
    {
        Schema::create('student_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('address_type_id')->constrained()->restrictOnDelete();

            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city', 80);
            $table->string('province', 80)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 80)->default('Cambodia');
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            // One address of each type per student.
            $table->unique(['student_id', 'address_type_id'], 'addresses_student_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_addresses');
    }
};
