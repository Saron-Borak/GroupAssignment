@extends('layouts.app')
@section('title', 'Assignments')
@section('heading', 'Project submission module')
@section('subheading', 'Assignments issued to the classes you teach')

@section('toolbar')
    <a href="{{ route('faculty.assignments.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New assignment
    </a>
@endsection

@section('content')
<x-page-card>
    @if ($assignments->isEmpty())
        <x-empty-state icon="bi-journal-x" title="No assignments yet"
                       message="Issue an assignment to one of your classes.">
            <a href="{{ route('faculty.assignments.create') }}" class="btn btn-sm btn-primary">Create one</a>
        </x-empty-state>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Assignment</th><th>Class</th><th>Deadline</th><th class="text-end">Submitted</th><th class="text-end">Late</th><th style="width:1%"></th></tr></thead>
                <tbody>
                @foreach ($assignments as $assignment)
                    <tr>
                        <td>
                            <a href="{{ route('faculty.assignments.show', $assignment) }}" class="fw-semibold text-decoration-none">
                                {{ $assignment->title }}
                            </a>
                        </td>
                        <td class="small text-secondary">
                            {{ $assignment->classSection->course->code }}-{{ $assignment->classSection->section_code }}
                        </td>
                        <td class="small text-nowrap">
                            {{ $assignment->deadline->format('d M Y, H:i') }}
                            @if ($assignment->isOverdue())
                                <span class="badge text-bg-secondary ms-1">Closed</span>
                            @else
                                <span class="badge text-bg-success ms-1">Open</span>
                            @endif
                        </td>
                        <td class="text-end">{{ $assignment->submissions_count }}</td>
                        <td class="text-end">
                            @if ($assignment->late_count)
                                <span class="text-warning-emphasis fw-semibold">{{ $assignment->late_count }}</span>
                            @else
                                <span class="text-body-tertiary">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('faculty.assignments.show', $assignment) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $assignments->links() }}</div>
    @endif
</x-page-card>
@endsection
