@extends('layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Registry dashboard')
@section('subheading', config('mis.university_name').' · '.config('mis.system_name'))

@section('content')
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <x-stat-card label="Active students" :value="number_format($counts['students'])" icon="bi-people"
                     variant="primary" :href="route('admin.students.index')" />
    </div>
    <div class="col-6 col-xl-3">
        <x-stat-card label="Programs" :value="$counts['programs']" icon="bi-diagram-3"
                     variant="info" :href="route('admin.programs.index')" />
    </div>
    <div class="col-6 col-xl-3">
        <x-stat-card label="Archived profiles" :value="$counts['archived']" icon="bi-archive"
                     variant="secondary" hint="Records retained" />
    </div>
    <div class="col-6 col-xl-3">
        <x-stat-card label="Courses" :value="$counts['courses']" icon="bi-journal-bookmark" variant="dark" />
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white py-3">
        <div class="fw-semibold">Signals from the integrated modules</div>
        <div class="small text-secondary">
            Each figure comes from a subsystem that now resolves students through this profile, not its own copy.
        </div>
    </div>
    <div class="row g-0">
        <div class="col-md-4 module-attendance">
            <div class="p-3">
                <div class="small text-uppercase fw-semibold text-secondary" style="font-size:.7rem; letter-spacing:.06em">Attendance</div>
                <div class="fs-3 fw-semibold">{{ $atRisk }}</div>
                <div class="small text-secondary">students below {{ config('mis.attendance_min_percentage') }}% attendance</div>
            </div>
        </div>
        <div class="col-md-4 module-submissions border-start">
            <div class="p-3">
                <div class="small text-uppercase fw-semibold text-secondary" style="font-size:.7rem; letter-spacing:.06em">Project submission</div>
                <div class="fs-3 fw-semibold">{{ $submissionsThisWeek }}</div>
                <div class="small text-secondary">submissions in the last seven days</div>
            </div>
        </div>
        <div class="col-md-4 module-complaints border-start">
            <div class="p-3">
                <div class="small text-uppercase fw-semibold text-secondary" style="font-size:.7rem; letter-spacing:.06em">Complaints</div>
                <div class="fs-3 fw-semibold">{{ $openComplaints }}</div>
                <div class="small text-secondary">cases still open</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <x-page-card title="Profiles needing attention" subtitle="Missing a photograph, an address or a guardian">
            <x-slot:actions>
                <a href="{{ route('admin.reports.completeness') }}" class="btn btn-sm btn-outline-secondary">Full report</a>
            </x-slot:actions>

            @if ($incomplete->isEmpty())
                <x-empty-state icon="bi-check2-circle" title="Every active profile is complete"
                               message="Nothing is waiting for the registry." />
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Student</th><th>Program</th><th style="width:170px">Completeness</th></tr></thead>
                        <tbody>
                        @foreach ($incomplete as $student)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.students.show', $student) }}" class="fw-semibold text-decoration-none">
                                        {{ $student->fullName() }}
                                    </a>
                                    <div class="small text-secondary">{{ $student->student_id_no }}</div>
                                </td>
                                <td class="small text-secondary">{{ $student->program->code }}</td>
                                <td><x-meter :percentage="$student->completenessPercentage()" /></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-page-card>
    </div>

    <div class="col-lg-5">
        <x-page-card title="Students by program">
            <x-slot:actions>
                <a href="{{ route('admin.reports.by-program') }}" class="btn btn-sm btn-outline-secondary">Report</a>
            </x-slot:actions>

            @if ($byProgram->isEmpty())
                <x-empty-state icon="bi-diagram-3" title="No programs yet" />
            @else
                <div class="list-group list-group-flush">
                    @foreach ($byProgram as $program)
                        <div class="list-group-item d-flex align-items-center gap-2">
                            <div class="flex-grow-1 min-w-0">
                                <div class="small fw-semibold">{{ $program->code }}</div>
                                <div class="text-secondary text-truncate" style="font-size:.78rem">{{ $program->name }}</div>
                            </div>
                            <span class="badge text-bg-light border">{{ $program->students_count }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-page-card>
    </div>
</div>
@endsection
