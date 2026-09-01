@extends('layouts.app')
@section('title', 'Edit '.$course->code)
@section('heading', 'Edit '.$course->code)
@section('subheading', $course->title)

@section('content')
<div class="row"><div class="col-lg-8">
    <x-page-card>
        <form method="POST" action="{{ route('admin.courses.update', $course) }}">
            @method('PUT')
            @include('admin.courses._form')
            <div class="card-footer bg-white d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save changes</button>
                <a href="{{ route('admin.courses.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>
</div></div>
@endsection
