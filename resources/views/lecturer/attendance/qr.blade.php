@extends('layouts.kiosk')
@section('title', 'Check-in · '.$session->classSection->label())

@section('content')
@php($localhost = str_contains(config('app.url'), '127.0.0.1') || str_contains(config('app.url'), 'localhost'))

<div class="container-fluid px-4 py-4 flex-grow-1 d-flex flex-column">

    <div class="d-flex align-items-center gap-3 mb-4">
        <div>
            <div class="text-uppercase small fw-semibold" style="letter-spacing:.14em; color:#7dd3fc">
                {{ config('mis.university_short_name') }} · Attendance check-in
            </div>
            <div class="h3 mb-0 fw-bold">{{ $session->classSection->label() }} — {{ $session->classSection->course->title }}</div>
            <div class="text-white-50">
                {{ $session->session_date->format('l, d F Y') }} · {{ $session->timeRange() }}
                @if ($session->topic) · {{ $session->topic }} @endif
            </div>
        </div>
        <form method="POST" action="{{ route('faculty.attendance.close', $session) }}" class="ms-auto">
            @csrf @method('PUT')
            <button class="btn btn-light btn-lg">
                <i class="bi bi-lock me-1"></i>Close session
            </button>
        </form>
    </div>

    @if ($localhost)
        <div class="alert alert-warning d-flex gap-2 align-items-start">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div>
                <strong>Phones cannot reach this address.</strong>
                APP_URL points at localhost, so the QR code resolves to the phone itself. Set
                <code>APP_URL</code> to this machine's LAN address and run
                <code>php artisan serve --host=0.0.0.0 --port=8001</code>. The six-character
                code below works either way.
            </div>
        </div>
    @endif

    <div class="row g-4 flex-grow-1">
        <div class="col-lg-5">
            <div class="kiosk-panel p-4 h-100 d-flex flex-column align-items-center justify-content-center text-center">
                <div class="kiosk-qr mb-3" id="qrHolder">{!! $svg !!}</div>
                <div class="text-white-50 text-uppercase small fw-semibold mb-1" style="letter-spacing:.14em">
                    Or type this code
                </div>
                <div class="kiosk-code" id="code">{{ $session->checkin_code }}</div>
                <div class="text-white-50 small mt-2">
                    New code in <span id="countdown">{{ $session->qrSecondsLeft() }}</span>s
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="kiosk-panel p-4 h-100">
                <div class="text-uppercase small fw-semibold text-white-50 mb-3" style="letter-spacing:.14em">
                    Checked in
                </div>
                <div class="row text-center g-3">
                    <div class="col-4">
                        <div class="kiosk-tally text-success" id="present">{{ $present }}</div>
                        <div class="small text-white-50">Present</div>
                    </div>
                    <div class="col-4">
                        <div class="kiosk-tally text-warning" id="late">{{ $late }}</div>
                        <div class="small text-white-50">Late</div>
                    </div>
                    <div class="col-4">
                        <div class="kiosk-tally" id="total">{{ $total }}</div>
                        <div class="small text-white-50">On roster</div>
                    </div>
                </div>
                <hr class="border-light opacity-25 my-4">
                <div class="small text-white-50">
                    A student scans the code or types it at
                    <span class="text-white">Student → Check in</span>. The lecturer never has to
                    call a register.
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="kiosk-panel p-4 h-100">
                <div class="text-uppercase small fw-semibold text-white-50 mb-3" style="letter-spacing:.14em">
                    Just arrived
                </div>
                <ul class="list-unstyled mb-0" id="recent">
                    @forelse ($recent as $entry)
                        <li class="d-flex justify-content-between gap-2 py-1 border-bottom border-light border-opacity-10">
                            <span>{{ $entry['name'] }}</span>
                            <span class="text-white-50 small">{{ $entry['at'] }}</span>
                        </li>
                    @empty
                        <li class="text-white-50 small">Nobody yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // The page never reloads: it polls for a fresh token and swaps the SVG in
    // place, so the countdown on screen stays honest and the tally is live.
    const refreshMs = {{ $refreshSeconds * 1000 }};
    const url = @json(route('faculty.attendance.qr.refresh', $session));
    let remaining = {{ $session->qrSecondsLeft() }};

    const el = (id) => document.getElementById(id);

    setInterval(() => {
        remaining = Math.max(0, remaining - 1);
        el('countdown').textContent = remaining;
    }, 1000);

    async function refresh() {
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();

            if (!data.open) {
                window.location.reload();
                return;
            }

            el('qrHolder').innerHTML = data.svg;
            el('code').textContent = data.code;
            el('present').textContent = data.present;
            el('late').textContent = data.late;
            el('total').textContent = data.total;
            remaining = data.expires_in;

            el('recent').innerHTML = data.recent.length
                ? data.recent.map(r =>
                    `<li class="d-flex justify-content-between gap-2 py-1 border-bottom border-light border-opacity-10">
                        <span>${r.name}</span><span class="text-white-50 small">${r.at ?? ''}</span></li>`).join('')
                : '<li class="text-white-50 small">Nobody yet.</li>';
        } catch (e) {
            // A dropped poll is not worth interrupting a lecture for; the next
            // one in a few seconds will pick the state back up.
        }
    }

    setInterval(refresh, refreshMs);
</script>
@endpush
@endsection
