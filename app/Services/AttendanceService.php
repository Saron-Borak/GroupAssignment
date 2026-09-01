<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\MarkedVia;
use App\Enums\SessionStatus;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSection;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Every write to the attendance tables passes through here.
 *
 * The module was originally a standalone mini project with its own students
 * table; it now marks against the shared profile, so a register is always a
 * list of Student records rather than a list of local user rows.
 *
 * Three ways in - the lecturer marking by hand, a student scanning the rotating
 * QR code, and a student typing the short code - all land in this class, so the
 * lateness rule and the roster check cannot drift apart between them.
 */
class AttendanceService
{
    /**
     * Persist a marked register.
     *
     * @param  array<int, string>  $marks  student id => status value
     * @return int Number of records written.
     */
    public function saveMarks(AttendanceSession $session, array $marks, ?User $by = null): int
    {
        // Only students on the roster may be marked, so a tampered form cannot
        // create a record against someone else's profile.
        $eligible = $this->rosterIds($session->classSection);

        $now = now();
        $rows = [];

        foreach ($marks as $studentId => $status) {
            $studentId = (int) $studentId;

            if (! $eligible->has($studentId)) {
                continue;
            }

            $rows[] = [
                'attendance_session_id' => $session->id,
                'student_id' => $studentId,
                'status' => AttendanceStatus::from($status)->value,
                'marked_via' => MarkedVia::Manual->value,
                'marked_at' => $now,
                'marked_by' => $by?->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) {
            return 0;
        }

        // Upsert, so re-saving a register corrects the existing rows rather
        // than failing on the unique constraint.
        DB::transaction(fn () => DB::table('attendance_records')->upsert(
            $rows,
            ['attendance_session_id', 'student_id'],
            ['status', 'marked_via', 'marked_at', 'marked_by', 'updated_at'],
        ));

        return count($rows);
    }

    /**
     * Create a class meeting, refusing a clash with an existing one.
     */
    public function createSession(ClassSection $section, array $data): AttendanceSession
    {
        return $section->sessions()->create([
            'session_date' => $data['session_date'],
            'start_time' => $this->time($data['start_time']),
            'end_time' => $this->time($data['end_time']),
            'topic' => $data['topic'] ?? null,
            'late_after_minutes' => $data['late_after_minutes'] ?? config('mis.late_after_minutes'),
            'status' => SessionStatus::from($data['status'] ?? SessionStatus::Scheduled->value),
        ]);
    }

    public function updateSession(AttendanceSession $session, array $data): AttendanceSession
    {
        $session->update([
            'session_date' => $data['session_date'],
            'start_time' => $this->time($data['start_time']),
            'end_time' => $this->time($data['end_time']),
            'topic' => $data['topic'] ?? null,
            'late_after_minutes' => $data['late_after_minutes'] ?? $session->late_after_minutes,
        ]);

        return $session->refresh();
    }

    /**
     * Does a meeting already exist for this section at this moment?
     *
     * The database enforces this too; checking first lets the interface report
     * it as a validation message rather than a constraint violation.
     */
    public function clashes(ClassSection $section, string $date, string $startTime, ?int $ignoreId = null): bool
    {
        return $section->sessions()
            ->whereDate('session_date', $date)
            ->where('start_time', $this->time($startTime))
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    // ------------------------------------------------------------- lifecycle

    /**
     * Open a session for self check-in and mint its first QR token.
     */
    public function openSession(AttendanceSession $session): AttendanceSession
    {
        $session->update([
            'status' => SessionStatus::Open,
            'opened_at' => $session->opened_at ?? now(),
            'closed_at' => null,
        ]);

        return $this->rotateQr($session);
    }

    /**
     * Mint a fresh token and short code, invalidating the previous pair.
     *
     * Rotation is the point: a photograph of the projector taken at the start of
     * the hour is useless a minute later, so a student cannot check a friend in
     * from outside the room.
     */
    public function rotateQr(AttendanceSession $session): AttendanceSession
    {
        $session->update([
            'qr_token' => Str::random(64),
            'qr_expires_at' => now()->addSeconds((int) config('mis.qr_ttl_seconds')),
            'checkin_code' => $this->shortCode(),
        ]);

        return $session->refresh();
    }

    /**
     * Close a session and mark every student who never checked in as absent.
     *
     * Without this a session left open reads as though nobody was absent, which
     * would quietly inflate every percentage drawn from it.
     *
     * @return int Number of absences recorded.
     */
    public function closeSession(AttendanceSession $session, ?User $by = null): int
    {
        $marked = $session->records()->pluck('student_id')->flip();
        $now = now();

        $rows = $this->rosterIds($session->classSection)
            ->keys()
            ->reject(fn ($id) => $marked->has($id))
            ->map(fn ($id) => [
                'attendance_session_id' => $session->id,
                'student_id' => $id,
                'status' => AttendanceStatus::Absent->value,
                'marked_via' => MarkedVia::Manual->value,
                'marked_at' => $now,
                'marked_by' => $by?->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        DB::transaction(function () use ($session, $rows, $now) {
            if ($rows !== []) {
                DB::table('attendance_records')->insert($rows);
            }

            $session->update([
                'status' => SessionStatus::Closed,
                'closed_at' => $now,
                // A closed session must not keep accepting scans.
                'qr_token' => null,
                'qr_expires_at' => null,
                'checkin_code' => null,
            ]);
        });

        return count($rows);
    }

    // ---------------------------------------------------------- self check-in

    public function resolveByToken(string $token): ?AttendanceSession
    {
        return AttendanceSession::where('qr_token', $token)
            ->where('qr_expires_at', '>', now())
            ->where('status', SessionStatus::Open)
            ->first();
    }

    public function resolveByCode(string $code): ?AttendanceSession
    {
        return AttendanceSession::where('checkin_code', Str::upper(trim($code)))
            ->where('qr_expires_at', '>', now())
            ->where('status', SessionStatus::Open)
            ->first();
    }

    /**
     * Record a student's own check-in.
     *
     * @return array{ok: bool, message: string, record: ?AttendanceRecord}
     */
    public function checkIn(AttendanceSession $session, Student $student, MarkedVia $via): array
    {
        if (! $session->status->acceptsCheckIn()) {
            return $this->refuse('That session is not open for check-in.');
        }

        if (! $this->rosterIds($session->classSection)->has($student->id)) {
            return $this->refuse('You are not enrolled in that class.');
        }

        if ($session->records()->where('student_id', $student->id)->exists()) {
            return $this->refuse('You have already been marked for this session.');
        }

        $status = $this->lateOrPresent($session);

        /** @var AttendanceRecord $record */
        $record = $session->records()->create([
            'student_id' => $student->id,
            'status' => $status,
            'marked_via' => $via,
            'marked_at' => now(),
        ]);

        return [
            'ok' => true,
            'message' => $status === AttendanceStatus::Late
                ? 'Checked in, but recorded as late.'
                : 'Checked in. You are marked present.',
            'record' => $record,
        ];
    }

    /**
     * Present or late, decided by the server clock against the session start.
     *
     * The browser plays no part: a device with its clock wound back must not be
     * able to turn a late arrival into a punctual one.
     */
    protected function lateOrPresent(AttendanceSession $session): AttendanceStatus
    {
        $starts = Carbon::parse(
            $session->session_date->toDateString().' '.$session->start_time,
            config('app.timezone'),
        );

        return now()->greaterThan($starts->addMinutes($session->late_after_minutes))
            ? AttendanceStatus::Late
            : AttendanceStatus::Present;
    }

    // ------------------------------------------------------------- timetable

    /**
     * Create sessions from the section's weekly timetable across a date range.
     *
     * Existing meetings are skipped rather than replaced, so running this twice
     * over an overlapping range is safe.
     *
     * @return int Number of sessions created.
     */
    public function generateSessions(ClassSection $section, string $from, string $to): int
    {
        $schedules = $section->schedules()->get();

        if ($schedules->isEmpty()) {
            return 0;
        }

        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();

        if ($end->lessThan($start)) {
            return 0;
        }

        $existing = $section->sessions()
            ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->map(fn (AttendanceSession $s) => $s->session_date->toDateString().' '.$s->start_time)
            ->flip();

        $now = now();
        $rows = [];

        for ($day = $start->copy(); $day->lessThanOrEqualTo($end); $day->addDay()) {
            foreach ($schedules->where('day_of_week', $day->dayOfWeekIso) as $slot) {
                $key = $day->toDateString().' '.$slot->start_time;

                if ($existing->has($key)) {
                    continue;
                }

                $existing->put($key, true);

                $rows[] = [
                    'class_section_id' => $section->id,
                    'session_date' => $day->toDateString(),
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'topic' => null,
                    'late_after_minutes' => config('mis.late_after_minutes'),
                    'status' => SessionStatus::Scheduled->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('attendance_sessions')->insert($chunk);
        }

        return count($rows);
    }

    // ----------------------------------------------------------------- shared

    /**
     * Student ids on the active roster, keyed for O(1) membership tests.
     *
     * @return Collection<int, int>
     */
    protected function rosterIds(ClassSection $section): Collection
    {
        return $section->enrollments()
            ->where('status', EnrollmentStatus::Enrolled)
            ->pluck('student_id')
            ->flip();
    }

    /**
     * A six-character code with no ambiguous glyphs, so it can be read aloud
     * or copied off a projector without an O being typed as a zero.
     */
    protected function shortCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($i = 0; $i < 6; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }

    /** @return array{ok: false, message: string, record: null} */
    protected function refuse(string $message): array
    {
        return ['ok' => false, 'message' => $message, 'record' => null];
    }

    protected function time(string $value): string
    {
        return strlen($value) === 5 ? $value.':00' : $value;
    }
}
