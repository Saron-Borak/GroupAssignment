@extends('layouts.app')
@section('title', 'My classes')
@section('heading', 'My classes')
@section('subheading', 'The classes you are enrolled in, and how you are doing in each')

@section('toolbar')
    <a href="{{ route('student.sections.browse') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-search me-1"></i>Browse the catalogue
    </a>
@endsection

@section('content')
@if ($enrollments->isEmpty())
    <x-page-card>
        <x-empty-state icon="bi-journal-x" title="You are not enrolled in anything yet"
                       message="Browse the catalogue to join a class, or ask the registry to enrol you.">
            <a href="{{ route('student.sections.browse') }}" class="btn btn-sm btn-primary">Browse classes</a>
        </x-empty-state>
    </x-page-card>
@else
    <div class="row g-4">
        @foreach ($enrollments as $enrollment)
            @php
                $section = $enrollment->classSection;
                $row = $stats->get($section->id);
                $dropped = $enrollment->status->value !== 'enrolled';
            @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card h-100 {{ $dropped ? 'opacity-75' : '' }}">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <div class="min-w-0">
                                <div class="h5 mb-0">{{ $section->course->code }}-{{ $section->section_code }}</div>
                                <div class="small text-secondary">{{ $section->course->title }}</div>
                            </div>
                            @if ($dropped)
                                <span class="badge text-bg-secondary ms-auto">{{ $enrollment->status->label() }}</span>
                            @endif
                        </div>

                        <div class="small text-secondary mb-3">
                            {{ $section->lecturer->name }} · {{ $section->term }}
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

                        @if ($row && $row->held)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-secondary">Attendance</span>
                                    <span class="{{ $row->at_risk ? 'text-danger fw-semibold' : '' }}">
                                        {{ $row->attended }} of {{ $row->countable }}
                                    </span>
                                </div>
                                <x-meter :percentage="$row->percentage" :threshold="$threshold" />
                            </div>
                        @else
                            <div class="small text-secondary mb-3">No attendance recorded yet.</div>
                        @endif
                    </div>

                    <div class="card-footer bg-white d-flex gap-2">
                        <a href="{{ route('student.attendance.show', $section) }}" class="btn btn-sm btn-outline-secondary">
                            History
                        </a>
                        @if (! $dropped)
                            <form method="POST" action="{{ route('student.sections.unenroll', $section) }}" class="ms-auto"
                                  onsubmit="return confirm('Leave {{ $section->label() }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-link text-danger">Leave</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
