<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ComplaintStatus;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Course;
use App\Models\Program;
use App\Models\Student;
use App\Models\Submission;
use App\Services\StudentInsightService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(StudentInsightService $insights): View
    {
        // Profiles missing a photo, an address or a guardian - the registry's
        // daily work queue.
        $incomplete = Student::query()
            ->where('status', StudentStatus::Active)
            ->where(function ($q) {
                $q->whereNull('photo_path')
                    ->orWhereDoesntHave('addresses')
                    ->orWhereDoesntHave('guardians');
            })
            ->with('program')
            ->limit(8)
            ->get();

        $byProgram = Program::withCount(['students' => fn ($q) => $q->where('status', StudentStatus::Active)])
            ->orderByDesc('students_count')
            ->get();

        return view('admin.dashboard', [
            'counts' => [
                'students' => Student::where('status', StudentStatus::Active)->count(),
                'programs' => Program::count(),
                'courses' => Course::count(),
                'archived' => Student::onlyTrashed()->count(),
            ],
            'byProgram' => $byProgram,
            'incomplete' => $incomplete,
            'openComplaints' => Complaint::whereIn('status', [ComplaintStatus::Pending, ComplaintStatus::InProgress])->count(),
            'submissionsThisWeek' => Submission::where('submitted_at', '>=', now()->subWeek())->count(),
            'atRisk' => $this->atRiskCount(),
        ]);
    }

    /**
     * Students below the attendance threshold, counted in one query rather
     * than by looping the cohort.
     */
    protected function atRiskCount(): int
    {
        $threshold = (float) config('mis.attendance_min_percentage');

        return DB::table('attendance_records')
            ->groupBy('student_id')
            ->havingRaw(
                "SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END)
                 / NULLIF(SUM(CASE WHEN status <> 'excused' THEN 1 ELSE 0 END), 0) * 100 < ?",
                [$threshold]
            )
            ->select('student_id')
            ->get()
            ->count();
    }
}
