@extends('layouts.app')
@section('title', 'Check in')
@section('heading', 'Check in to a class')
@section('subheading', 'Scan the code on the screen, or type the six characters shown beside it')

@section('content')
<div class="row g-4">
    <div class="col-lg-6">
        <x-page-card title="Enter the code" subtitle="Shown next to the QR code at the front of the room">
            <div class="card-body">
                <form method="POST" action="{{ route('student.checkin.submit') }}" class="d-grid gap-3">
                    @csrf
                    <div>
                        <label for="code" class="form-label">Six-character code</label>
                        <input id="code" name="code" value="{{ old('code') }}" required
                               maxlength="6" autocomplete="off" autocapitalize="characters" autofocus
                               class="form-control form-control-lg text-center @error('code') is-invalid @enderror"
                               style="font-family: ui-monospace, Consolas, monospace; letter-spacing:.4em; text-transform:uppercase"
                               placeholder="ABC123">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">
                            The code changes every minute, so use the one on the screen now.
                        </div>
                    </div>
                    <button class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Check in
                    </button>
                </form>
            </div>
        </x-page-card>
    </div>

    <div class="col-lg-6">
        <x-page-card title="How this works">
            <div class="card-body">
                <ol class="mb-3 ps-3 small">
                    <li class="mb-2">Your lecturer opens the session and puts the code on the projector.</li>
                    <li class="mb-2">Scan it with your phone camera, or type the six characters here.</li>
                    <li class="mb-2">You are marked <strong>present</strong>, or <strong>late</strong> if you check in
                        more than {{ config('mis.late_after_minutes') }} minutes after the class started.</li>
                    <li>Anyone who has not checked in when the lecturer closes the session is marked absent.</li>
                </ol>

                <div class="alert alert-light border small mb-0">
                    <i class="bi bi-shield-check text-primary me-1"></i>
                    The code is replaced roughly every minute, and you can only check yourself in
                    to a class you are enrolled in — so a code passed to a friend outside the room
                    will not work for them.
                </div>
            </div>
        </x-page-card>
    </div>
</div>
@endsection
