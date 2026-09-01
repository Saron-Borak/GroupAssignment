@extends('layouts.app')
@section('title', 'Mark register')
@section('heading', 'Register · '.$session->classSection->course->code.'-'.$session->classSection->section_code)
@section('subheading', $session->session_date->format('l, d F Y').' · '.$session->timeRange())

@section('toolbar')
    @if ($session->status === App\Enums\SessionStatus::Open)
        <a href="{{ route('faculty.attendance.qr', $session) }}" class="btn btn-success btn-sm no-print">
            <i class="bi bi-broadcast me-1"></i>Open kiosk
        </a>
        <form method="POST" action="{{ route('faculty.attendance.close', $session) }}" class="d-inline ms-2 no-print">
            @csrf @method('PUT')
            <button class="btn btn-outline-danger btn-sm">
                <i class="bi bi-lock me-1"></i>Close session
            </button>
        </form>
    @elseif ($session->status !== App\Enums\SessionStatus::Closed)
        <form method="POST" action="{{ route('faculty.attendance.open', $session) }}" class="d-inline no-print">
            @csrf @method('PUT')
            <button class="btn btn-success btn-sm">
                <i class="bi bi-qr-code me-1"></i>Open for check-in
            </button>
        </form>
    @endif
    <a href="{{ route('faculty.attendance.index') }}" class="btn btn-outline-secondary btn-sm no-print ms-2">
        <i class="bi bi-arrow-left me-1"></i>All sessions
    </a>
@endsection

@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <x-status-badge :status="$session->status" />
    @if ($session->status === App\Enums\SessionStatus::Open)
        <span class="small text-secondary">
            Students can check themselves in. Close the session to mark everyone who did not.
        </span>
    @elseif ($session->status === App\Enums\SessionStatus::Closed)
        <span class="small text-secondary">
            Closed {{ $session->closed_at?->format('d M Y, H:i') }}. Saving again corrects the existing marks.
        </span>
    @endif
</div>
@if ($roster->isEmpty())
    <x-page-card>
        <x-empty-state icon="bi-person-plus" title="Nobody is enrolled in this class"
                       message="The registry manages the roster from the student profile module." />
    </x-page-card>
@else
<form method="POST" action="{{ route('faculty.attendance.mark.store', $session) }}" id="registerForm">
    @csrf @method('PUT')

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <div class="me-auto">
                <div class="fw-semibold">{{ $roster->count() }} students on the roster</div>
                <div class="small text-secondary">Names are read from the shared profile, not a copy held by this module.</div>
            </div>
            <div class="btn-group btn-group-sm no-print" role="group" aria-label="Mark everyone">
                <span class="btn btn-light disabled border">Mark all</span>
                @foreach ($statuses as $status)
                    <button type="button" class="btn btn-outline-secondary mark-all" data-status="{{ $status->value }}">
                        {{ $status->label() }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <x-page-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th style="width:1%">#</th><th>Student</th><th style="width:1%" class="text-nowrap">Attendance</th></tr></thead>
                <tbody>
                @foreach ($roster as $index => $enrollment)
                    @php($student = $enrollment->student)
                    @php($record = $existing->get($student->id))
                    @php($current = old('marks.'.$student->id, $record?->status->value ?? 'present'))
                    <tr>
                        <td class="text-secondary small">{{ $index + 1 }}</td>
                        <td>
                            <div class="fw-semibold">{{ $student->fullName() }}</div>
                            <div class="small text-secondary">{{ $student->student_id_no }}</div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm text-nowrap" role="group"
                                 aria-label="Attendance for {{ $student->fullName() }}">
                                @foreach ($statuses as $status)
                                    @php($id = 'm'.$student->id.$status->value)
                                    <input type="radio" class="btn-check" name="marks[{{ $student->id }}]"
                                           id="{{ $id }}" value="{{ $status->value }}" @checked($current === $status->value)>
                                    <label class="btn btn-outline-{{ str_replace('text-bg-', '', $status->badgeClass()) }}" for="{{ $id }}">
                                        {{ $status->label() }}
                                    </label>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex gap-2 no-print">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save register</button>
            <a href="{{ route('faculty.attendance.index') }}" class="btn btn-outline-secondary ms-auto">Cancel</a>
        </div>
    </x-page-card>
</form>

@push('scripts')
<script>
    document.querySelectorAll('.mark-all').forEach(function (button) {
        button.addEventListener('click', function () {
            const status = this.dataset.status;
            document.querySelectorAll('#registerForm input[type=radio][value="' + status + '"]')
                .forEach(radio => { radio.checked = true; });
        });
    });
</script>
@endpush
@endif
@endsection
