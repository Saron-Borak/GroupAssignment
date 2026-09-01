@extends('layouts.app')
@section('title', 'New class section')
@section('heading', 'Create a class section')
@section('subheading', 'One delivery of a course, taught by one lecturer in one term')

@section('content')
<div class="row"><div class="col-lg-9">
    <x-page-card>
        <form method="POST" action="{{ route('admin.sections.store') }}">
            @include('admin.sections._form')
            <div class="card-footer bg-white d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create section</button>
                <a href="{{ route('admin.sections.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>
</div></div>
@endsection
