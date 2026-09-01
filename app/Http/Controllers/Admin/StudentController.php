<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentRequest;
use App\Models\AddressType;
use App\Models\Program;
use App\Models\Student;
use App\Services\StudentInsightService;
use App\Services\StudentProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Student Profile module: the core of the MIS.
 */
class StudentController extends Controller
{
    public function __construct(
        protected StudentProfileService $profiles,
        protected StudentInsightService $insights,
    ) {}

    /**
     * Searchable, filterable list of student profiles.
     */
    public function index(Request $request): View
    {
        $students = Student::with(['program.department'])
            ->search($request->string('q')->toString() ?: null)
            ->when($request->integer('program_id'), fn ($q, $id) => $q->where('program_id', $id))
            ->when($request->integer('intake_year'), fn ($q, $y) => $q->where('intake_year', $y))
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', StudentStatus::from($request->string('status')->toString()))
            )
            ->when($request->boolean('archived'), fn ($q) => $q->onlyTrashed())
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        // One aggregate query per module for the whole page, rather than three
        // queries per student.
        $insights = $this->insights->forCohort($students->getCollection());

        return view('admin.students.index', [
            'students' => $students,
            'insights' => $insights,
            'programs' => Program::orderBy('name')->get(),
            'intakeYears' => Student::query()->distinct()->orderByDesc('intake_year')->pluck('intake_year'),
        ]);
    }

    public function create(): View
    {
        $year = (int) date('Y');

        return view('admin.students.create', [
            'student' => new Student([
                'intake_year' => $year,
                'admission_date' => now()->toDateString(),
                'status' => StudentStatus::Active,
                'nationality' => 'Cambodian',
            ]),
            'addresses' => [],
            'guardians' => [],
            'suggestedNumber' => $this->profiles->nextStudentNumber($year),
        ] + $this->formOptions());
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $student = $this->profiles->create(
            $request->safe()->except(['addresses', 'guardians', 'photo', 'remove_photo']),
            $request->addressRows(),
            $request->guardianRows(),
            $request->file('photo'),
        );

        return redirect()
            ->route('admin.students.show', $student)
            ->with('success', "Profile created for {$student->fullName()}.");
    }

    /**
     * The profile page. This is where the three integrated modules surface.
     */
    public function show(Student $student): View
    {
        $student->load([
            'program.department',
            'addresses.addressType',
            'guardians',
            'user',
            'enrollments.classSection.course',
        ]);

        return view('admin.students.show', [
            'student' => $student,
            'insight' => $this->insights->forStudent($student),
            'recentAttendance' => $student->attendanceRecords()
                ->with('session.classSection.course')
                ->latest('marked_at')
                ->limit(8)
                ->get(),
            'recentSubmissions' => $student->submissions()
                ->with('assignment.classSection.course')
                ->latest('submitted_at')
                ->limit(8)
                ->get(),
            'recentComplaints' => $student->complaints()->latest()->limit(8)->get(),
        ]);
    }

    public function edit(Student $student): View
    {
        $student->load(['addresses', 'guardians']);

        return view('admin.students.edit', [
            'student' => $student,
            'addresses' => $student->addresses->toArray(),
            'guardians' => $student->guardians->toArray(),
        ] + $this->formOptions());
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $this->profiles->update(
            $student,
            $request->safe()->except(['addresses', 'guardians', 'photo', 'remove_photo']),
            $request->addressRows(),
            $request->guardianRows(),
            $request->file('photo'),
            $request->boolean('remove_photo'),
        );

        return redirect()
            ->route('admin.students.show', $student)
            ->with('success', 'Profile updated.');
    }

    /**
     * Archive rather than hard delete, so attendance, submission and complaint
     * history referencing this student is preserved.
     */
    public function destroy(Student $student): RedirectResponse
    {
        $this->profiles->archive($student);

        return redirect()
            ->route('admin.students.index')
            ->with('success', "{$student->fullName()} has been archived. Their records are retained.");
    }

    public function restore(int $id): RedirectResponse
    {
        $student = Student::onlyTrashed()->findOrFail($id);
        $this->profiles->restore($student);

        return back()->with('success', "{$student->fullName()} has been restored.");
    }

    /** @return array<string, mixed> */
    protected function formOptions(): array
    {
        return [
            'programs' => Program::with('department')->orderBy('name')->get(),
            'addressTypes' => AddressType::orderBy('id')->get(),
        ];
    }
}
