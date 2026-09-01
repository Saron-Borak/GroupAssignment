@extends('layouts.app')
@section('title', $assignment->title)
@section('heading', $assignment->title)
@section('subheading', $assignment->classSection->course->code.'-'.$assignment->classSection->section_code.' · due '.$assignment->deadline->format('d M Y, H:i'))

@section('toolbar')
    <a href="{{ route('faculty.assignments.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>All assignments
    </a>
@endsection

@section('content')
@php
    $submitted = $roster->filter(fn ($e) => $submissions->has($e->student_id))->count();
    $late = $submissions->where('status', \App\Enums\SubmissionStatus::Late)->count();
@endphp

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <x-stat-card label="On the roster" :value="$roster->count()" icon="bi-people" variant="primary" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Submitted" :value="$submitted" icon="bi-cloud-arrow-up" variant="success" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Late" :value="$late" icon="bi-clock" variant="warning" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Not submitted" :value="$roster->count() - $submitted" icon="bi-dash-circle" variant="danger" />
    </div>
</div>

@if ($assignment->description)
    <x-page-card title="Instructions" class="mb-3">
        <div class="card-body"><p class="mb-0">{{ $assignment->description }}</p></div>
    </x-page-card>
@endif

<x-page-card title="Submissions" subtitle="The roster comes from the shared enrolment table">
    @if ($roster->isEmpty())
        <x-empty-state icon="bi-person-plus" title="Nobody is enrolled in this class" />
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Student</th><th>Submitted</th><th>File</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                @foreach ($roster as $enrollment)
                    @php($submission = $submissions->get($enrollment->student_id))
                    <tr class="{{ $submission ? '' : 'row-at-risk' }}">
                        <td>
                            <div class="fw-semibold">{{ $enrollment->student->fullName() }}</div>
                            <div class="small text-secondary">{{ $enrollment->student->student_id_no }}</div>
                        </td>
                        <td class="small text-nowrap">{{ $submission?->submitted_at?->format('d M Y, H:i') ?: '-' }}</td>
                        <td class="small text-secondary">{{ $submission?->original_filename ?: '-' }}</td>
                        <td>
                            @if ($submission)
                                <x-status-badge :status="$submission->status" />
                            @else
                                <span class="badge text-bg-danger-subtle text-danger-emphasis border border-danger-subtle">Not submitted</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if ($submission)
                                <a href="{{ route('faculty.assignments.download', [$assignment, $enrollment->student_id]) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-download me-1"></i>Download
                                </a>
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
