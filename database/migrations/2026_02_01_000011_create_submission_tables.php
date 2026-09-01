<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Integrated from the Student Project Submission mini project. Assignments
     * now hang off a class section rather than a separate subject table.
     */
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_section_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('deadline');
            $table->timestamps();
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->dateTime('submitted_at');
            $table->string('status', 20)->default('on_time')->index();
            $table->timestamps();

            // Resubmitting updates the row rather than adding a second one.
            $table->unique(['assignment_id', 'student_id'], 'submissions_assignment_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('assignments');
    }
};
