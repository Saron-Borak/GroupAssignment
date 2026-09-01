@extends('layouts.app')
@section('title', 'Courses')
@section('heading', 'Course catalogue')
@section('subheading', 'One catalogue for both modules — attendance called these courses, submission called them subjects')

@section('toolbar')
    <a href="{{ route('admin.courses.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New course
    </a>
@endsection

@section('content')
<x-page-card>
    <div class="card-header bg-white py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-sm-5 col-lg-4">
                <input name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                       placeholder="Search by code or title">
            </div>
            <div class="col-sm-4 col-lg-3">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">Every department</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-outline-secondary">Filter</button>
                @if (request()->hasAny(['q', 'department_id']))
                    <a href="{{ route('admin.courses.index') }}" class="btn btn-sm btn-link">Clear</a>
                @endif
            </div>
        </form>
    </div>

    @if ($courses->isEmpty())
        <x-empty-state icon="bi-journal-x" title="No courses yet"
                       message="Add a course, then create the class sections that deliver it.">
            <a href="{{ route('admin.courses.create') }}" class="btn btn-sm btn-primary">Add a course</a>
        </x-empty-state>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>Code</th><th>Title</th><th>Department</th>
                        <th class="text-end">Credits</th><th class="text-end">Sections</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($courses as $course)
                    <tr>
                        <td class="fw-semibold">{{ $course->code }}</td>
                        <td>{{ $course->title }}</td>
                        <td class="small text-secondary">{{ $course->department->name }}</td>
                        <td class="text-end">{{ $course->credit_hours }}</td>
                        <td class="text-end">
                            <span class="badge text-bg-light border">{{ $course->class_sections_count }}</span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if (! $course->class_sections_count)
                                <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" class="d-inline"
                                      onsubmit="return confirm('Remove {{ $course->code }} from the catalogue?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $courses->links() }}</div>
    @endif
</x-page-card>
@endsection
