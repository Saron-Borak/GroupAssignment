@extends('layouts.app')
@section('title', 'User accounts')
@section('heading', 'User accounts')
@section('subheading', 'Reset passwords and control who can sign in')

@section('content')
<x-page-card>
    <div class="card-header bg-white py-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-sm-6 col-lg-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or email">
                </div>
            </div>
            <div class="col-sm-4 col-lg-3">
                <select name="role" class="form-select form-select-sm">
                    <option value="">All roles</option>
                    @foreach (\App\Enums\UserRole::cases() as $case)
                        <option value="{{ $case->value }}" @selected(request('role') === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">Filter</button></div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Name</th><th>Role</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $user->name }}</div>
                        <div class="small text-secondary">{{ $user->email }}</div>
                    </td>
                    <td><span class="badge text-bg-light border"><i class="bi {{ $user->role->icon() }} me-1"></i>{{ $user->role->label() }}</span></td>
                    <td>
                        @if ($user->is_active)
                            <span class="badge text-bg-success-subtle text-success-emphasis border border-success-subtle">Active</span>
                        @else
                            <span class="badge text-bg-danger-subtle text-danger-emphasis border border-danger-subtle">Deactivated</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#pw{{ $user->id }}">
                            <i class="bi bi-key me-1"></i>Reset password
                        </button>
                        @unless ($user->is(auth()->user()))
                            <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="d-inline">
                                @csrf @method('PUT')
                                <button class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                    <i class="bi {{ $user->is_active ? 'bi-lock' : 'bi-unlock' }} me-1"></i>{{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $users->links() }}</div>
</x-page-card>

@foreach ($users as $user)
    <div class="modal fade" id="pw{{ $user->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('admin.users.password', $user) }}" class="modal-content">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Reset password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-secondary">
                        Setting a new password for <strong>{{ $user->name }}</strong>.
                        The system sends no email, so pass it on directly.
                    </p>
                    <div class="mb-3">
                        <label class="form-label" for="pw-new-{{ $user->id }}">New password</label>
                        <input type="password" id="pw-new-{{ $user->id }}" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>
                    <div>
                        <label class="form-label" for="pw-conf-{{ $user->id }}">Confirm password</label>
                        <input type="password" id="pw-conf-{{ $user->id }}" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Set password</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection
