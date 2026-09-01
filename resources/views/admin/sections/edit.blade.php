@extends('layouts.app')
@section('title', 'Edit '.$section->label())
@section('heading', 'Edit '.$section->label())
@section('subheading', $section->course->title)

@section('content')
<div class="row"><div class="col-lg-9">
    <x-page-card>
        <form method="POST" action="{{ route('admin.sections.update', $section) }}">
            @method('PUT')
            @include('admin.sections._form')
            <div class="card-footer bg-white d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save changes</button>
                <a href="{{ route('admin.sections.show', $section) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>

    {{-- A separate form: nesting one inside the edit form would be invalid HTML. --}}
    <form method="POST" action="{{ route('admin.sections.destroy', $section) }}" class="mt-3 text-end"
          onsubmit="return confirm('Delete this class section? Only possible while it has no history.')">
        @csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-danger">
            <i class="bi bi-trash me-1"></i>Delete this section
        </button>
    </form>
</div></div>
@endsection
