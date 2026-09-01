@extends('layouts.app')
@section('title', 'Students at risk')
@section('heading', 'Students below the attendance requirement')
@section('subheading', 'Worst first. A student below '.$threshold.'% can be barred from the examination.')

@section('toolbar')
    <a href="{{ route('admin.reports.at-risk.export', request()->only('program_id')) }}"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>CSV
    </a>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm ms-2 no-print">
        <i class="bi bi-printer me-1"></i>Print
    </button>
@endsection

@section('content')
<x-page-card>
    <div class="card-header bg-white py-3 no-print">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-sm-5 col-lg-4">
                <select name="program_id" class="form-select form-select-sm">
                    <option value="">Every programme</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program->id }}" @selected($programId == $program->id)>
                            {{ $program->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-outline-secondary">Filter</button>
                @if ($programId)
                    <a href="{{ route('admin.reports.at-risk') }}" class="btn btn-sm btn-link">Clear</a>
                @endif
            </div>
            <div class="col-auto ms-auto small text-secondary">
                {{ $rows->count() }} {{ Str::plural('enrolment', $rows->count()) }} below {{ $threshold }}%
            </div>
        </form>
    </div>

    @if ($rows->isEmpty())
        <x-empty-state icon="bi-emoji-smile" title="Nobody is below the requirement"
                       message="Every enrolled student is meeting the {{ $threshold }}% minimum." />
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Student</th><th>Programme</th><th>Class</th>
                        <th class="text-end">Attended</th><th class="text-end">Counted</th>
                        <th class="text-end">Absent</th><th style="min-width:170px">Attendance</th><th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $row->name }}</div>
                            <div class="small text-secondary">{{ $row->student_id_no }}</div>
                        </td>
                        <td class="small text-secondary">{{ $row->program_name }}</td>
                        <td class="small">{{ $row->course_code }}-{{ $row->section_code }}</td>
                        <td class="text-end">{{ $row->attended }}</td>
                        <td class="text-end">{{ $row->countable }}</td>
                        <td class="text-end text-danger fw-semibold">{{ $row->absent }}</td>
                        <td><x-meter :percentage="$row->percentage" :threshold="$threshold" /></td>
                        <td class="text-end no-print">
                            <a href="{{ route('admin.students.show', $row->student_id) }}"
                               class="btn btn-sm btn-outline-secondary">Profile</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white small text-secondary">
            One row per enrolment, not per student: somebody below the requirement in two classes
            appears twice, because each class is judged on its own.
        </div>
    @endif
</x-page-card>
@endsection
