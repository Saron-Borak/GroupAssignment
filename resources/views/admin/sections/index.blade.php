@extends('layouts.app')
@section('title', 'Class sections')
@section('heading', 'Class sections')
@section('subheading', 'One delivery of a course in a term. Its roster is the list both modules read.')

@section('toolbar')
    <a href="{{ route('admin.sections.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New section
    </a>
@endsection

@section('content')
<x-page-card>
    <div class="card-header bg-white py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-sm-5 col-lg-4">
                <select name="course_id" class="form-select form-select-sm">
                    <option value="">Every course</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>
                            {{ $course->code }} — {{ $course->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3">
                <select name="term" class="form-select form-select-sm">
                    <option value="">Every term</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term }}" @selected(request('term') === $term)>{{ $term }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-outline-secondary">Filter</button>
                @if (request()->hasAny(['course_id', 'term']))
                    <a href="{{ route('admin.sections.index') }}" class="btn btn-sm btn-link">Clear</a>
                @endif
            </div>
        </form>
    </div>

    @if ($sections->isEmpty())
        <x-empty-state icon="bi-people" title="No class sections yet"
                       message="A section is what students enrol onto, attendance is taken against, and assignments are issued to.">
            <a href="{{ route('admin.sections.create') }}" class="btn btn-sm btn-primary">Create a section</a>
        </x-empty-state>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>Section</th><th>Lecturer</th><th>Term</th><th>Room</th>
                        <th class="text-end">Roster</th><th class="text-end">Sessions</th>
                        <th class="text-end">Assignments</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($sections as $section)
                    <tr>
                        <td>
                            <a href="{{ route('admin.sections.show', $section) }}" class="fw-semibold text-decoration-none">
                                {{ $section->course->code }}-{{ $section->section_code }}
                            </a>
                            <div class="small text-secondary">{{ $section->course->title }}</div>
                        </td>
                        <td class="small text-secondary">{{ $section->lecturer->name }}</td>
                        <td class="small">{{ $section->term }}</td>
                        <td class="small text-secondary">{{ $section->room ?: '—' }}</td>
                        <td class="text-end"><span class="badge text-bg-primary">{{ $section->roster_count }}</span></td>
                        <td class="text-end"><span class="badge text-bg-light border">{{ $section->sessions_count }}</span></td>
                        <td class="text-end"><span class="badge text-bg-light border">{{ $section->assignments_count }}</span></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.sections.show', $section) }}" class="btn btn-sm btn-outline-secondary">
                                Manage
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $sections->links() }}</div>
    @endif
</x-page-card>
@endsection
