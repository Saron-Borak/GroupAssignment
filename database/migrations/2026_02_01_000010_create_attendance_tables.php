<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Integrated from the Attendance Management mini project. The records now
     * reference students.id rather than users.id, so attendance reads the same
     * profile as every other module.
     */
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_section_id')->constrained()->cascadeOnDelete();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('topic')->nullable();
            $table->string('status', 20)->default('closed')->index();
            $table->timestamps();

            $table->unique(['class_section_id', 'session_date', 'start_time'], 'sessions_section_date_time_unique');
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->index();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();

            // A student has exactly one outcome per session.
            $table->unique(['attendance_session_id', 'student_id'], 'records_session_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_sessions');
    }
};
