@extends('layouts.app')
@section('title', 'Attendance by class')
@section('heading', 'Attendance across the university')
@section('subheading', 'One grouped query per screen, so the cost does not grow with the cohort')

@section('toolbar')
    <a href="{{ route('admin.reports.attendance.export') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>CSV
    </a>
    <a href="{{ route('admin.reports.at-risk') }}" class="btn btn-primary btn-sm ms-2">
        <i class="bi bi-exclamation-triangle me-1"></i>At-risk students
    </a>
@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="University attendance" :value="number_format($totals->percentage, 1).'%'"
                     icon="bi-percent" :variant="$totals->percentage >= $threshold ? 'success' : 'warning'"
                     hint="Present and late, over counted sessions" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Marks recorded" :value="number_format($totals->held)" icon="bi-journal-check"
                     variant="primary" hint="Closed sessions only" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Absences" :value="number_format($totals->absent)" icon="bi-x-circle" variant="danger" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Excused" :value="number_format($totals->excused)" icon="bi-shield-check"
                     variant="secondary" hint="Removed from the total" />
    </div>
</div>

<x-page-card title="By class section" subtitle="Ordered by course code">
    @if ($sections->isEmpty())
        <x-empty-state icon="bi-journal-x" title="No class sections yet"
                       message="Add a course and a class section to start recording attendance." />
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Class</th><th>Lecturer</th><th>Term</th>
                        <th class="text-end">Sessions</th><th class="text-end">Present</th>
                        <th class="text-end">Late</th><th class="text-end">Absent</th>
                        <th style="min-width:170px">Attendance</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($sections as $row)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $row->course_code }}-{{ $row->section_code }}</div>
                            <div class="small text-secondary">{{ $row->course_title }}</div>
                        </td>
                        <td class="small text-secondary">{{ $row->lecturer_name }}</td>
                        <td class="small text-secondary">{{ $row->term }}</td>
                        <td class="text-end">{{ $row->sessions_held }}</td>
                        <td class="text-end">{{ $row->present }}</td>
                        <td class="text-end">{{ $row->late }}</td>
                        <td class="text-end {{ $row->absent ? 'text-danger' : '' }}">{{ $row->absent }}</td>
                        <td>
                            @if ($row->held)
                                <x-meter :percentage="$row->percentage" :threshold="$threshold" />
                            @else
                                <span class="small text-secondary">No closed sessions</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-page-card>
@endsection
