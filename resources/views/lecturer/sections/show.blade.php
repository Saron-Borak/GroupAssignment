@extends('layouts.app')
@section('title', $section->label())
@section('heading', $section->label().' — '.$section->course->title)
@section('subheading', $section->term.($section->room ? ' · '.$section->room : '').' · all three modules on one screen')

@section('toolbar')
    <a href="{{ route('faculty.attendance.report', $section) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-bar-chart me-1"></i>Attendance report
    </a>
    <a href="{{ route('faculty.attendance.create', ['section_id' => $section->id]) }}" class="btn btn-primary btn-sm ms-2">
        <i class="bi bi-plus-lg me-1"></i>New session
    </a>
@endsection

@section('content')
@php
    $atRisk = $stats->where('at_risk', true);
    $countable = $stats->sum('countable');
    $attended = $stats->sum('attended');
    $average = $countable > 0 ? round($attended / $countable * 100, 1) : 0.0;
@endphp

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="On the roster" :value="$roster->count()" icon="bi-people" variant="primary" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Class average" :value="number_format($average, 1).'%'" icon="bi-percent"
                     :variant="$average >= $threshold ? 'success' : 'warning'" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Below {{ $threshold }}%" :value="$atRisk->count()" icon="bi-exclamation-triangle"
                     :variant="$atRisk->isEmpty() ? 'secondary' : 'danger'" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Assignments" :value="$assignments->count()" icon="bi-file-earmark-arrow-up"
                     variant="secondary" />
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <x-page-card title="Roster" subtitle="Names read from the shared profile, with each student's standing">
            @if ($roster->isEmpty())
                <x-empty-state icon="bi-person-plus" title="Nobody enrolled"
                               message="The registry manages the roster." />
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Student</th><th>Programme</th><th style="min-width:160px">Attendance</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($roster as $enrollment)
                            @php($row = $stats->get($enrollment->student_id))
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $enrollment->student->fullName() }}</div>
                                    <div class="small text-secondary">{{ $enrollment->student->student_id_no }}</div>
                                </td>
                                <td class="small text-secondary">{{ $enrollment->student->program?->code ?? '—' }}</td>
                                <td>
                                    @if ($row && $row->held)
                                        <x-meter :percentage="$row->percentage" :threshold="$threshold" />
                                    @else
                                        <span class="small text-secondary">No data</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('faculty.students.show', $enrollment->student) }}"
                                       class="btn btn-sm btn-outline-secondary">Profile</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-page-card>
    </div>

    <div class="col-lg-5">
        <x-page-card title="Recent sessions" subtitle="Newest first">
            @if ($sessions->isEmpty())
                <x-empty-state icon="bi-calendar-x" title="No sessions yet" />
            @else
                <ul class="list-group list-group-flush">
                    @foreach ($sessions as $session)
                        <li class="list-group-item d-flex align-items-center gap-2">
                            <div class="min-w-0">
                                <div class="fw-semibold">{{ $session->session_date->format('D, d M Y') }}</div>
                                <div class="small text-secondary">
                                    {{ $session->timeRange() }}@if ($session->topic) · {{ $session->topic }} @endif
                                </div>
                            </div>
                            <div class="ms-auto text-end">
                                <x-status-badge :status="$session->status" />
                                <div class="small text-secondary mt-1">
                                    {{ $session->attended_count }} / {{ $roster->count() }} attended
                                </div>
                            </div>
                            <a href="{{ route('faculty.attendance.mark', $session) }}"
                               class="btn btn-sm btn-outline-secondary">Open</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-page-card>

        <x-page-card title="Assignments" subtitle="Issued to this same roster" class="mt-4">
            @if ($assignments->isEmpty())
                <x-empty-state icon="bi-file-earmark-x" title="Nothing issued yet">
                    <a href="{{ route('faculty.assignments.create') }}" class="btn btn-sm btn-primary">Issue an assignment</a>
                </x-empty-state>
            @else
                <ul class="list-group list-group-flush">
                    @foreach ($assignments as $assignment)
                        <li class="list-group-item d-flex align-items-center gap-2">
                            <div class="min-w-0">
                                <a href="{{ route('faculty.assignments.show', $assignment) }}"
                                   class="fw-semibold text-decoration-none">{{ $assignment->title }}</a>
                                <div class="small text-secondary">
                                    Due {{ $assignment->deadline->format('d M Y, H:i') }}
                                </div>
                            </div>
                            <div class="ms-auto text-end small">
                                <div class="fw-semibold">{{ $assignment->submissions_count }} / {{ $roster->count() }}</div>
                                @if ($assignment->late_count)
                                    <div class="text-warning">{{ $assignment->late_count }} late</div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-page-card>
    </div>
</div>
@endsection
