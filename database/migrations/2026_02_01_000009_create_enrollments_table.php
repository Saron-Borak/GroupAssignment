<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One roster serves both integrated modules: attendance is taken against
     * it, and assignments are issued to it.
     */
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('enrolled')->index();
            $table->date('enrolled_at');
            $table->timestamps();

            $table->unique(['class_section_id', 'student_id'], 'enrollments_section_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
