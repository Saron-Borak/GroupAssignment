@extends('layouts.app')
@section('title', 'Departments')
@section('heading', 'Departments')
@section('subheading', 'The top-level academic divisions')

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <x-page-card title="All departments">
            @if ($departments->isEmpty())
                <x-empty-state icon="bi-building" title="No departments yet"
                               message="Create one before adding programs or courses." />
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Code</th><th>Name</th><th class="text-end">Programs</th><th class="text-end">Courses</th><th style="width:1%"></th></tr></thead>
                        <tbody>
                        @foreach ($departments as $department)
                            <tr>
                                <td><span class="badge text-bg-light border font-monospace">{{ $department->code }}</span></td>
                                <td class="fw-semibold">{{ $department->name }}</td>
                                <td class="text-end">{{ $department->programs_count }}</td>
                                <td class="text-end">{{ $department->courses_count }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.departments.destroy', $department) }}"
                                          onsubmit="return confirm('Delete this department?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white">{{ $departments->links() }}</div>
            @endif
        </x-page-card>
    </div>

    <div class="col-lg-4">
        <x-page-card title="Add a department">
            <form method="POST" action="{{ route('admin.departments.store') }}">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" id="code" name="code" maxlength="20"
                               class="form-control text-uppercase @error('code') is-invalid @enderror"
                               value="{{ old('code') }}" required placeholder="FCIT">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required placeholder="Faculty of Computing and IT">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Add department</button>
                </div>
            </form>
        </x-page-card>
    </div>
</div>
@endsection
