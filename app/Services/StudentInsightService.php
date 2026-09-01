<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\ComplaintStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\SubmissionStatus;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reads the three integrated modules on behalf of the student profile.
 *
 * This class is the point of the whole system. Before integration, a member of
 * the registry answering "how is this student doing?" had to open three
 * separate applications, each holding its own copy of the student's identity.
 * Here the profile is held once and every module is resolved from it.
 *
 * Each figure is produced by one aggregate query, so a profile page costs a
 * fixed number of queries no matter how much history a student has.
 */
class StudentInsightService
{
    /**
     * Every module summarised for one student, for the profile page.
     *
     * @return array<string, mixed>
     */
    public function forStudent(Student $student): array
    {
        return [
            'attendance' => $this->attendance($student),
            'submissions' => $this->submissions($student),
            'complaints' => $this->complaints($student),
            'enrollments' => $this->enrollmentCount($student),
        ];
    }

    /**
     * Attendance percentage, from the Attendance Management module.
     *
     * Excused absences leave the denominator rather than counting against the
     * student, which is the rule the attendance system itself applies.
     *
     * @return array<string, mixed>
     */
    public function attendance(Student $student): array
    {
        $present = AttendanceStatus::Present->value;
        $late = AttendanceStatus::Late->value;
        $absent = AttendanceStatus::Absent->value;
        $excused = AttendanceStatus::Excused->value;

        $row = DB::table('attendance_records')
            ->where('student_id', $student->id)
            ->selectRaw("
                COUNT(*) as recorded,
                SUM(CASE WHEN status IN ('{$present}','{$late}') THEN 1 ELSE 0 END) as attended,
                SUM(CASE WHEN status = '{$present}' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = '{$late}' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status = '{$absent}' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN status = '{$excused}' THEN 1 ELSE 0 END) as excused_count
            ")
            ->first();

        $recorded = (int) ($row->recorded ?? 0);
        $excusedCount = (int) ($row->excused_count ?? 0);
        $attended = (int) ($row->attended ?? 0);
        $countable = max(0, $recorded - $excusedCount);
        $percentage = $countable > 0 ? round($attended / $countable * 100, 1) : 0.0;

        return [
            'recorded' => $recorded,
            'countable' => $countable,
            'attended' => $attended,
            'present' => (int) ($row->present_count ?? 0),
            'late' => (int) ($row->late_count ?? 0),
            'absent' => (int) ($row->absent_count ?? 0),
            'excused' => $excusedCount,
            'percentage' => $percentage,
            'at_risk' => $countable > 0 && $percentage < (float) config('mis.attendance_min_percentage'),
        ];
    }

    /**
     * Submission counts, from the Project Submission module.
     *
     * @return array<string, mixed>
     */
    public function submissions(Student $student): array
    {
        $onTime = SubmissionStatus::OnTime->value;
        $late = SubmissionStatus::Late->value;

        $row = DB::table('submissions')
            ->where('student_id', $student->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = '{$onTime}' THEN 1 ELSE 0 END) as on_time,
                SUM(CASE WHEN status = '{$late}' THEN 1 ELSE 0 END) as late_count
            ")
            ->first();

        // Assignments issued to the sections this student is enrolled in.
        $issued = DB::table('assignments')
            ->join('enrollments', 'enrollments.class_section_id', '=', 'assignments.class_section_id')
            ->where('enrollments.student_id', $student->id)
            ->where('enrollments.status', EnrollmentStatus::Enrolled->value)
            ->count();

        $total = (int) ($row->total ?? 0);

        return [
            'issued' => $issued,
            'submitted' => $total,
            'on_time' => (int) ($row->on_time ?? 0),
            'late' => (int) ($row->late_count ?? 0),
            'missing' => max(0, $issued - $total),
            'rate' => $issued > 0 ? round($total / $issued * 100, 1) : 0.0,
        ];
    }

    /**
     * Complaint counts, from the Complaint Management module.
     *
     * @return array<string, mixed>
     */
    public function complaints(Student $student): array
    {
        $pending = ComplaintStatus::Pending->value;
        $inProgress = ComplaintStatus::InProgress->value;
        $resolved = ComplaintStatus::Resolved->value;

        $row = DB::table('complaints')
            ->where('student_id', $student->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = '{$pending}' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = '{$inProgress}' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = '{$resolved}' THEN 1 ELSE 0 END) as resolved
            ")
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'pending' => (int) ($row->pending ?? 0),
            'in_progress' => (int) ($row->in_progress ?? 0),
            'resolved' => (int) ($row->resolved ?? 0),
            'open' => (int) ($row->pending ?? 0) + (int) ($row->in_progress ?? 0),
        ];
    }

    public function enrollmentCount(Student $student): int
    {
        return $student->enrollments()->active()->count();
    }

    /**
     * The same three modules summarised for a whole cohort, in one query per
     * module rather than one per student.
     *
     * Used by the registry reports, where looping students would mean three
     * queries each - 180 queries for a 60-student program.
     *
     * @param  Collection<int, Student>|array<int, int>  $students
     * @return array<int, array<string, mixed>> keyed by student id
     */
    public function forCohort(Collection|array $students): array
    {
        $ids = $students instanceof Collection
            ? $students->pluck('id')->all()
            : $students;

        if ($ids === []) {
            return [];
        }

        $present = AttendanceStatus::Present->value;
        $late = AttendanceStatus::Late->value;
        $excused = AttendanceStatus::Excused->value;

        $attendance = DB::table('attendance_records')
            ->whereIn('student_id', $ids)
            ->groupBy('student_id')
            ->selectRaw("
                student_id,
                COUNT(*) as recorded,
                SUM(CASE WHEN status IN ('{$present}','{$late}') THEN 1 ELSE 0 END) as attended,
                SUM(CASE WHEN status = '{$excused}' THEN 1 ELSE 0 END) as excused_count
            ")
            ->get()
            ->keyBy('student_id');

        $submissions = DB::table('submissions')
            ->whereIn('student_id', $ids)
            ->groupBy('student_id')
            ->selectRaw('student_id, COUNT(*) as submitted')
            ->get()
            ->keyBy('student_id');

        $openComplaints = DB::table('complaints')
            ->whereIn('student_id', $ids)
            ->whereIn('status', ComplaintStatus::openValues())
            ->groupBy('student_id')
            ->selectRaw('student_id, COUNT(*) as open_count')
            ->get()
            ->keyBy('student_id');

        $threshold = (float) config('mis.attendance_min_percentage');
        $out = [];

        foreach ($ids as $id) {
            $a = $attendance->get($id);
            $recorded = (int) ($a->recorded ?? 0);
            $countable = max(0, $recorded - (int) ($a->excused_count ?? 0));
            $attended = (int) ($a->attended ?? 0);
            $pct = $countable > 0 ? round($attended / $countable * 100, 1) : 0.0;

            $out[$id] = [
                'attendance_percentage' => $pct,
                'attendance_at_risk' => $countable > 0 && $pct < $threshold,
                'sessions_recorded' => $recorded,
                'submissions' => (int) ($submissions->get($id)->submitted ?? 0),
                'open_complaints' => (int) ($openComplaints->get($id)->open_count ?? 0),
            ];
        }

        return $out;
    }
}
