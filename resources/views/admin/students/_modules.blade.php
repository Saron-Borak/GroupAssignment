{{--
    The integration payoff: three subsystems, each built as a separate mini
    project, all resolved from this one profile. Every card carries its own
    accent colour so the source of each figure is obvious.
--}}
<div class="col-md-4">
    <div class="card h-100 module-attendance">
        <div class="card-body">
            <div class="d-flex align-items-start gap-2 mb-2">
                <div class="flex-grow-1">
                    <div class="small text-uppercase fw-semibold text-secondary" style="font-size:.7rem; letter-spacing:.06em">
                        Attendance module
                    </div>
                    <div class="fs-3 fw-semibold lh-1 mt-1 {{ $insight['attendance']['at_risk'] ? 'text-danger' : '' }}">
                        {{ number_format($insight['attendance']['percentage'], 1) }}%
                    </div>
                </div>
                <i class="bi bi-calendar-check fs-4 text-secondary opacity-50"></i>
            </div>

            @if ($insight['attendance']['at_risk'])
                <div class="small text-danger fw-semibold mb-2">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Below the {{ config('mis.attendance_min_percentage') }}% requirement
                </div>
            @endif

            <div class="small text-secondary">
                {{ $insight['attendance']['attended'] }} of {{ $insight['attendance']['countable'] }} sessions attended
            </div>
            <div class="small text-secondary mt-1">
                <span class="text-success">{{ $insight['attendance']['present'] }} present</span> ·
                <span class="text-warning-emphasis">{{ $insight['attendance']['late'] }} late</span> ·
                <span class="text-danger">{{ $insight['attendance']['absent'] }} absent</span>
            </div>
        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="card h-100 module-submissions">
        <div class="card-body">
            <div class="d-flex align-items-start gap-2 mb-2">
                <div class="flex-grow-1">
                    <div class="small text-uppercase fw-semibold text-secondary" style="font-size:.7rem; letter-spacing:.06em">
                        Submission module
                    </div>
                    <div class="fs-3 fw-semibold lh-1 mt-1">
                        {{ $insight['submissions']['submitted'] }}<span class="fs-6 text-secondary">/{{ $insight['submissions']['issued'] }}</span>
                    </div>
                </div>
                <i class="bi bi-cloud-arrow-up fs-4 text-secondary opacity-50"></i>
            </div>

            @if ($insight['submissions']['missing'] > 0)
                <div class="small text-warning-emphasis fw-semibold mb-2">
                    <i class="bi bi-exclamation-circle-fill me-1"></i>{{ $insight['submissions']['missing'] }} not submitted
                </div>
            @endif

            <div class="small text-secondary">Assignments submitted</div>
            <div class="small text-secondary mt-1">
                <span class="text-success">{{ $insight['submissions']['on_time'] }} on time</span> ·
                <span class="text-warning-emphasis">{{ $insight['submissions']['late'] }} late</span>
            </div>
        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="card h-100 module-complaints">
        <div class="card-body">
            <div class="d-flex align-items-start gap-2 mb-2">
                <div class="flex-grow-1">
                    <div class="small text-uppercase fw-semibold text-secondary" style="font-size:.7rem; letter-spacing:.06em">
                        Complaint module
                    </div>
                    <div class="fs-3 fw-semibold lh-1 mt-1">{{ $insight['complaints']['total'] }}</div>
                </div>
                <i class="bi bi-chat-left-text fs-4 text-secondary opacity-50"></i>
            </div>

            @if ($insight['complaints']['open'] > 0)
                <div class="small text-warning-emphasis fw-semibold mb-2">
                    <i class="bi bi-hourglass-split me-1"></i>{{ $insight['complaints']['open'] }} still open
                </div>
            @endif

            <div class="small text-secondary">Cases raised by this student</div>
            <div class="small text-secondary mt-1">
                <span class="text-danger">{{ $insight['complaints']['pending'] }} pending</span> ·
                <span class="text-warning-emphasis">{{ $insight['complaints']['in_progress'] }} in progress</span> ·
                <span class="text-success">{{ $insight['complaints']['resolved'] }} resolved</span>
            </div>
        </div>
    </div>
</div>
