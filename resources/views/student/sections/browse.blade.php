@extends('layouts.app')
@section('title', 'Browse classes')
@section('heading', 'Class catalogue')
@section('subheading', 'Everything on offer that you are not already enrolled in')

@section('toolbar')
    <a href="{{ route('student.sections.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>My classes
    </a>
@endsection

@section('content')
<x-page-card class="mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-sm-5 col-lg-4">
                <input name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                       placeholder="Search by course code or title">
            </div>
            <div class="col-sm-4 col-lg-3">
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">Every course</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>
                            {{ $course->code }} — {{ $course->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-outline-secondary">Filter</button>
                @if (request()->hasAny(['q', 'course_id']))
                    <a href="{{ route('student.sections.browse') }}" class="btn btn-sm btn-link">Clear</a>
                @endif
            </div>
        </form>
    </div>
</x-page-card>

@if ($sections->isEmpty())
    <x-page-card>
        <x-empty-state icon="bi-search" title="Nothing matches"
                       message="You may already be enrolled in every class on offer." />
    </x-page-card>
@else
    <div class="row g-4">
        @foreach ($sections as $section)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="h5 mb-0">{{ $section->course->code }}-{{ $section->section_code }}</div>
                        <div class="small text-secondary mb-3">{{ $section->course->title }}</div>

                        <dl class="row small mb-3">
                            <dt class="col-5 fw-normal text-secondary">Lecturer</dt>
                            <dd class="col-7 mb-1">{{ $section->lecturer->name }}</dd>
                            <dt class="col-5 fw-normal text-secondary">Term</dt>
                            <dd class="col-7 mb-1">{{ $section->term }}</dd>
                            <dt class="col-5 fw-normal text-secondary">Enrolled</dt>
                            <dd class="col-7 mb-0">{{ $section->roster_count }} students</dd>
                        </dl>

                        @if ($section->schedules->isNotEmpty())
                            <div class="small">
                                @foreach ($section->schedules->sortBy(['day_of_week', 'start_time']) as $slot)
                                    <span class="badge text-bg-light border me-1 mb-1">
                                        {{ substr($slot->dayName(), 0, 3) }} {{ $slot->timeRange() }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <div class="small text-secondary">No timetable published yet.</div>
                        @endif
                    </div>
                    <div class="card-footer bg-white">
                        <form method="POST" action="{{ route('student.sections.enroll', $section) }}">
                            @csrf
                            <button class="btn btn-sm btn-primary w-100">
                                <i class="bi bi-person-plus me-1"></i>Enrol
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">{{ $sections->links() }}</div>
@endif
@endsection
