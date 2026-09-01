@extends('layouts.app')
@section('title', $student->fullName())
@section('heading', $student->fullName())
@section('subheading', $student->student_id_no.' · '.$student->program->name)

@section('toolbar')
    <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-primary btn-sm no-print">
        <i class="bi bi-pencil me-1"></i>Edit profile
    </a>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm ms-2 no-print">
        <i class="bi bi-printer me-1"></i>Print
    </button>
@endsection

@section('content')
@include('admin.students._identity')

<div class="row g-3 mb-3">
    @include('admin.students._modules')
</div>

<div class="row g-3">
    <div class="col-lg-5">
        @include('admin.students._details')
    </div>
    <div class="col-lg-7">
        @include('admin.students._activity')
    </div>
</div>
@endsection
