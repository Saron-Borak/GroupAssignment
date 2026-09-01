<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parent or guardian contacts. A student may list more than one, so this
     * is a separate table rather than parent_name / parent_phone columns.
     */
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            $table->string('full_name', 160);
            $table->string('relationship', 20)->default('guardian');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('occupation', 120)->nullable();
            $table->boolean('is_emergency_contact')->default(false);

            $table->timestamps();
            $table->index(['student_id', 'is_emergency_contact']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
