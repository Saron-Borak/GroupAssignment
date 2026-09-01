@extends('layouts.app')
@section('title', 'My classes')
@section('heading', 'My classes')
@section('subheading', 'Everything the three modules hold about the sections you teach')

@section('content')
@if ($sections->isEmpty())
    <x-page-card>
        <x-empty-state icon="bi-journal-x" title="You are not assigned to any classes"
                       message="The registry assigns a lecturer when it creates a class section." />
    </x-page-card>
@else
    <div class="row g-4">
        @foreach ($sections as $section)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <div>
                                <a href="{{ route('faculty.sections.show', $section) }}"
                                   class="h5 mb-0 text-decoration-none stretched-link">
                                    {{ $section->course->code }}-{{ $section->section_code }}
                                </a>
                                <div class="small text-secondary">{{ $section->course->title }}</div>
                            </div>
                        </div>

                        <div class="small text-secondary mb-3">
                            {{ $section->term }}@if ($section->room) · {{ $section->room }}@endif
                        </div>

                        @if ($section->schedules->isNotEmpty())
                            <div class="small mb-3">
                                @foreach ($section->schedules->sortBy(['day_of_week', 'start_time']) as $slot)
                                    <span class="badge text-bg-light border me-1 mb-1">
                                        {{ substr($slot->dayName(), 0, 3) }} {{ $slot->timeRange() }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <div class="row text-center g-2 pt-2 border-top">
                            <div class="col-4">
                                <div class="fw-semibold">{{ $section->roster_count }}</div>
                                <div class="small text-secondary">Students</div>
                            </div>
                            <div class="col-4">
                                <div class="fw-semibold">{{ $section->sessions_count }}</div>
                                <div class="small text-secondary">Sessions</div>
                            </div>
                            <div class="col-4">
                                <div class="fw-semibold">{{ $section->assignments_count }}</div>
                                <div class="small text-secondary">Assignments</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
