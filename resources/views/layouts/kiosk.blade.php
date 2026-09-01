<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Check-in') · {{ config('mis.university_short_name') }} MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* Projector view: no navigation, high contrast, large type. */
        body { background:#0b1730; color:#fff; min-height:100vh; }
        .kiosk-code { font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
                      font-size: clamp(2.4rem, 7vw, 4.6rem); letter-spacing:.18em; font-weight:700; }
        .kiosk-qr { background:#fff; border-radius:1rem; padding:1rem; line-height:0; }
        .kiosk-qr svg { width:100%; height:auto; max-width:340px; }
        .kiosk-panel { background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); border-radius:1rem; }
        .kiosk-tally { font-size: clamp(1.8rem, 4vw, 3rem); font-weight:700; line-height:1; }
    </style>
</head>
<body class="d-flex flex-column">
    @yield('content')
    @stack('scripts')
</body>
</html>
