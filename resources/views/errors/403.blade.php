@extends('layouts.guest')
@section('title', 'Access denied')
@section('content')
<div class="row justify-content-center">
    <div class="col-11 col-sm-8 col-md-6 col-lg-5">
        <div class="card shadow-lg border-0 text-center">
            <div class="card-body p-5">
                <i class="bi bi-shield-lock text-danger d-block mb-3" style="font-size:2.8rem"></i>
                <h1 class="h4 mb-2">Access denied</h1>
                <p class="text-secondary">{{ $exception?->getMessage() ?: 'You do not have permission to view this page.' }}</p>
                @auth
                    <a href="{{ route(auth()->user()->role->homeRoute()) }}" class="btn btn-primary mt-2">
                        <i class="bi bi-house me-1"></i>Back to my dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary mt-2">Sign in</a>
                @endauth
            </div>
        </div>
    </div>
</div>
@endsection
