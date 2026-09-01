@extends('layouts.app')
@section('title', 'My assignments')
@section('heading', 'My assignments')
@section('subheading', 'Issued to the classes you are enrolled in')

@section('content')
@if ($assignments->isEmpty())
    <x-page-card>
        <x-empty-state icon="bi-journal-x" title="No assignments yet"
                       message="Assignments appear here once a lecturer issues one to a class you are enrolled in." />
    </x-page-card>
@else
    <div class="row g-3">
    @foreach ($assignments as $assignment)
        @php($submission = $assignment->studentSubmission)
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <div class="flex-grow-1">
                            <h2 class="h6 mb-1">{{ $assignment->title }}</h2>
                            <div class="small text-secondary">
                                {{ $assignment->classSection->course->code }} · due {{ $assignment->deadline->format('d M Y, H:i') }}
                            </div>
                        </div>
                        @if ($submission)
                            <x-status-badge :status="$submission->status" />
                        @elseif ($assignment->isOverdue())
                            <span class="badge text-bg-danger">Overdue</span>
                        @else
                            <span class="badge text-bg-secondary">Open</span>
                        @endif
                    </div>

                    @if ($assignment->description)
                        <p class="small text-secondary">{{ $assignment->description }}</p>
                    @endif

                    @if ($submission)
                        <div class="alert alert-light border small mb-2">
                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                            Submitted {{ $submission->submitted_at->format('d M Y, H:i') }} —
                            <a href="{{ route('student.assignments.download', $assignment) }}">{{ $submission->original_filename }}</a>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('student.assignments.submit', $assignment) }}"
                          enctype="multipart/form-data" class="row g-2 align-items-end">
                        @csrf
                        <div class="col">
                            <label for="file{{ $assignment->id }}" class="form-label small mb-1">
                                {{ $submission ? 'Replace your submission' : 'Upload your work' }}
                            </label>
                            <input type="file" id="file{{ $assignment->id }}" name="file"
                                   class="form-control form-control-sm @error('file') is-invalid @enderror" required>
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-sm btn-primary">
                                <i class="bi bi-cloud-arrow-up me-1"></i>{{ $submission ? 'Replace' : 'Submit' }}
                            </button>
                        </div>
                    </form>
                    <div class="form-text">PDF, Word, ZIP or text, up to 10 MB.</div>
                </div>
            </div>
        </div>
    @endforeach
    </div>
@endif
@endsection
