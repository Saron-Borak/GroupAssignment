{{-- Identity header: photograph, key identifiers and profile completeness. --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-start">
            <div class="col-auto">
                <x-student-photo :student="$student" />
            </div>
            <div class="col">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <h2 class="h4 mb-0">{{ $student->fullName() }}</h2>
                    <x-status-badge :status="$student->status" />
                    @if ($student->trashed())
                        <span class="badge text-bg-dark">Archived</span>
                    @endif
                </div>

                <dl class="row mb-0 small">
                    <dt class="col-sm-4 col-lg-3 text-secondary fw-normal">Student number</dt>
                    <dd class="col-sm-8 col-lg-9 font-monospace">{{ $student->student_id_no }}</dd>

                    <dt class="col-sm-4 col-lg-3 text-secondary fw-normal">Program</dt>
                    <dd class="col-sm-8 col-lg-9">
                        {{ $student->program->name }}
                        <span class="text-secondary">({{ $student->program->department->name }})</span>
                    </dd>

                    <dt class="col-sm-4 col-lg-3 text-secondary fw-normal">Email</dt>
                    <dd class="col-sm-8 col-lg-9">{{ $student->email }}</dd>

                    <dt class="col-sm-4 col-lg-3 text-secondary fw-normal">Phone</dt>
                    <dd class="col-sm-8 col-lg-9">{{ $student->phone ?: 'Not recorded' }}</dd>

                    <dt class="col-sm-4 col-lg-3 text-secondary fw-normal">Sign-in account</dt>
                    <dd class="col-sm-8 col-lg-9">
                        @if ($student->user)
                            <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Issued</span>
                        @else
                            <span class="text-secondary">Not issued</span>
                        @endif
                    </dd>
                </dl>
            </div>

            <div class="col-lg-3">
                <div class="border rounded p-3 bg-light">
                    <div class="small text-uppercase fw-semibold text-secondary mb-2" style="font-size:.7rem; letter-spacing:.06em">
                        Profile completeness
                    </div>
                    <x-meter :percentage="$student->completenessPercentage()" class="mb-2" />
                    <ul class="list-unstyled mb-0 small">
                        @foreach ($student->completeness() as $section => $done)
                            <li class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi {{ $done ? 'bi-check-circle-fill text-success' : 'bi-circle text-secondary' }}"></i>
                                <span class="{{ $done ? '' : 'text-secondary' }}">{{ ucfirst($section) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
