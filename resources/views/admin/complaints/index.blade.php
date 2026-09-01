@extends('layouts.app')
@section('title', 'Complaints')
@section('heading', 'Complaint module')
@section('subheading', 'Cases raised by students, resolved through the shared profile')

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <x-stat-card label="Pending" :value="$counts['pending']" icon="bi-hourglass" variant="danger" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="In progress" :value="$counts['in_progress']" icon="bi-arrow-repeat" variant="warning" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Resolved" :value="$counts['resolved']" icon="bi-check2-circle" variant="success" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Open total" :value="$counts['pending'] + $counts['in_progress']" icon="bi-inbox" variant="primary" />
    </div>
</div>

<x-page-card>
    <div class="card-header bg-white py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-sm-5 col-lg-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Reference or subject">
                </div>
            </div>
            <div class="col-sm-3 col-lg-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Any status</option>
                    @foreach (\App\Enums\ComplaintStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected(request('status') === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3 col-lg-2">
                <select name="category" class="form-select form-select-sm">
                    <option value="">Any category</option>
                    @foreach (\App\Enums\ComplaintCategory::cases() as $case)
                        <option value="{{ $case->value }}" @selected(request('category') === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-sm btn-primary">Filter</button></div>
            <div class="col-auto"><a href="{{ route('admin.complaints.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a></div>
        </form>
    </div>

    @if ($complaints->isEmpty())
        <x-empty-state icon="bi-emoji-smile" title="No complaints match"
                       message="Adjust the filters, or there is genuinely nothing outstanding." />
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Reference</th><th>Student</th><th>Subject</th><th>Category</th><th>Status</th><th>Raised</th><th style="width:1%"></th></tr></thead>
                <tbody>
                @foreach ($complaints as $complaint)
                    <tr class="{{ $complaint->status === \App\Enums\ComplaintStatus::Pending ? 'row-at-risk' : '' }}">
                        <td class="font-monospace small">{{ $complaint->reference }}</td>
                        <td>
                            <a href="{{ route('admin.students.show', $complaint->student) }}" class="fw-semibold text-decoration-none">
                                {{ $complaint->student->fullName() }}
                            </a>
                            <div class="small text-secondary">{{ $complaint->student->student_id_no }} · {{ $complaint->student->program->code }}</div>
                        </td>
                        <td class="small">{{ $complaint->title }}</td>
                        <td class="small text-secondary">{{ $complaint->category->label() }}</td>
                        <td><x-status-badge :status="$complaint->status" /></td>
                        <td class="small text-secondary text-nowrap">{{ $complaint->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.complaints.show', $complaint) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $complaints->links() }}</div>
    @endif
</x-page-card>
@endsection
