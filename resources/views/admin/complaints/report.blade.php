@extends('layouts.app')
@section('title', 'Complaint report')
@section('heading', 'Complaint summary')
@section('subheading', 'Counts by status and by category, over an optional date range')

@section('toolbar')
    <a href="{{ route('admin.complaints.report.export', request()->only('from', 'to')) }}"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>CSV
    </a>
    <a href="{{ route('admin.complaints.index') }}" class="btn btn-primary btn-sm ms-2">
        <i class="bi bi-inbox me-1"></i>Case queue
    </a>
@endsection

@section('content')
<x-page-card class="mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-4 col-lg-3">
                <label class="form-label small mb-1">Raised from</label>
                <input type="date" name="from" value="{{ $from }}"
                       class="form-control form-control-sm @error('from') is-invalid @enderror">
                @error('from')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-4 col-lg-3">
                <label class="form-label small mb-1">to</label>
                <input type="date" name="to" value="{{ $to }}"
                       class="form-control form-control-sm @error('to') is-invalid @enderror">
                @error('to')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-outline-secondary">Apply</button>
                @if ($from || $to)
                    <a href="{{ route('admin.complaints.report') }}" class="btn btn-sm btn-link">Clear</a>
                @endif
            </div>
        </form>
    </div>
</x-page-card>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Cases in range" :value="$summary['total']" icon="bi-chat-square-text" variant="primary" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Resolved" :value="$summary['by_status']['resolved'] ?? 0"
                     icon="bi-check2-circle" variant="success" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Still open"
                     :value="($summary['by_status']['pending'] ?? 0) + ($summary['by_status']['in_progress'] ?? 0)"
                     icon="bi-hourglass-split" variant="warning" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat-card label="Resolution rate" :value="number_format($summary['resolved_rate'], 1).'%'"
                     icon="bi-percent" :variant="$summary['resolved_rate'] >= 60 ? 'success' : 'warning'" />
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <x-page-card title="By status">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Status</th><th class="text-end">Cases</th><th style="min-width:150px">Share</th></tr></thead>
                    <tbody>
                    @foreach ($statuses as $status)
                        @php($count = $summary['by_status'][$status->value] ?? 0)
                        <tr>
                            <td><x-status-badge :status="$status" /></td>
                            <td class="text-end fw-semibold">{{ $count }}</td>
                            <td>
                                <x-meter :percentage="$summary['total'] ? $count / $summary['total'] * 100 : 0" />
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-page-card>
    </div>

    <div class="col-lg-6">
        <x-page-card title="By category">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Category</th><th class="text-end">Cases</th><th style="min-width:150px">Share</th></tr></thead>
                    <tbody>
                    @foreach ($categories as $category)
                        @php($count = $summary['by_category'][$category->value] ?? 0)
                        <tr>
                            <td>{{ $category->label() }}</td>
                            <td class="text-end fw-semibold">{{ $count }}</td>
                            <td>
                                <x-meter :percentage="$summary['total'] ? $count / $summary['total'] * 100 : 0" />
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-page-card>
    </div>
</div>
@endsection
