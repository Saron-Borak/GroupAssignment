@extends('layouts.app')
@section('title', 'Edit profile')
@section('heading', 'Edit '.$student->fullName())
@section('subheading', $student->student_id_no)

@section('content')
<div class="row"><div class="col-xl-11">
    <x-page-card>
        <form method="POST" action="{{ route('admin.students.update', $student) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="card-body">@include('admin.students._form')</div>
            <div class="card-footer bg-white d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save changes</button>
                <a href="{{ route('admin.students.show', $student) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>
</div></div>
@endsection
