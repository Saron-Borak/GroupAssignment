@extends('layouts.app')
@section('title', $complaint->reference)
@section('heading', $complaint->title)
@section('subheading', $complaint->reference.' · '.$complaint->category->label().' · raised '.$complaint->created_at->format('d M Y'))

@section('toolbar')
    <a href="{{ route('admin.complaints.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>All complaints
    </a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <x-page-card title="What the student reported" class="mb-3">
            <div class="card-body">
                <p class="mb-0">{{ $complaint->description }}</p>
            </div>
        </x-page-card>

        <x-page-card title="Respond and update the case">
            <form method="POST" action="{{ route('admin.complaints.respond', $complaint) }}">
                @csrf @method('PUT')
                <div class="card-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach (\App\Enums\ComplaintStatus::cases() as $case)
                                <option value="{{ $case->value }}" @selected(old('status', $complaint->status->value) === $case->value)>
                                    {{ $case->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="admin_response" class="form-label">Response to the student</label>
                        <textarea id="admin_response" name="admin_response" rows="5"
                                  class="form-control @error('admin_response') is-invalid @enderror"
                                  placeholder="What has been done, or what happens next">{{ old('admin_response', $complaint->admin_response) }}</textarea>
                        <div class="form-text">The student sees this on their own copy of the case.</div>
                        @error('admin_response')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update case</button>
                </div>
            </form>
        </x-page-card>
    </div>

    <div class="col-lg-5">
        <x-page-card title="Who raised it" subtitle="Read from the shared student profile">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <x-student-photo :student="$complaint->student" :size="64" />
                    <div>
                        <a href="{{ route('admin.students.show', $complaint->student) }}" class="fw-semibold text-decoration-none">
                            {{ $complaint->student->fullName() }}
                        </a>
                        <div class="small text-secondary">{{ $complaint->student->student_id_no }}</div>
                        <div class="small text-secondary">{{ $complaint->student->program->name }}</div>
                    </div>
                </div>
                <dl class="row mb-0 small">
                    <dt class="col-5 text-secondary fw-normal">Email</dt><dd class="col-7">{{ $complaint->student->email }}</dd>
                    <dt class="col-5 text-secondary fw-normal">Phone</dt><dd class="col-7">{{ $complaint->student->phone ?: 'Not recorded' }}</dd>
                    <dt class="col-5 text-secondary fw-normal">Status</dt>
                    <dd class="col-7"><x-status-badge :status="$complaint->student->status" /></dd>
                </dl>
                <div class="alert alert-light border small mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Before integration this panel was impossible: the complaint system held only a name and a role.
                </div>
            </div>
        </x-page-card>

        @if ($complaint->handler)
            <x-page-card title="Handling history" class="mt-3">
                <div class="card-body small">
                    <div>Last updated by <strong>{{ $complaint->handler->name }}</strong></div>
                    <div class="text-secondary">{{ $complaint->updated_at->format('d M Y, H:i') }}</div>
                </div>
            </x-page-card>
        @endif
    </div>
</div>
@endsection
