@extends('layouts.app')
@section('title', 'Profile completeness')
@section('heading', 'Profile completeness')
@section('subheading', 'Which records are still missing required information')

@section('toolbar')
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print"><i class="bi bi-printer me-1"></i>Print</button>
@endsection

@section('content')
<form method="GET" class="row g-2 align-items-end mb-3 no-print">
    <div class="col-sm-5 col-lg-3">
        <label for="program_id" class="form-label small mb-1">Program</label>
        <select id="program_id" name="program_id" class="form-select form-select-sm">
            <option value="">All programs</option>
            @foreach ($programs as $option)
                <option value="{{ $option->id }}" @selected(request('program_id') == $option->id)>{{ $option->code }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Apply</button></div>
    <div class="col-auto ms-auto small text-secondary">Least complete first</div>
</form>

<x-page-card>
    @if ($students->isEmpty())
        <x-empty-state icon="bi-clipboard-check" title="No active students" />
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Student</th><th>Program</th>
                        <th class="text-center">Personal</th><th class="text-center">Contact</th>
                        <th class="text-center">Address</th><th class="text-center">Guardian</th><th class="text-center">Photo</th>
                        <th style="width:150px">Complete</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($students as $student)
                    @php($checks = $student->completeness())
                    <tr class="{{ $student->completenessPercentage() < 60 ? 'row-at-risk' : '' }}">
                        <td>
                            <a href="{{ route('admin.students.show', $student) }}" class="fw-semibold text-decoration-none">{{ $student->fullName() }}</a>
                            <div class="small text-secondary">{{ $student->student_id_no }}</div>
                        </td>
                        <td class="small text-secondary">{{ $student->program->code }}</td>
                        @foreach ($checks as $done)
                            <td class="text-center">
                                <i class="bi {{ $done ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-danger opacity-50' }}"></i>
                            </td>
                        @endforeach
                        <td><x-meter :percentage="$student->completenessPercentage()" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-page-card>
@endsection
