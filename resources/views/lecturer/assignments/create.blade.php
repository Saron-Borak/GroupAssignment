@extends('layouts.app')
@section('title', 'New assignment')
@section('heading', 'Issue an assignment')
@section('subheading', 'Every student enrolled in the chosen class will see it')

@section('content')
<div class="row"><div class="col-lg-7">
    <x-page-card>
        <form method="POST" action="{{ route('faculty.assignments.store') }}">
            @csrf
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="class_section_id" class="form-label">Class <span class="text-danger">*</span></label>
                        <select id="class_section_id" name="class_section_id"
                                class="form-select @error('class_section_id') is-invalid @enderror" required>
                            <option value="">Choose a class...</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" @selected(old('class_section_id') == $section->id)>
                                    {{ $section->course->code }}-{{ $section->section_code }} - {{ $section->course->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('class_section_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required placeholder="Assignment 1: Database Design">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Instructions</label>
                        <textarea id="description" name="description" rows="4"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="What the students must submit">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="deadline" class="form-label">Deadline <span class="text-danger">*</span></label>
                        <input type="datetime-local" id="deadline" name="deadline"
                               class="form-control @error('deadline') is-invalid @enderror"
                               value="{{ old('deadline', now()->addWeek()->format('Y-m-d\TH:i')) }}" required>
                        <div class="form-text">Anything submitted after this moment is recorded as late.</div>
                        @error('deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Issue assignment</button>
                <a href="{{ route('faculty.assignments.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>
</div></div>
@endsection
