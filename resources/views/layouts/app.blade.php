<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('mis.university_short_name') }} MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    @include('layouts.partials.sidebar')

    <div class="app-main flex-grow-1">
        <header class="app-topbar bg-white border-bottom sticky-top">
            <div class="d-flex align-items-center gap-3 px-3 px-lg-4 py-2">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <div class="me-auto">
                    <h1 class="h5 mb-0">@yield('heading', View::yieldContent('title', 'Dashboard'))</h1>
                    @hasSection('subheading')
                        <div class="small text-secondary">@yield('subheading')</div>
                    @endif
                </div>

                @yield('toolbar')

                <div class="dropdown">
                    <button class="btn btn-link text-decoration-none text-body d-flex align-items-center gap-2 px-1"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar-bubble">{{ auth()->user()->initials() }}</span>
                        <span class="d-none d-md-inline text-start lh-sm">
                            <span class="d-block small fw-semibold">{{ auth()->user()->name }}</span>
                            <span class="d-block text-secondary" style="font-size:.75rem">{{ auth()->user()->role->label() }}</span>
                        </span>
                        <i class="bi bi-chevron-down small text-secondary"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Sign out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="p-3 p-lg-4">
            <x-flash />
            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', function () {
        document.querySelector('.app-sidebar')?.classList.toggle('show');
    });
</script>
@stack('scripts')
</body>
</html>
