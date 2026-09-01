@extends('layouts.app')
@section('title', $complaint->reference)
@section('heading', $complaint->title)
@section('subheading', $complaint->reference.' · raised '.$complaint->created_at->format('d M Y'))

@section('toolbar')
    <a href="{{ route('student.complaints.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>My complaints
    </a>
@endsection

@section('content')
<div class="row"><div class="col-lg-8">
    <x-page-card title="Case detail">
        <div class="card-body">
            <dl class="row mb-3 small">
                <dt class="col-4 col-sm-3 text-secondary fw-normal">Reference</dt>
                <dd class="col-8 col-sm-9 font-monospace">{{ $complaint->reference }}</dd>
                <dt class="col-4 col-sm-3 text-secondary fw-normal">Category</dt>
                <dd class="col-8 col-sm-9">{{ $complaint->category->label() }}</dd>
                <dt class="col-4 col-sm-3 text-secondary fw-normal">Status</dt>
                <dd class="col-8 col-sm-9"><x-status-badge :status="$complaint->status" /></dd>
                @if ($complaint->resolved_at)
                    <dt class="col-4 col-sm-3 text-secondary fw-normal">Resolved</dt>
                    <dd class="col-8 col-sm-9">{{ $complaint->resolved_at->format('d M Y') }}</dd>
                @endif
            </dl>

            <h3 class="h6 text-secondary text-uppercase" style="letter-spacing:.06em">What I reported</h3>
            <p>{{ $complaint->description }}</p>

            <h3 class="h6 text-secondary text-uppercase mt-4" style="letter-spacing:.06em">Registry response</h3>
            @if ($complaint->admin_response)
                <div class="alert alert-light border mb-0">
                    <p class="mb-1">{{ $complaint->admin_response }}</p>
                    @if ($complaint->handler)
                        <div class="small text-secondary">{{ $complaint->handler->name }}</div>
                    @endif
                </div>
            @else
                <p class="text-secondary mb-0">No response yet. The registry will update this case.</p>
            @endif
        </div>
    </x-page-card>
</div></div>
@endsection
