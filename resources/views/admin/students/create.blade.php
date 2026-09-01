@extends('layouts.app')
@section('title', 'Add a student')
@section('heading', 'Add a student profile')
@section('subheading', 'Creates the record that every other module in the MIS will refer to')

@section('content')
<div class="row"><div class="col-xl-11">
    <x-page-card>
        <form method="POST" action="{{ route('admin.students.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body">@include('admin.students._form')</div>
            <div class="card-footer bg-white d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create profile</button>
                <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>
</div></div>
@endsection
