@extends('layouts.app')
@section('title', 'My complaints')
@section('heading', 'Complaint module')
@section('subheading', 'Raise an issue with the registry and follow its progress')

@section('content')
<div class="row g-3">
    <div class="col-lg-5">
        <x-page-card title="Raise a complaint">
            <form method="POST" action="{{ route('student.complaints.store') }}">
                @csrf
                <div class="card-body">
                    <div class="mb-3">
                        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                        <select id="category" name="category" class="form-select @error('category') is-invalid @enderror" required>
                            @foreach ($categories as $case)
                                <option value="{{ $case->value }}" @selected(old('category') === $case->value)>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required placeholder="Air conditioning not working in B-204">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="description" class="form-label">Details <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" rows="5"
                                  class="form-control @error('description') is-invalid @enderror"
                                  required placeholder="Describe the issue so it can be investigated">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <button class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>Submit complaint</button>
                </div>
            </form>
        </x-page-card>
    </div>

    <div class="col-lg-7">
        <x-page-card title="My cases">
            @if ($complaints->isEmpty())
                <x-empty-state icon="bi-emoji-smile" title="No complaints raised"
                               message="Anything you submit will appear here with its reference and status." />
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Reference</th><th>Subject</th><th>Category</th><th>Status</th><th>Raised</th></tr></thead>
                        <tbody>
                        @foreach ($complaints as $complaint)
                            <tr>
                                <td class="font-monospace small">
                                    <a href="{{ route('student.complaints.show', $complaint) }}" class="text-decoration-none">
                                        {{ $complaint->reference }}
                                    </a>
                                </td>
                                <td class="small">{{ $complaint->title }}</td>
                                <td class="small text-secondary">{{ $complaint->category->label() }}</td>
                                <td><x-status-badge :status="$complaint->status" /></td>
                                <td class="small text-secondary text-nowrap">{{ $complaint->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white">{{ $complaints->links() }}</div>
            @endif
        </x-page-card>
    </div>
</div>
@endsection
