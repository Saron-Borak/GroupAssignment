@extends('layouts.app')
@section('title', 'Edit assignment')
@section('heading', 'Edit assignment')
@section('subheading', $assignment->classSection->label().' — '.$assignment->classSection->course->title)

@section('content')
<div class="row"><div class="col-lg-7">
    <x-page-card>
        <form method="POST" action="{{ route('faculty.assignments.update', $assignment) }}">
            @csrf @method('PUT')
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" required
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $assignment->title) }}">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Instructions</label>
                        <textarea id="description" name="description" rows="4"
                                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $assignment->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="deadline" class="form-label">Deadline <span class="text-danger">*</span></label>
                        <input type="datetime-local" id="deadline" name="deadline" required
                               class="form-control @error('deadline') is-invalid @enderror"
                               value="{{ old('deadline', $assignment->deadline->format('Y-m-d\TH:i')) }}">
                        <div class="form-text">
                            Moving the deadline does not re-judge work already submitted: each submission
                            keeps the status the server gave it at the moment it arrived.
                        </div>
                        @error('deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save changes</button>
                <a href="{{ route('faculty.assignments.show', $assignment) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>

    @if ($assignment->submissions()->doesntExist())
        {{-- Separate form: nesting it inside the edit form would be invalid HTML. --}}
        <form method="POST" action="{{ route('faculty.assignments.destroy', $assignment) }}" class="mt-3 text-end"
              onsubmit="return confirm('Withdraw this assignment?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash me-1"></i>Withdraw this assignment
            </button>
        </form>
    @else
        <div class="alert alert-light border mt-3 small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Work has been submitted against this assignment, so it can no longer be withdrawn — deleting
            it would take the submitted files with it.
        </div>
    @endif
</div></div>
@endsection
