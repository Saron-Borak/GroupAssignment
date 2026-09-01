@extends('layouts.app')
@section('title', 'New session')
@section('heading', 'New class session')
@section('subheading', 'A meeting of one of your class sections')

@section('content')
<div class="row"><div class="col-lg-7">
    <x-page-card>
        <form method="POST" action="{{ route('faculty.attendance.store') }}">
            @csrf
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="class_section_id" class="form-label">Class <span class="text-danger">*</span></label>
                        <select id="class_section_id" name="class_section_id"
                                class="form-select @error('class_section_id') is-invalid @enderror" required>
                            <option value="">Choose a class...</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" @selected(old('class_section_id', $selected) == $section->id)>
                                    {{ $section->course->code }}-{{ $section->section_code }} - {{ $section->course->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('class_section_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label for="session_date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" id="session_date" name="session_date"
                               class="form-control @error('session_date') is-invalid @enderror"
                               value="{{ old('session_date', now()->toDateString()) }}" required>
                        @error('session_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="start_time" class="form-label">Start <span class="text-danger">*</span></label>
                        <input type="time" id="start_time" name="start_time"
                               class="form-control @error('start_time') is-invalid @enderror"
                               value="{{ old('start_time', '08:00') }}" required>
                        @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="end_time" class="form-label">End <span class="text-danger">*</span></label>
                        <input type="time" id="end_time" name="end_time"
                               class="form-control @error('end_time') is-invalid @enderror"
                               value="{{ old('end_time', '10:00') }}" required>
                        @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="topic" class="form-label">Topic</label>
                        <input type="text" id="topic" name="topic" class="form-control @error('topic') is-invalid @enderror"
                               value="{{ old('topic') }}" placeholder="Optional">
                        @error('topic')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label for="late_after_minutes" class="form-label">Late after</label>
                        <div class="input-group">
                            <input type="number" id="late_after_minutes" name="late_after_minutes" min="0" max="120"
                                   class="form-control @error('late_after_minutes') is-invalid @enderror"
                                   value="{{ old('late_after_minutes', config('mis.late_after_minutes')) }}">
                            <span class="input-group-text">minutes</span>
                        </div>
                        @error('late_after_minutes')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-7">
                        <label for="status" class="form-label">Start it as</label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                            @foreach ($statuses as $option)
                                <option value="{{ $option->value }}" @selected(old('status', 'scheduled') === $option->value)>
                                    {{ $option->label() }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            Choose <strong>Open</strong> to start QR check-in straight away, or
                            <strong>Scheduled</strong> to mark the register by hand later.
                        </div>
                        @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create and mark register</button>
                <a href="{{ route('faculty.attendance.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>
</div></div>
@endsection
