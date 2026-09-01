@extends('layouts.app')
@section('title', 'My students')
@section('heading', 'My students')
@section('subheading', 'Students enrolled in the sections you teach')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <x-stat-card label="My sections" :value="$sections->count()" icon="bi-journal-bookmark" variant="primary" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Students taught" :value="$students->count()" icon="bi-people" variant="info" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Below attendance rule"
                     :value="collect($insights)->where('attendance_at_risk', true)->count()"
                     icon="bi-exclamation-triangle" variant="danger" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Open complaints"
                     :value="collect($insights)->sum('open_complaints')"
                     icon="bi-chat-left-text" variant="warning" />
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <x-page-card title="Sections I teach">
            @if ($sections->isEmpty())
                <x-empty-state icon="bi-journal-x" title="No sections assigned" />
            @else
                <div class="list-group list-group-flush">
                    @foreach ($sections as $section)
                        <div class="list-group-item d-flex align-items-center gap-2">
                            <div class="flex-grow-1">
                                <div class="small fw-semibold">{{ $section->course->code }}-{{ $section->section_code }}</div>
                                <div class="text-secondary" style="font-size:.78rem">{{ $section->course->title }}</div>
                            </div>
                            <span class="badge text-bg-light border">{{ $section->students_count }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-page-card>
    </div>

    <div class="col-lg-8">
        <x-page-card title="Student directory"
                     subtitle="Profiles are read from the shared MIS record, not held separately">
            @if ($students->isEmpty())
                <x-empty-state icon="bi-people" title="No students enrolled yet" />
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Student</th><th>Program</th><th class="text-end">Attendance</th><th class="text-end">Files</th></tr></thead>
                        <tbody>
                        @foreach ($students as $student)
                            @php($i = $insights[$student->id] ?? null)
                            <tr class="{{ $i && $i['attendance_at_risk'] ? 'row-at-risk' : '' }}">
                                <td>
                                    <a href="{{ route('faculty.students.show', $student) }}" class="fw-semibold text-decoration-none">
                                        {{ $student->fullName() }}
                                    </a>
                                    <div class="small text-secondary">{{ $student->student_id_no }}</div>
                                </td>
                                <td class="small text-secondary">{{ $student->program->code }}</td>
                                <td class="text-end" style="width:170px">
                                    @if ($i && $i['sessions_recorded'] > 0)
                                        <x-meter :percentage="$i['attendance_percentage']" :threshold="config('mis.attendance_min_percentage')" />
                                    @else
                                        <span class="small text-body-tertiary">-</span>
                                    @endif
                                </td>
                                <td class="text-end small">{{ $i['submissions'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-page-card>
    </div>
</div>
@endsection
