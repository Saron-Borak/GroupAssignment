@extends('layouts.app')
@section('title', $section->label().' attendance')
@section('heading', $section->label().' — '.$section->course->title)
@section('subheading', 'Every meeting of this class, and what was recorded for you')

@section('toolbar')
    <a href="{{ route('student.attendance.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>All classes
    </a>
@endsection

@section('content')
@if ($summary)
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-stat-card label="Attendance" :value="number_format($summary->percentage, 1).'%'" icon="bi-percent"
                         :variant="$summary->at_risk ? 'danger' : 'success'"
                         :hint="$summary->at_risk ? 'Below the '.$threshold.'% requirement' : 'Meeting the requirement'" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-stat-card label="Present" :value="$summary->present" icon="bi-check2-circle" variant="success" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-stat-card label="Late" :value="$summary->late" icon="bi-clock-history" variant="warning"
                         hint="Counts as attended" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-stat-card label="Absent" :value="$summary->absent" icon="bi-x-circle"
                         :variant="$summary->absent ? 'danger' : 'secondary'" />
        </div>
    </div>
@endif

<x-page-card title="Session history"
             :subtitle="'Taught by '.$section->lecturer->name.($section->room ? ' · '.$section->room : '')">
    @if ($history->isEmpty())
        <x-empty-state icon="bi-calendar-x" title="No sessions yet"
                       message="This class has no meetings recorded." />
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>Date</th><th>Time</th><th>Topic</th><th>Session</th><th>You were</th><th>Recorded</th></tr>
                </thead>
                <tbody>
                @foreach ($history as $row)
                    @php
                        $status = $row->record_status ? App\Enums\AttendanceStatus::from($row->record_status) : null;
                        $sessionStatus = App\Enums\SessionStatus::from($row->status);
                        $via = $row->marked_via ? App\Enums\MarkedVia::from($row->marked_via) : null;
                    @endphp
                    <tr>
                        <td class="fw-semibold text-nowrap">
                            {{ \Illuminate\Support\Carbon::parse($row->session_date)->format('D, d M Y') }}
                        </td>
                        <td class="small text-nowrap">
                            {{ substr($row->start_time, 0, 5) }} - {{ substr($row->end_time, 0, 5) }}
                        </td>
                        <td class="small text-secondary">{{ $row->topic ?: '—' }}</td>
                        <td><x-status-badge :status="$sessionStatus" /></td>
                        <td>
                            @if ($status)
                                <x-status-badge :status="$status" />
                            @elseif ($sessionStatus === App\Enums\SessionStatus::Open)
                                <span class="badge text-bg-primary">Open — check in now</span>
                            @else
                                <span class="text-secondary small">Not marked</span>
                            @endif
                        </td>
                        <td class="small text-secondary">
                            @if ($via)
                                <i class="bi {{ $via->icon() }} me-1"></i>{{ $via->label() }}
                                @if ($row->marked_at)
                                    <span class="d-block">
                                        {{ \Illuminate\Support\Carbon::parse($row->marked_at)->format('d M, H:i') }}
                                    </span>
                                @endif
                            @else
                                —
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
