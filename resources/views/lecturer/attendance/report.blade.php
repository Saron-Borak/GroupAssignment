@extends('layouts.app')
@section('title', $section->label().' report')
@section('heading', 'Attendance report — '.$section->label())
@section('subheading', $section->course->title.' · one grouped query, whatever the size of the class')

@section('toolbar')
    <a href="{{ route('faculty.attendance.report.export', ['section' => $section] + request()->only('from', 'to')) }}"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>CSV
    </a>
    <a href="{{ route('faculty.sections.show', $section) }}" class="btn btn-outline-secondary btn-sm ms-2">
        <i class="bi bi-arrow-left me-1"></i>Class
    </a>
@endsection

@section('content')
@php
    $atRisk = $rows->where('at_risk', true);
    $countable = $rows->sum('countable');
    $attended = $rows->sum('attended');
    $classAverage = $countable > 0 ? round($attended / $countable * 100, 1) : 0.0;
@endphp

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Class average" :value="number_format($classAverage, 1).'%'" icon="bi-percent"
                     :variant="$classAverage >= $threshold ? 'success' : 'warning'" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="On the roster" :value="$rows->count()" icon="bi-people" variant="primary" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Below {{ $threshold }}%" :value="$atRisk->count()" icon="bi-exclamation-triangle"
                     :variant="$atRisk->isEmpty() ? 'secondary' : 'danger'" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Sessions counted" :value="$rows->max('held') ?? 0" icon="bi-calendar-check"
                     variant="secondary" hint="Closed sessions only" />
    </div>
</div>

<x-page-card>
    <div class="card-header bg-white py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-4 col-lg-3">
                <label class="form-label small mb-1">From</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm">
            </div>
            <div class="col-sm-4 col-lg-3">
                <label class="form-label small mb-1">To</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-outline-secondary">Apply</button>
                @if ($from || $to)
                    <a href="{{ route('faculty.attendance.report', $section) }}" class="btn btn-sm btn-link">Clear</a>
                @endif
            </div>
        </form>
    </div>

    @if ($rows->isEmpty())
        <x-empty-state icon="bi-people" title="Nobody on this roster"
                       message="The registry enrols students onto a class section." />
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th class="text-end">Held</th><th class="text-end">Present</th>
                        <th class="text-end">Late</th><th class="text-end">Absent</th>
                        <th class="text-end">Excused</th><th style="min-width:170px">Attendance</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($rows as $row)
                    <tr class="{{ $row->at_risk ? 'table-danger' : '' }}">
                        <td>
                            <div class="fw-semibold">{{ $row->name }}</div>
                            <div class="small text-secondary">{{ $row->student_id_no }}</div>
                        </td>
                        <td class="text-end">{{ $row->held }}</td>
                        <td class="text-end">{{ $row->present }}</td>
                        <td class="text-end">{{ $row->late }}</td>
                        <td class="text-end">{{ $row->absent }}</td>
                        <td class="text-end text-secondary">{{ $row->excused }}</td>
                        <td><x-meter :percentage="$row->percentage" :threshold="$threshold" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white small text-secondary">
            Attendance counts present plus late, over the sessions held minus the excused ones.
            A row is flagged below {{ $threshold }}%.
        </div>
    @endif
</x-page-card>
@endsection
