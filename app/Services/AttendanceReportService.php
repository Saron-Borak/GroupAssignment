<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\SessionStatus;
use App\Models\ClassSection;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every attendance read that spans more than one student.
 *
 * The rule everywhere in here: a roster summary is one grouped query with
 * conditional sums, never a loop that queries per student. A class of sixty
 * costs the same as a class of six.
 *
 * Only *closed* sessions count toward the denominator, so a meeting still open
 * cannot drag a percentage down before anybody has been marked.
 */
class AttendanceReportService
{
    /**
     * The conditional sums shared by every summary in this class.
     */
    protected function selectExpressions(string $prefix = 'r'): string
    {
        $present = AttendanceStatus::Present->value;
        $late = AttendanceStatus::Late->value;
        $absent = AttendanceStatus::Absent->value;
        $excused = AttendanceStatus::Excused->value;

        return "
            COUNT({$prefix}.id) as held,
            SUM(CASE WHEN {$prefix}.status = '{$present}' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN {$prefix}.status = '{$late}' THEN 1 ELSE 0 END) as late,
            SUM(CASE WHEN {$prefix}.status = '{$absent}' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN {$prefix}.status = '{$excused}' THEN 1 ELSE 0 END) as excused
        ";
    }

    /**
     * Attach the derived percentage and at-risk flag to a summary row.
     */
    protected function decorate(object $row): object
    {
        $countable = (int) $row->held - (int) $row->excused;
        $attended = (int) $row->present + (int) $row->late;

        $row->countable = $countable;
        $row->attended = $attended;
        $row->percentage = $countable > 0 ? round($attended / $countable * 100, 1) : 0.0;
        $row->at_risk = $countable > 0 && $row->percentage < (float) config('mis.attendance_min_percentage');

        return $row;
    }

    /**
     * One row per student on a section's roster.
     *
     * The date filter sits inside the JOIN rather than the WHERE clause: in the
     * WHERE it would discard the null side of the LEFT JOIN, so a student with
     * no records in the range would vanish from the report instead of showing
     * zero. That is the difference between "attended nothing" and "not listed".
     */
    public function classSectionStats(ClassSection $section, ?string $from = null, ?string $to = null): Collection
    {
        $closed = SessionStatus::Closed->value;

        return DB::table('enrollments as e')
            ->join('students as st', 'st.id', '=', 'e.student_id')
            ->leftJoin('attendance_sessions as s', function ($join) use ($section, $closed, $from, $to) {
                $join->on('s.class_section_id', '=', 'e.class_section_id')
                    ->where('s.class_section_id', $section->id)
                    ->where('s.status', $closed);

                if ($from) {
                    $join->where('s.session_date', '>=', $from);
                }

                if ($to) {
                    $join->where('s.session_date', '<=', $to);
                }
            })
            ->leftJoin('attendance_records as r', function ($join) {
                $join->on('r.attendance_session_id', '=', 's.id')
                    ->on('r.student_id', '=', 'e.student_id');
            })
            ->where('e.class_section_id', $section->id)
            ->where('e.status', EnrollmentStatus::Enrolled->value)
            ->groupBy('st.id', 'st.student_id_no', 'st.first_name', 'st.last_name', 'st.email')
            ->orderBy('st.last_name')
            ->orderBy('st.first_name')
            ->selectRaw("
                st.id as student_id,
                st.student_id_no,
                CONCAT(st.first_name, ' ', st.last_name) as name,
                st.email,
                {$this->selectExpressions()}
            ")
            ->get()
            ->map(fn ($row) => $this->decorate($row));
    }

    /**
     * One row per class a student is enrolled in.
     */
    public function studentOverall(Student $student): Collection
    {
        $closed = SessionStatus::Closed->value;

        return DB::table('enrollments as e')
            ->join('class_sections as cs', 'cs.id', '=', 'e.class_section_id')
            ->join('courses as c', 'c.id', '=', 'cs.course_id')
            ->join('users as u', 'u.id', '=', 'cs.lecturer_id')
            ->leftJoin('attendance_sessions as s', function ($join) use ($closed) {
                $join->on('s.class_section_id', '=', 'cs.id')->where('s.status', $closed);
            })
            ->leftJoin('attendance_records as r', function ($join) {
                $join->on('r.attendance_session_id', '=', 's.id')
                    ->on('r.student_id', '=', 'e.student_id');
            })
            ->where('e.student_id', $student->id)
            ->where('e.status', EnrollmentStatus::Enrolled->value)
            ->groupBy('cs.id', 'c.code', 'c.title', 'cs.section_code', 'cs.term', 'u.name')
            ->orderBy('c.code')
            ->selectRaw("
                cs.id as class_section_id,
                c.code as course_code,
                c.title as course_title,
                cs.section_code,
                cs.term,
                u.name as lecturer_name,
                {$this->selectExpressions()}
            ")
            ->get()
            ->map(fn ($row) => $this->decorate($row));
    }

    /**
     * Session-by-session history for one student in one class.
     */
    public function studentClassHistory(Student $student, ClassSection $section): Collection
    {
        return DB::table('attendance_sessions as s')
            ->leftJoin('attendance_records as r', function ($join) use ($student) {
                $join->on('r.attendance_session_id', '=', 's.id')
                    ->where('r.student_id', $student->id);
            })
            ->where('s.class_section_id', $section->id)
            ->orderByDesc('s.session_date')
            ->orderByDesc('s.start_time')
            ->select([
                's.id', 's.session_date', 's.start_time', 's.end_time', 's.topic', 's.status',
                'r.status as record_status', 'r.marked_via', 'r.marked_at',
            ])
            ->get();
    }

    /**
     * Every enrolment sitting below the university minimum, worst first.
     *
     * Filtering happens in PHP rather than a HAVING clause because the
     * percentage is derived from four conditional sums; expressing it in SQL
     * would duplicate the rule that decorate() already owns.
     */
    public function atRisk(?int $programId = null): Collection
    {
        $closed = SessionStatus::Closed->value;

        return DB::table('enrollments as e')
            ->join('students as st', 'st.id', '=', 'e.student_id')
            ->join('programs as p', 'p.id', '=', 'st.program_id')
            ->join('class_sections as cs', 'cs.id', '=', 'e.class_section_id')
            ->join('courses as c', 'c.id', '=', 'cs.course_id')
            ->leftJoin('attendance_sessions as s', function ($join) use ($closed) {
                $join->on('s.class_section_id', '=', 'cs.id')->where('s.status', $closed);
            })
            ->leftJoin('attendance_records as r', function ($join) {
                $join->on('r.attendance_session_id', '=', 's.id')
                    ->on('r.student_id', '=', 'e.student_id');
            })
            ->whereNull('st.deleted_at')
            ->where('e.status', EnrollmentStatus::Enrolled->value)
            ->when($programId, fn ($q) => $q->where('st.program_id', $programId))
            ->groupBy('st.id', 'st.student_id_no', 'st.first_name', 'st.last_name', 'st.email',
                'p.name', 'c.code', 'cs.section_code')
            ->selectRaw("
                st.id as student_id,
                st.student_id_no,
                CONCAT(st.first_name, ' ', st.last_name) as name,
                st.email,
                p.name as program_name,
                c.code as course_code,
                cs.section_code,
                {$this->selectExpressions()}
            ")
            ->get()
            ->map(fn ($row) => $this->decorate($row))
            ->filter(fn ($row) => $row->at_risk)
            ->sortBy('percentage')
            ->values();
    }

    /**
     * One row per class section, for the registry overview.
     */
    public function sectionOverview(): Collection
    {
        $closed = SessionStatus::Closed->value;

        return DB::table('class_sections as cs')
            ->join('courses as c', 'c.id', '=', 'cs.course_id')
            ->join('users as u', 'u.id', '=', 'cs.lecturer_id')
            ->leftJoin('attendance_sessions as s', function ($join) use ($closed) {
                $join->on('s.class_section_id', '=', 'cs.id')->where('s.status', $closed);
            })
            ->leftJoin('attendance_records as r', 'r.attendance_session_id', '=', 's.id')
            ->groupBy('cs.id', 'c.code', 'c.title', 'cs.section_code', 'cs.term', 'u.name')
            ->orderBy('c.code')
            ->selectRaw("
                cs.id as class_section_id,
                c.code as course_code,
                c.title as course_title,
                cs.section_code,
                cs.term,
                u.name as lecturer_name,
                COUNT(DISTINCT s.id) as sessions_held,
                {$this->selectExpressions()}
            ")
            ->get()
            ->map(fn ($row) => $this->decorate($row));
    }

    /**
     * Headline figures for the registry dashboard, in one query.
     */
    public function universityTotals(): object
    {
        $closed = SessionStatus::Closed->value;

        $row = DB::table('attendance_records as r')
            ->join('attendance_sessions as s', 's.id', '=', 'r.attendance_session_id')
            ->where('s.status', $closed)
            ->selectRaw($this->selectExpressions())
            ->first();

        return $this->decorate($row ?? (object) [
            'held' => 0, 'present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0,
        ]);
    }
}
