@extends('layouts.app')
@section('title', 'My profile')
@section('heading', 'My student profile')
@section('subheading', $student->student_id_no.' · '.$student->program->name)

@section('toolbar')
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print"><i class="bi bi-printer me-1"></i>Print</button>
@endsection

@section('content')
@if ($insight['attendance']['at_risk'])
    <div class="alert alert-danger d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
        <div>
            <strong>Your attendance is {{ number_format($insight['attendance']['percentage'], 1) }}%.</strong>
            <div class="small">
                The university requires at least {{ config('mis.attendance_min_percentage') }}%.
                Please speak to your faculty office.
            </div>
        </div>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-3 align-items-start">
        <x-student-photo :student="$student" />
        <div class="flex-grow-1">
            <h2 class="h4 mb-2">{{ $student->fullName() }}</h2>
            <dl class="row mb-0 small">
                <dt class="col-sm-4 col-lg-3 text-secondary fw-normal">Student number</dt>
                <dd class="col-sm-8 col-lg-9 font-monospace">{{ $student->student_id_no }}</dd>
                <dt class="col-sm-4 col-lg-3 text-secondary fw-normal">Program</dt>
                <dd class="col-sm-8 col-lg-9">{{ $student->program->name }} ({{ $student->program->department->name }})</dd>
                <dt class="col-sm-4 col-lg-3 text-secondary fw-normal">Date of birth</dt>
                <dd class="col-sm-8 col-lg-9">{{ $student->date_of_birth->format('d M Y') }}</dd>
                <dt class="col-sm-4 col-lg-3 text-secondary fw-normal">Email</dt><dd class="col-sm-8 col-lg-9">{{ $student->email }}</dd>
                <dt class="col-sm-4 col-lg-3 text-secondary fw-normal">Phone</dt><dd class="col-sm-8 col-lg-9">{{ $student->phone ?: 'Not recorded' }}</dd>
                <dt class="col-sm-4 col-lg-3 text-secondary fw-normal">Status</dt>
                <dd class="col-sm-8 col-lg-9"><x-status-badge :status="$student->status" /></dd>
            </dl>
            <div class="alert alert-light border small mt-3 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Your profile is maintained by the registry. Contact them if any detail is wrong.
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    @include('admin.students._modules')
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <x-page-card title="Addresses on file" class="mb-3">
            @if ($student->addresses->isEmpty())
                <x-empty-state icon="bi-geo-alt" title="No address recorded" />
            @else
                <div class="list-group list-group-flush">
                    @foreach ($student->addresses as $address)
                        <div class="list-group-item">
                            <span class="badge text-bg-light border mb-1">{{ $address->addressType->name }}</span>
                            <div class="small">{{ $address->oneLine() }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-page-card>

        <x-page-card title="Change my password">
            <form method="POST" action="{{ route('student.profile.password') }}">
                @csrf @method('PUT')
                <div class="card-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current password</label>
                        <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                               class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">New password</label>
                        <input type="password" id="password" name="password" autocomplete="new-password" minlength="8"
                               class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="form-label">Confirm new password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               autocomplete="new-password" minlength="8" class="form-control" required>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <button class="btn btn-primary"><i class="bi bi-key me-1"></i>Change password</button>
                </div>
            </form>
        </x-page-card>
    </div>

    <div class="col-lg-7">
        <x-page-card title="My recent attendance" class="mb-3">
            @if ($recentAttendance->isEmpty())
                <x-empty-state icon="bi-calendar-x" title="No attendance recorded yet" />
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead><tr><th>Date</th><th>Course</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($recentAttendance as $record)
                            <tr>
                                <td class="small text-nowrap">{{ $record->session->session_date->format('d M Y') }}</td>
                                <td class="small">{{ $record->session->classSection->course->code }}</td>
                                <td><x-status-badge :status="$record->status" /></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-page-card>

        <x-page-card title="My submissions" class="mb-3">
            @if ($recentSubmissions->isEmpty())
                <x-empty-state icon="bi-cloud-slash" title="No submissions yet" />
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead><tr><th>Assignment</th><th>Submitted</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($recentSubmissions as $submission)
                            <tr>
                                <td class="small">{{ $submission->assignment->title }}</td>
                                <td class="small text-nowrap">{{ $submission->submitted_at->format('d M, H:i') }}</td>
                                <td><x-status-badge :status="$submission->status" /></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-page-card>

        <x-page-card title="My complaints">
            @if ($complaints->isEmpty())
                <x-empty-state icon="bi-emoji-smile" title="No complaints raised" />
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead><tr><th>Reference</th><th>Title</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($complaints as $complaint)
                            <tr>
                                <td class="small font-monospace">{{ $complaint->reference }}</td>
                                <td class="small">{{ $complaint->title }}</td>
                                <td><x-status-badge :status="$complaint->status" /></td>
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
