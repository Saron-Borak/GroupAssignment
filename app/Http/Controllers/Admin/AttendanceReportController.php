<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Services\AttendanceReportService;
use App\Support\CsvExporter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The registry's view of attendance across the university: which classes are
 * running below the requirement, and which students are at risk of being
 * barred from an examination.
 *
 * Both screens are grouped aggregate queries, so a cohort of forty-five costs
 * the same as a cohort of four thousand.
 */
class AttendanceReportController extends Controller
{
    public function __construct(protected AttendanceReportService $reports) {}

    /**
     * Every class section, with the proportion of marks that were attendance.
     */
    public function overview(): View
    {
        return view('admin.reports.attendance', [
            'totals' => $this->reports->universityTotals(),
            'sections' => $this->reports->sectionOverview(),
            'threshold' => (float) config('mis.attendance_min_percentage'),
        ]);
    }

    /**
     * Students below the minimum, worst first.
     */
    public function atRisk(Request $request): View
    {
        return view('admin.reports.at-risk', [
            'programs' => Program::orderBy('name')->get(),
            'programId' => $request->integer('program_id') ?: null,
            'rows' => $this->reports->atRisk($request->integer('program_id') ?: null),
            'threshold' => (float) config('mis.attendance_min_percentage'),
        ]);
    }

    public function exportAtRisk(Request $request, CsvExporter $csv): StreamedResponse
    {
        $rows = $this->reports->atRisk($request->integer('program_id') ?: null);

        return $csv->download(
            $csv->filename('students-at-risk'),
            ['Student No', 'Name', 'Email', 'Program', 'Course', 'Section', 'Attended', 'Countable', 'Absent', 'Attendance %'],
            $rows->map(fn ($r) => [
                $r->student_id_no, $r->name, $r->email, $r->program_name,
                $r->course_code, $r->section_code,
                $r->attended, $r->countable, $r->absent,
                number_format($r->percentage, 1),
            ]),
        );
    }

    public function exportOverview(CsvExporter $csv): StreamedResponse
    {
        return $csv->download(
            $csv->filename('attendance-by-class'),
            ['Course', 'Title', 'Section', 'Term', 'Lecturer', 'Sessions Held', 'Present', 'Late', 'Absent', 'Excused', 'Attendance %'],
            $this->reports->sectionOverview()->map(fn ($r) => [
                $r->course_code, $r->course_title, $r->section_code, $r->term, $r->lecturer_name,
                $r->sessions_held, $r->present, $r->late, $r->absent, $r->excused,
                number_format($r->percentage, 1),
            ]),
        );
    }
}
