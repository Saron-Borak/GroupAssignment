@extends('layouts.app')
@section('title', 'Student profiles')
@section('heading', 'Student profiles')
@section('subheading', $students->total().' record(s)'.(request()->boolean('archived') ? ' · showing archived' : ''))

@section('toolbar')
    <a href="{{ route('admin.students.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus me-1"></i>Add a student
    </a>
@endsection

@section('content')
<x-page-card>
    <div class="card-header bg-white py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-6 col-lg-3">
                <label for="q" class="form-label small mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="search" id="q" name="q" value="{{ request('q') }}" class="form-control"
                           placeholder="Number, name, email or phone">
                </div>
            </div>
            <div class="col-sm-4 col-lg-2">
                <label for="program_id" class="form-label small mb-1">Program</label>
                <select id="program_id" name="program_id" class="form-select form-select-sm">
                    <option value="">All programs</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-4 col-lg-2">
                <label for="intake_year" class="form-label small mb-1">Intake</label>
                <select id="intake_year" name="intake_year" class="form-select form-select-sm">
                    <option value="">Any year</option>
                    @foreach ($intakeYears as $year)
                        <option value="{{ $year }}" @selected(request('intake_year') == $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-4 col-lg-2">
                <label for="status" class="form-label small mb-1">Status</label>
                <select id="status" name="status" class="form-select form-select-sm">
                    <option value="">Any status</option>
                    @foreach (\App\Enums\StudentStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected(request('status') === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" id="archived" name="archived" value="1" @checked(request()->boolean('archived'))>
                    <label class="form-check-label small" for="archived">Archived</label>
                </div>
            </div>
            <div class="col-auto"><button class="btn btn-sm btn-primary">Filter</button></div>
            <div class="col-auto"><a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a></div>
        </form>
    </div>

    @if ($students->isEmpty())
        <x-empty-state icon="bi-people" title="No student profiles found"
                       message="Adjust your filters, or create the first profile.">
            <a href="{{ route('admin.students.create') }}" class="btn btn-sm btn-primary">Add a student</a>
        </x-empty-state>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Student</th><th>Program</th><th>Intake</th><th>Status</th>
                        <th class="text-end">Attendance</th><th class="text-end">Files</th><th class="text-end">Cases</th>
                        <th style="width:1%"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($students as $student)
                    @php($i = $insights[$student->id] ?? null)
                    <tr class="{{ $i && $i['attendance_at_risk'] ? 'row-at-risk' : '' }}">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <x-student-photo :student="$student" :size="34" />
                                <div>
                                    <a href="{{ route('admin.students.show', $student) }}" class="fw-semibold text-decoration-none">
                                        {{ $student->fullName() }}
                                    </a>
                                    <div class="small text-secondary">{{ $student->student_id_no }} · {{ $student->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small text-secondary">{{ $student->program->code }}</td>
                        <td class="small">{{ $student->intake_year }}</td>
                        <td><x-status-badge :status="$student->status" /></td>
                        <td class="text-end small">
                            @if ($i && $i['sessions_recorded'] > 0)
                                <span class="fw-semibold {{ $i['attendance_at_risk'] ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($i['attendance_percentage'], 1) }}%
                                </span>
                            @else
                                <span class="text-body-tertiary">-</span>
                            @endif
                        </td>
                        <td class="text-end small">{{ $i['submissions'] ?? 0 }}</td>
                        <td class="text-end small">
                            @if (($i['open_complaints'] ?? 0) > 0)
                                <span class="badge text-bg-warning">{{ $i['open_complaints'] }}</span>
                            @else
                                <span class="text-body-tertiary">-</span>
                            @endif
                        </td>
                        <td class="text-nowrap text-end">
                            @if ($student->trashed())
                                <form method="POST" action="{{ route('admin.students.restore', $student->id) }}" class="d-inline">
                                    @csrf @method('PUT')
                                    <button class="btn btn-sm btn-outline-success" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button>
                                </form>
                            @else
                                <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.students.destroy', $student) }}" class="d-inline"
                                      onsubmit="return confirm('Archive this profile? Attendance, submission and complaint records are retained.')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Archive"><i class="bi bi-archive"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $students->links() }}</div>
    @endif
</x-page-card>
@endsection
