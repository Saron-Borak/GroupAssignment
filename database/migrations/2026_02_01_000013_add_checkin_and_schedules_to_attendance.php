<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The rest of the Attendance Management mini project: the session lifecycle,
     * QR self check-in, and the weekly timetable that sessions are generated from.
     *
     * The QR token rotates, so a screenshot shared outside the room stops working;
     * the short code is the fallback for a student without a working camera.
     */
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('late_after_minutes')->default(15)->after('topic');
            $table->string('qr_token', 64)->nullable()->unique()->after('status');
            $table->timestamp('qr_expires_at')->nullable()->after('qr_token');
            $table->string('checkin_code', 8)->nullable()->after('qr_expires_at');
            $table->timestamp('opened_at')->nullable()->after('checkin_code');
            $table->timestamp('closed_at')->nullable()->after('opened_at');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('marked_via', 10)->default('manual')->after('status');
            $table->foreignId('marked_by')->nullable()->after('marked_at')
                ->constrained('users')->nullOnDelete();
            $table->string('remarks')->nullable()->after('marked_by');
        });

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_section_id')->constrained()->cascadeOnDelete();
            // ISO-8601: 1 = Monday through 7 = Sunday.
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 50)->nullable();
            $table->timestamps();

            $table->unique(['class_section_id', 'day_of_week', 'start_time'], 'schedules_section_day_time_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('marked_by');
            $table->dropColumn(['marked_via', 'remarks']);
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'late_after_minutes', 'qr_token', 'qr_expires_at',
                'checkin_code', 'opened_at', 'closed_at',
            ]);
        });
    }
};
