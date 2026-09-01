<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\AttendanceSession;
use App\Models\ClassSection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AttendanceSession> */
class AttendanceSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'class_section_id' => ClassSection::factory(),
            'session_date' => now()->subDays(fake()->numberBetween(1, 40))->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'topic' => ucfirst(fake()->words(3, true)),
            'late_after_minutes' => 15,
            'status' => SessionStatus::Closed,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'session_date' => now()->addDay()->toDateString(),
            'status' => SessionStatus::Scheduled,
        ]);
    }

    /** Open right now, with a live token and short code. */
    public function open(): static
    {
        return $this->state(fn () => [
            'session_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:00'),
            'end_time' => now()->addHours(2)->format('H:i:00'),
            'status' => SessionStatus::Open,
            'qr_token' => Str::random(64),
            'qr_expires_at' => now()->addMinute(),
            'checkin_code' => Str::upper(Str::random(6)),
            'opened_at' => now(),
        ]);
    }

    /** Open, but started long enough ago that arriving now counts as late. */
    public function openAndLate(): static
    {
        return $this->open()->state(fn () => [
            'start_time' => now()->subHour()->format('H:i:00'),
        ]);
    }

    /** Open, but the token has already expired. */
    public function openWithExpiredToken(): static
    {
        return $this->open()->state(fn () => [
            'qr_expires_at' => now()->subMinute(),
        ]);
    }
}
