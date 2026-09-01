@extends('layouts.app')
@section('title', 'Attendance')
@section('heading', 'Attendance module')
@section('subheading', 'Registers taken against the shared student profiles enrolled in your sections')

@section('toolbar')
    <a href="{{ route('faculty.attendance.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New session
    </a>
@endsection

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <x-page-card>
            <div class="card-header bg-white py-3">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-sm-5">
                        <select name="section_id" class="form-select form-select-sm">
                            <option value="">All my classes</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" @selected(request('section_id') == $section->id)>
                                    {{ $section->course->code }}-{{ $section->section_code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Any status</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">Filter</button></div>
                </form>
            </div>

            @if ($sessions->isEmpty())
                <x-empty-state icon="bi-calendar-x" title="No sessions yet"
                               message="Create a session, then mark the register or open it for check-in.">
                    <a href="{{ route('faculty.attendance.create') }}" class="btn btn-sm btn-primary">Create a session</a>
                </x-empty-state>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th><th>Class</th><th>Time</th><th>Status</th>
                                <th class="text-end">Attended</th><th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($sessions as $session)
                            <tr>
                                <td class="fw-semibold text-nowrap">{{ $session->session_date->format('D, d M Y') }}</td>
                                <td class="small">
                                    <div class="fw-semibold">{{ $session->classSection->course->code }}-{{ $session->classSection->section_code }}</div>
                                    <div class="text-secondary">{{ $session->topic ?: $session->classSection->course->title }}</div>
                                </td>
                                <td class="small text-nowrap">{{ $session->timeRange() }}</td>
                                <td><x-status-badge :status="$session->status" /></td>
                                <td class="text-end small">
                                    @if ($session->records_count)
                                        <span class="fw-semibold">{{ $session->present_count }}</span>
                                        <span class="text-secondary">/ {{ $session->records_count }}</span>
                                    @else
                                        <span class="badge text-bg-warning">Not marked</span>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if ($session->status === App\Enums\SessionStatus::Open)
                                        <a href="{{ route('faculty.attendance.qr', $session) }}" class="btn btn-sm btn-success">
                                            <i class="bi bi-broadcast me-1"></i>Kiosk
                                        </a>
                                    @else
                                        <form method="POST" action="{{ route('faculty.attendance.open', $session) }}" class="d-inline">
                                            @csrf @method('PUT')
                                            <button class="btn btn-sm btn-outline-success" title="Open for QR check-in">
                                                <i class="bi bi-qr-code"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('faculty.attendance.mark', $session) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil-square me-1"></i>{{ $session->records_count ? 'Edit' : 'Mark' }}
                                    </a>

                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('faculty.attendance.edit', $session) }}">
                                                    <i class="bi bi-sliders me-2"></i>Edit details
                                                </a>
                                            </li>
                                            @if ($session->status !== App\Enums\SessionStatus::Closed)
                                                <li>
                                                    <form method="POST" action="{{ route('faculty.attendance.close', $session) }}">
                                                        @csrf @method('PUT')
                                                        <button class="dropdown-item">
                                                            <i class="bi bi-lock me-2"></i>Close &amp; mark absentees
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                            @if (! $session->records_count)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('faculty.attendance.destroy', $session) }}"
                                                          onsubmit="return confirm('Delete this session?')">
                                                        @csrf @method('DELETE')
                                                        <button class="dropdown-item text-danger">
                                                            <i class="bi bi-trash me-2"></i>Delete session
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white">{{ $sessions->links() }}</div>
            @endif
        </x-page-card>
    </div>

    <div class="col-lg-4">
        <x-page-card title="Generate from the timetable"
                     subtitle="Creates one session per weekly slot across a date range">
            <div class="card-body">
                <form method="POST" action="{{ route('faculty.attendance.generate') }}" class="d-grid gap-3">
                    @csrf
                    <div>
                        <label class="form-label small">Class</label>
                        <select name="class_section_id" class="form-select form-select-sm" required>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}">
                                    {{ $section->course->code }}-{{ $section->section_code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col">
                            <label class="form-label small">From</label>
                            <input type="date" name="from" class="form-control form-control-sm"
                                   value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col">
                            <label class="form-label small">To</label>
                            <input type="date" name="to" class="form-control form-control-sm"
                                   value="{{ now()->addWeeks(8)->toDateString() }}" required>
                        </div>
                    </div>
                    <button class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-calendar-plus me-1"></i>Generate sessions
                    </button>
                    <div class="form-text">
                        Meetings that already exist are skipped, so running this twice over the same
                        weeks is safe. The registry sets the weekly timetable on the class section.
                    </div>
                </form>
            </div>
        </x-page-card>

        <x-page-card title="How check-in works" class="mt-4">
            <div class="card-body small text-secondary">
                <p class="mb-2">
                    <strong class="text-body">Open</strong> a session to put a rotating QR code and a
                    six-character code on the projector. Students scan or type it and mark themselves.
                </p>
                <p class="mb-2">
                    <strong class="text-body">Close</strong> it when the class ends: everyone on the
                    roster who never checked in is marked absent in one write.
                </p>
                <p class="mb-0">
                    You can still mark the register by hand at any point — all three paths write
                    through the same service.
                </p>
            </div>
        </x-page-card>
    </div>
</div>
@endsection
