@extends('layouts.app')
@section('title', 'New course')
@section('heading', 'Add a course')
@section('subheading', 'A taught unit in the shared catalogue')

@section('content')
<div class="row"><div class="col-lg-8">
    <x-page-card>
        <form method="POST" action="{{ route('admin.courses.store') }}">
            @include('admin.courses._form')
            <div class="card-footer bg-white d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Add course</button>
                <a href="{{ route('admin.courses.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>
</div></div>
@endsection
