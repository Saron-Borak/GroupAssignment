<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lecturer_id')->constrained('users')->restrictOnDelete();
            $table->string('term', 30);
            $table->string('section_code', 10);
            $table->string('room', 50)->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'term', 'section_code'], 'sections_course_term_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sections');
    }
};
