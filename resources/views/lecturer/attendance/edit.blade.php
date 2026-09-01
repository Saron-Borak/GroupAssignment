@extends('layouts.app')
@section('title', 'Edit session')
@section('heading', 'Edit session')
@section('subheading', $session->classSection->label().' — '.$session->classSection->course->title)

@section('content')
<div class="row">
    <div class="col-lg-7">
        <x-page-card title="Session details">
            <div class="card-body">
                <form method="POST" action="{{ route('faculty.attendance.update', $session) }}" class="d-grid gap-3">
                    @csrf @method('PUT')

                    <div>
                        <label for="session_date" class="form-label">Date</label>
                        <input type="date" id="session_date" name="session_date" required
                               class="form-control @error('session_date') is-invalid @enderror"
                               value="{{ old('session_date', $session->session_date->toDateString()) }}">
                        @error('session_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="start_time" class="form-label">Starts</label>
                            <input type="time" id="start_time" name="start_time" required
                                   class="form-control @error('start_time') is-invalid @enderror"
                                   value="{{ old('start_time', substr($session->start_time, 0, 5)) }}">
                            @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="end_time" class="form-label">Ends</label>
                            <input type="time" id="end_time" name="end_time" required
                                   class="form-control @error('end_time') is-invalid @enderror"
                                   value="{{ old('end_time', substr($session->end_time, 0, 5)) }}">
                            @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="topic" class="form-label">Topic <span class="text-secondary fw-normal">(optional)</span></label>
                        <input id="topic" name="topic" maxlength="255"
                               class="form-control @error('topic') is-invalid @enderror"
                               value="{{ old('topic', $session->topic) }}">
                        @error('topic')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="late_after_minutes" class="form-label">Late after</label>
                        <div class="input-group" style="max-width:220px">
                            <input type="number" id="late_after_minutes" name="late_after_minutes"
                                   min="0" max="120" class="form-control @error('late_after_minutes') is-invalid @enderror"
                                   value="{{ old('late_after_minutes', $session->late_after_minutes) }}">
                            <span class="input-group-text">minutes</span>
                        </div>
                        <div class="form-text">
                            A student checking in after this many minutes past the start is recorded as late.
                        </div>
                        @error('late_after_minutes')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary">Save changes</button>
                        <a href="{{ route('faculty.attendance.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </x-page-card>
    </div>

    <div class="col-lg-5">
        <x-page-card title="Current state">
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5 text-secondary fw-normal">Status</dt>
                    <dd class="col-7"><x-status-badge :status="$session->status" /></dd>

                    <dt class="col-5 text-secondary fw-normal">Opened</dt>
                    <dd class="col-7">{{ $session->opened_at?->format('d M Y, H:i') ?? '—' }}</dd>

                    <dt class="col-5 text-secondary fw-normal">Closed</dt>
                    <dd class="col-7">{{ $session->closed_at?->format('d M Y, H:i') ?? '—' }}</dd>

                    <dt class="col-5 text-secondary fw-normal">Marks recorded</dt>
                    <dd class="col-7">{{ $session->records()->count() }}</dd>
                </dl>
            </div>
        </x-page-card>
    </div>
</div>
@endsection
