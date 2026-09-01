<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The central entity of the MIS. Every other module in this system refers
     * to a student through this table rather than keeping its own copy of the
     * student's details.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            // A profile can exist before a sign-in account is issued, so the
            // link to users is optional but must stay one-to-one.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();

            $table->string('student_id_no', 30)->unique();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('gender', 20)->default('undisclosed');
            $table->date('date_of_birth');
            $table->string('nationality', 60)->nullable();
            $table->string('national_id', 40)->nullable()->unique();

            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->string('photo_path')->nullable();

            $table->unsignedSmallInteger('intake_year');
            $table->date('admission_date');
            $table->string('status', 20)->default('active')->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
