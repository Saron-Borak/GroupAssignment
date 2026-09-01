@extends('layouts.app')
@section('title', 'Edit program')
@section('heading', 'Edit '.$program->code)
@section('content')
<div class="row"><div class="col-lg-9">
    <x-page-card>
        <form method="POST" action="{{ route('admin.programs.update', $program) }}">
            @csrf @method('PUT')
            <div class="card-body">@include('admin.programs._form')</div>
            <div class="card-footer bg-white d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save changes</button>
                <a href="{{ route('admin.programs.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>
</div></div>
@endsection
