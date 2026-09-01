@extends('layouts.guest')
@section('title', 'Sign in')

@section('content')
<div class="row justify-content-center">
    <div class="col-11 col-sm-9 col-md-7 col-lg-5 col-xl-4">
        <div class="text-center text-white mb-4">
            <span class="brand-mark mx-auto mb-3" style="width:56px;height:56px;font-size:1rem">
                {{ config('mis.university_short_name') }}
            </span>
            <h1 class="h4 fw-semibold mb-1">{{ config('mis.university_name') }}</h1>
            <p class="text-white-50 mb-0">{{ config('mis.system_name') }}</p>
        </div>

        <div class="card shadow-lg border-0">
            <div class="card-body p-4">
                <h2 class="h6 text-secondary text-uppercase mb-3" style="letter-spacing:.06em">Sign in</h2>

                @if (session('status'))
                    <div class="alert alert-info py-2 small">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
                @endif
                @error('email')
                    <div class="alert alert-danger py-2 small d-flex gap-2">
                        <i class="bi bi-x-octagon-fill"></i><div>{{ $message }}</div>
                    </div>
                @enderror

                <form method="POST" action="{{ route('login.store') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold">Email address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required autofocus autocomplete="username"
                                   placeholder="you@eamu.edu">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   required autocomplete="current-password" placeholder="********">
                        </div>
                        @error('password')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label small" for="remember">Keep me signed in</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Sign in
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center text-white-50 small mt-4 mb-0">
            Accounts are issued by the university registry.
        </p>
    </div>
</div>
@endsection
