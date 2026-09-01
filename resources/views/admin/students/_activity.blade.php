<x-page-card title="Enrolled classes" class="mb-3">
    @if ($student->enrollments->isEmpty())
        <x-empty-state icon="bi-journal-x" title="Not enrolled in any class" />
    @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead><tr><th>Course</th><th>Term</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($student->enrollments as $enrollment)
                    <tr>
                        <td class="small">
                            <span class="fw-semibold">{{ $enrollment->classSection->course->code }}-{{ $enrollment->classSection->section_code }}</span>
                            <div class="text-secondary">{{ $enrollment->classSection->course->title }}</div>
                        </td>
                        <td class="small">{{ $enrollment->classSection->term }}</td>
                        <td><span class="badge text-bg-light border">{{ $enrollment->status->label() }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-page-card>

<x-page-card title="Recent activity across the modules"
             subtitle="Read live from the attendance, submission and complaint records">
    <div class="card-body pb-0">
        <ul class="nav nav-tabs card-header-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-attendance" type="button" role="tab">
                    Attendance <span class="badge text-bg-light border ms-1">{{ $recentAttendance->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-submissions" type="button" role="tab">
                    Submissions <span class="badge text-bg-light border ms-1">{{ $recentSubmissions->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-complaints" type="button" role="tab">
                    Complaints <span class="badge text-bg-light border ms-1">{{ $recentComplaints->count() }}</span>
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-attendance" role="tabpanel">
            @if ($recentAttendance->isEmpty())
                <x-empty-state icon="bi-calendar-x" title="No attendance recorded yet" />
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead><tr><th>Date</th><th>Course</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($recentAttendance as $record)
                            <tr>
                                <td class="small text-nowrap">{{ $record->session->session_date->format('d M Y') }}</td>
                                <td class="small">{{ $record->session->classSection->course->code }}</td>
                                <td><x-status-badge :status="$record->status" /></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="tab-pane fade" id="tab-submissions" role="tabpanel">
            @if ($recentSubmissions->isEmpty())
                <x-empty-state icon="bi-cloud-slash" title="No submissions yet" />
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead><tr><th>Assignment</th><th>Course</th><th>Submitted</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($recentSubmissions as $submission)
                            <tr>
                                <td class="small">{{ $submission->assignment->title }}</td>
                                <td class="small text-secondary">{{ $submission->assignment->classSection->course->code }}</td>
                                <td class="small text-nowrap">{{ $submission->submitted_at->format('d M, H:i') }}</td>
                                <td><x-status-badge :status="$submission->status" /></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="tab-pane fade" id="tab-complaints" role="tabpanel">
            @if ($recentComplaints->isEmpty())
                <x-empty-state icon="bi-emoji-smile" title="No complaints raised" />
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead><tr><th>Reference</th><th>Title</th><th>Category</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($recentComplaints as $complaint)
                            <tr>
                                <td class="small font-monospace">{{ $complaint->reference }}</td>
                                <td class="small">{{ $complaint->title }}</td>
                                <td class="small text-secondary">{{ $complaint->category->label() }}</td>
                                <td><x-status-badge :status="$complaint->status" /></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-page-card>
