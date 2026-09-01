@extends('layouts.app')
@section('title', $assignment->title)
@section('heading', $assignment->title)
@section('subheading', $assignment->classSection->label().' — '.$assignment->classSection->course->title)

@section('toolbar')
    <a href="{{ route('student.assignments.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>All assignments
    </a>
@endsection

@section('content')
@php($closed = now()->greaterThan($assignment->deadline))

<div class="row g-4">
    <div class="col-lg-7">
        <x-page-card title="Instructions">
            <div class="card-body">
                @if ($assignment->description)
                    <p class="mb-0" style="white-space: pre-line">{{ $assignment->description }}</p>
                @else
                    <p class="text-secondary mb-0">Your lecturer did not add written instructions.</p>
                @endif
            </div>
        </x-page-card>

        <x-page-card title="{{ $submission ? 'Replace your submission' : 'Submit your work' }}" class="mt-4">
            <div class="card-body">
                @if ($closed && ! $submission)
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        The deadline has passed. You can still upload, but it will be recorded as late.
                    </div>
                @endif

                <form method="POST" action="{{ route('student.assignments.submit', $assignment) }}"
                      enctype="multipart/form-data" class="d-grid gap-3">
                    @csrf
                    <div>
                        <label for="file" class="form-label">Your file</label>
                        <input type="file" id="file" name="file" required
                               class="form-control @error('file') is-invalid @enderror">
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">PDF, Word, ZIP or text, up to 10 MB.</div>
                    </div>
                    <button class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i>{{ $submission ? 'Replace submission' : 'Submit' }}
                    </button>
                </form>

                @if ($submission)
                    <div class="alert alert-light border small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Replacing deletes the file you sent before, and the new one is judged against
                        the deadline at the moment it arrives.
                    </div>
                @endif
            </div>
        </x-page-card>
    </div>

    <div class="col-lg-5">
        <x-page-card title="This assignment">
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5 fw-normal text-secondary">Class</dt>
                    <dd class="col-7">{{ $assignment->classSection->label() }}</dd>

                    <dt class="col-5 fw-normal text-secondary">Lecturer</dt>
                    <dd class="col-7">{{ $assignment->classSection->lecturer->name }}</dd>

                    <dt class="col-5 fw-normal text-secondary">Deadline</dt>
                    <dd class="col-7">
                        {{ $assignment->deadline->format('D, d M Y, H:i') }}
                        <span class="badge {{ $closed ? 'text-bg-secondary' : 'text-bg-success' }} ms-1">
                            {{ $closed ? 'Closed' : 'Open' }}
                        </span>
                    </dd>
                </dl>
            </div>
        </x-page-card>

        <x-page-card title="Your submission" class="mt-4">
            @if (! $submission)
                <x-empty-state icon="bi-file-earmark-x" title="Nothing submitted yet"
                               message="Upload a file to record your submission." />
            @else
                <div class="card-body small">
                    <dl class="row mb-3">
                        <dt class="col-5 fw-normal text-secondary">Status</dt>
                        <dd class="col-7"><x-status-badge :status="$submission->status" /></dd>

                        <dt class="col-5 fw-normal text-secondary">Received</dt>
                        <dd class="col-7">{{ $submission->submitted_at->format('D, d M Y, H:i') }}</dd>

                        <dt class="col-5 fw-normal text-secondary">File</dt>
                        <dd class="col-7 text-break">{{ $submission->original_filename }}</dd>
                    </dl>

                    <a href="{{ route('student.assignments.download', $assignment) }}"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-download me-1"></i>Download what I sent
                    </a>

                    <div class="text-secondary mt-3 mb-0">
                        On time or late was decided by the university's clock when the file arrived,
                        not by your device.
                    </div>
                </div>
            @endif
        </x-page-card>
    </div>
</div>
@endsection
