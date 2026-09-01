@extends('layouts.app')
@section('title', $section->label())
@section('heading', $section->label().' — '.$section->course->title)
@section('subheading', $section->term.' · '.$section->lecturer->name.($section->room ? ' · '.$section->room : ''))

@section('toolbar')
    <a href="{{ route('admin.sections.edit', $section) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-pencil me-1"></i>Edit
    </a>
    <a href="{{ route('admin.sections.index') }}" class="btn btn-outline-secondary btn-sm ms-2">
        <i class="bi bi-arrow-left me-1"></i>All sections
    </a>
@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="On the roster" :value="$roster->where('status.value', 'enrolled')->count()"
                     icon="bi-people" variant="primary" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Class meetings" :value="$sessionCount" icon="bi-calendar-check" variant="secondary" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Assignments" :value="$assignmentCount" icon="bi-file-earmark-arrow-up" variant="secondary" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Timetable slots" :value="$section->schedules->count()" icon="bi-clock" variant="secondary"
                     hint="Sessions generate from these" />
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <x-page-card title="Weekly timetable"
                     subtitle="The lecturer generates a term of sessions from these slots">
            @if ($section->schedules->isEmpty())
                <x-empty-state icon="bi-clock-history" title="No timetable yet"
                               message="Add a slot below so sessions can be generated automatically." />
            @else
                <ul class="list-group list-group-flush">
                    @foreach ($section->schedules->sortBy(['day_of_week', 'start_time']) as $slot)
                        <li class="list-group-item d-flex align-items-center gap-2">
                            <div>
                                <div class="fw-semibold">{{ $slot->dayName() }}</div>
                                <div class="small text-secondary">
                                    {{ $slot->timeRange() }}@if ($slot->room) · {{ $slot->room }}@endif
                                </div>
                            </div>
                            <form method="POST" class="ms-auto"
                                  action="{{ route('admin.sections.schedules.destroy', [$section, $slot]) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="card-footer bg-white">
                <form method="POST" action="{{ route('admin.sections.schedules.store', $section) }}" class="row g-2">
                    @csrf
                    <div class="col-12">
                        <label class="form-label small mb-1">Day</label>
                        <select name="day_of_week" class="form-select form-select-sm" required>
                            @foreach ($days as $number => $name)
                                <option value="{{ $number }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label small mb-1">Start</label>
                        <input type="time" name="start_time" value="08:00" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label small mb-1">End</label>
                        <input type="time" name="end_time" value="10:00" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label small mb-1">Room</label>
                        <input name="room" maxlength="50" class="form-control form-control-sm"
                               value="{{ $section->room }}">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-plus-lg me-1"></i>Add slot
                        </button>
                    </div>
                </form>
            </div>
        </x-page-card>
    </div>

    <div class="col-lg-7">
        <x-page-card title="Roster"
                     subtitle="Attendance is taken against this list and assignments are issued to it">
            @if ($roster->isEmpty())
                <x-empty-state icon="bi-person-plus" title="Nobody enrolled yet"
                               message="Add a student below." />
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Student</th><th>Programme</th><th>Status</th><th>Since</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($roster as $enrollment)
                            <tr class="{{ $enrollment->status->value !== 'enrolled' ? 'opacity-75' : '' }}">
                                <td>
                                    <a href="{{ route('admin.students.show', $enrollment->student) }}"
                                       class="fw-semibold text-decoration-none">
                                        {{ $enrollment->student->fullName() }}
                                    </a>
                                    <div class="small text-secondary">{{ $enrollment->student->student_id_no }}</div>
                                </td>
                                <td class="small text-secondary">{{ $enrollment->student->program?->code ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $enrollment->status->value === 'enrolled' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $enrollment->status->label() }}
                                    </span>
                                </td>
                                <td class="small text-secondary">{{ $enrollment->enrolled_at?->format('d M Y') }}</td>
                                <td class="text-end">
                                    @if ($enrollment->status->value === 'enrolled')
                                        <form method="POST" class="d-inline"
                                              action="{{ route('admin.sections.unenroll', [$section, $enrollment]) }}"
                                              onsubmit="return confirm('Withdraw {{ $enrollment->student->fullName() }} from this class?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Withdraw</button>
                                        </form>
                                    @else
                                        <form method="POST" class="d-inline" action="{{ route('admin.sections.enroll', $section) }}">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $enrollment->student_id }}">
                                            <button class="btn btn-sm btn-outline-success">Re-enrol</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="card-footer bg-white">
                <form method="POST" action="{{ route('admin.sections.enroll', $section) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-sm-8">
                        <label class="form-label small mb-1">Enrol a student</label>
                        <select name="student_id" class="form-select form-select-sm" required>
                            <option value="">Choose a student...</option>
                            @foreach ($candidates as $student)
                                <option value="{{ $student->id }}">
                                    {{ $student->fullName() }} — {{ $student->student_id_no }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <button class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-person-plus me-1"></i>Enrol
                        </button>
                    </div>
                </form>
                <div class="form-text mt-2">
                    Withdrawing somebody who already has attendance or submitted work marks them
                    <em>dropped</em> rather than deleting the row, so their history survives.
                </div>
            </div>
        </x-page-card>
    </div>
</div>
@endsection
