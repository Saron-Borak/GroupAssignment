@extends('layouts.app')
@section('title', 'Students by program')
@section('heading', 'Students by program')
@section('subheading', $program ? $program->name.' ('.$program->department->name.')' : 'All programs')

@section('toolbar')
    <a href="{{ route('admin.reports.by-program.export', request()->query()) }}" class="btn btn-outline-success btn-sm no-print">
        <i class="bi bi-filetype-csv me-1"></i>Download CSV
    </a>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm ms-2 no-print"><i class="bi bi-printer me-1"></i>Print</button>
@endsection

@section('content')
<form method="GET" class="row g-2 align-items-end mb-3 no-print">
    <div class="col-sm-5 col-lg-4">
        <label for="program_id" class="form-label small mb-1">Program</label>
        <select id="program_id" name="program_id" class="form-select form-select-sm">
            <option value="">All programs</option>
            @foreach ($programs as $option)
                <option value="{{ $option->id }}" @selected($program?->id === $option->id)>
                    {{ $option->code }} - {{ $option->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-3 col-lg-2">
        <label for="status" class="form-label small mb-1">Status</label>
        <select id="status" name="status" class="form-select form-select-sm">
            @foreach (\App\Enums\StudentStatus::cases() as $case)
                <option value="{{ $case->value }}" @selected(request('status', 'active') === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Apply</button></div>
    <div class="col-auto ms-auto small text-secondary">{{ $students->count() }} student(s)</div>
</form>

<x-page-card>
    @if ($students->isEmpty())
        <x-empty-state icon="bi-list-columns" title="No students match"
                       message="Choose a different program or status." />
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Student no.</th><th>Name</th><th>Program</th><th>Intake</th>
                        <th class="text-end">Attendance</th><th class="text-end">Submissions</th><th class="text-end">Open cases</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($students as $student)
                    @php($i = $insights[$student->id] ?? null)
                    <tr class="{{ $i && $i['attendance_at_risk'] ? 'row-at-risk' : '' }}">
                        <td class="font-monospace small">{{ $student->student_id_no }}</td>
                        <td>
                            <a href="{{ route('admin.students.show', $student) }}" class="fw-semibold text-decoration-none">{{ $student->fullName() }}</a>
                            <div class="small text-secondary">{{ $student->email }}</div>
                        </td>
                        <td class="small text-secondary">{{ $student->program->code }}</td>
                        <td class="small">{{ $student->intake_year }}</td>
                        <td class="text-end" style="width:170px">
                            @if ($i && $i['sessions_recorded'] > 0)
                                <x-meter :percentage="$i['attendance_percentage']" :threshold="config('mis.attendance_min_percentage')" />
                            @else
                                <span class="small text-body-tertiary">No records</span>
                            @endif
                        </td>
                        <td class="text-end small">{{ $i['submissions'] ?? 0 }}</td>
                        <td class="text-end small">{{ $i['open_complaints'] ?? 0 }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-page-card>
@endsection
