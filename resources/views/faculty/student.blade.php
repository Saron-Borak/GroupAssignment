@extends('layouts.app')
@section('title', $student->fullName())
@section('heading', $student->fullName())
@section('subheading', $student->student_id_no.' · '.$student->program->name)

@section('toolbar')
    <a href="{{ route('faculty.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to my students
    </a>
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-3 align-items-start">
        <x-student-photo :student="$student" :size="96" />
        <div class="flex-grow-1">
            <dl class="row mb-0 small">
                <dt class="col-sm-3 text-secondary fw-normal">Program</dt><dd class="col-sm-9">{{ $student->program->name }}</dd>
                <dt class="col-sm-3 text-secondary fw-normal">Email</dt><dd class="col-sm-9">{{ $student->email }}</dd>
                <dt class="col-sm-3 text-secondary fw-normal">Phone</dt><dd class="col-sm-9">{{ $student->phone ?: 'Not recorded' }}</dd>
                <dt class="col-sm-3 text-secondary fw-normal">Status</dt><dd class="col-sm-9"><x-status-badge :status="$student->status" /></dd>
                <dt class="col-sm-3 text-secondary fw-normal">Emergency contact</dt>
                <dd class="col-sm-9">
                    @if ($contact = $student->emergencyContact())
                        {{ $contact->full_name }} ({{ $contact->relationship->label() }}) · {{ $contact->phone }}
                    @else
                        <span class="text-secondary">Not recorded</span>
                    @endif
                </dd>
            </dl>
        </div>
    </div>
</div>

<div class="row g-3">
    @include('admin.students._modules')
</div>
@endsection
