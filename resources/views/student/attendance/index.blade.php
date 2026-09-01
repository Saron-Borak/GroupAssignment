@extends('layouts.app')
@section('title', 'My attendance')
@section('heading', 'My attendance')
@section('subheading', 'One row per class, counted from the sessions your lecturer has closed')

@section('toolbar')
    <a href="{{ route('student.checkin.form') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-qr-code-scan me-1"></i>Check in
    </a>
@endsection

@section('content')
@php
    $atRisk = $rows->where('at_risk', true);
    $overallCountable = $rows->sum('countable');
    $overallAttended = $rows->sum('attended');
    $overall = $overallCountable > 0 ? round($overallAttended / $overallCountable * 100, 1) : 0.0;
@endphp

@if ($atRisk->isNotEmpty())
    <div class="alert alert-danger d-flex gap-2 align-items-start">
        <i class="bi bi-exclamation-octagon-fill mt-1"></i>
        <div>
            <strong>You are below the {{ $threshold }}% requirement in
            {{ $atRisk->count() }} {{ Str::plural('class', $atRisk->count()) }}.</strong>
            <div class="small">
                {{ $atRisk->map(fn ($r) => $r->course_code.'-'.$r->section_code)->join(', ') }}.
                Speak to your lecturer or the registry — falling below the requirement can bar you
                from the examination.
            </div>
        </div>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Overall" :value="number_format($overall, 1).'%'" icon="bi-percent"
                     :variant="$overall >= $threshold ? 'success' : 'danger'"
                     hint="Across every class" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Classes" :value="$rows->count()" icon="bi-journal-bookmark" variant="primary" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Sessions attended" :value="$overallAttended" icon="bi-check2-circle" variant="success"
                     :hint="'of '.$overallCountable.' counted'" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Below requirement" :value="$atRisk->count()" icon="bi-exclamation-triangle"
                     :variant="$atRisk->isEmpty() ? 'secondary' : 'danger'" />
    </div>
</div>

<x-page-card title="By class" subtitle="An excused absence is removed from the total, not counted against you">
    @if ($rows->isEmpty())
        <x-empty-state icon="bi-calendar-x" title="No attendance recorded yet"
                       message="Once your lecturer closes a session, it will appear here." />
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Class</th><th>Lecturer</th>
                        <th class="text-end">Present</th><th class="text-end">Late</th>
                        <th class="text-end">Absent</th><th class="text-end">Excused</th>
                        <th style="min-width:170px">Attendance</th><th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $row->course_code }}-{{ $row->section_code }}</div>
                            <div class="small text-secondary">{{ $row->course_title }}</div>
                        </td>
                        <td class="small text-secondary">{{ $row->lecturer_name }}</td>
                        <td class="text-end">{{ $row->present }}</td>
                        <td class="text-end">{{ $row->late }}</td>
                        <td class="text-end {{ $row->absent ? 'text-danger fw-semibold' : '' }}">{{ $row->absent }}</td>
                        <td class="text-end text-secondary">{{ $row->excused }}</td>
                        <td><x-meter :percentage="$row->percentage" :threshold="$threshold" /></td>
                        <td class="text-end">
                            <a href="{{ route('student.attendance.show', $row->class_section_id) }}"
                               class="btn btn-sm btn-outline-secondary">History</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-page-card>
@endsection
