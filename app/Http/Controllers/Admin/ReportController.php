<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Student;
use App\Services\StudentInsightService;
use App\Support\CsvExporter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(protected StudentInsightService $insights) {}

    /**
     * Students by program - the reporting requirement in the specification.
     */
    public function byProgram(Request $request): View
    {
        $program = $request->integer('program_id')
            ? Program::with('department')->find($request->integer('program_id'))
            : null;

        $students = Student::with('program')
            ->when($program, fn ($q) => $q->where('program_id', $program->id))
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', StudentStatus::from($request->string('status')->toString())),
                fn ($q) => $q->where('status', StudentStatus::Active),
            )
            ->orderBy('program_id')
            ->orderBy('last_name')
            ->get();

        return view('admin.reports.by-program', [
            'programs' => Program::with('department')->orderBy('name')->get(),
            'program' => $program,
            'students' => $students,
            'insights' => $this->insights->forCohort($students),
        ]);
    }

    /**
     * Which profiles are still missing required information.
     */
    public function completeness(Request $request): View
    {
        $students = Student::with(['program', 'addresses', 'guardians'])
            ->where('status', StudentStatus::Active)
            ->when($request->integer('program_id'), fn ($q, $id) => $q->where('program_id', $id))
            ->orderBy('last_name')
            ->get()
            ->sortBy(fn (Student $s) => $s->completenessPercentage())
            ->values();

        return view('admin.reports.completeness', [
            'programs' => Program::orderBy('name')->get(),
            'students' => $students,
        ]);
    }

    public function exportByProgram(Request $request, CsvExporter $csv): StreamedResponse
    {
        $program = $request->integer('program_id') ? Program::find($request->integer('program_id')) : null;

        $students = Student::with('program')
            ->when($program, fn ($q) => $q->where('program_id', $program->id))
            ->where('status', StudentStatus::Active)
            ->orderBy('last_name')
            ->get();

        $insights = $this->insights->forCohort($students);

        return $csv->download(
            $csv->filename('students', $program?->code ?? 'all-programs'),
            ['Student No', 'First Name', 'Last Name', 'Gender', 'Date of Birth', 'Email', 'Phone',
                'Program', 'Intake', 'Status', 'Attendance %', 'Submissions', 'Open Complaints'],
            $students->map(fn (Student $s) => [
                $s->student_id_no,
                $s->first_name,
                $s->last_name,
                $s->gender->label(),
                $s->date_of_birth?->format('Y-m-d'),
                $s->email,
                $s->phone,
                $s->program->code,
                $s->intake_year,
                $s->status->label(),
                number_format($insights[$s->id]['attendance_percentage'] ?? 0, 1),
                $insights[$s->id]['submissions'] ?? 0,
                $insights[$s->id]['open_complaints'] ?? 0,
            ]),
        );
    }
}
