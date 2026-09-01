<x-page-card title="Personal details" class="mb-3">
    <div class="card-body">
        <dl class="row mb-0 small">
            <dt class="col-5 text-secondary fw-normal">Date of birth</dt>
            <dd class="col-7">
                {{ $student->date_of_birth->format('d M Y') }}
                <span class="text-secondary">({{ $student->age() }} years)</span>
            </dd>
            <dt class="col-5 text-secondary fw-normal">Gender</dt><dd class="col-7">{{ $student->gender->label() }}</dd>
            <dt class="col-5 text-secondary fw-normal">Nationality</dt><dd class="col-7">{{ $student->nationality ?: '-' }}</dd>
            <dt class="col-5 text-secondary fw-normal">National ID</dt><dd class="col-7 font-monospace">{{ $student->national_id ?: '-' }}</dd>
            <dt class="col-5 text-secondary fw-normal">Intake year</dt><dd class="col-7">{{ $student->intake_year }}</dd>
            <dt class="col-5 text-secondary fw-normal">Admitted</dt><dd class="col-7">{{ $student->admission_date->format('d M Y') }}</dd>
        </dl>
    </div>
</x-page-card>

<x-page-card title="Addresses" class="mb-3">
    @if ($student->addresses->isEmpty())
        <x-empty-state icon="bi-geo-alt" title="No address on file" message="Add one from the edit screen." />
    @else
        <div class="list-group list-group-flush">
            @foreach ($student->addresses as $address)
                <div class="list-group-item">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge text-bg-light border">{{ $address->addressType->name }}</span>
                        @if ($address->is_primary)<span class="badge text-bg-primary">Primary</span>@endif
                    </div>
                    <div class="small">{{ $address->oneLine() }}</div>
                </div>
            @endforeach
        </div>
    @endif
</x-page-card>

<x-page-card title="Parent or guardian">
    @if ($student->guardians->isEmpty())
        <x-empty-state icon="bi-person-square" title="No guardian on file"
                       message="At least one contact is required for an active student." />
    @else
        <div class="list-group list-group-flush">
            @foreach ($student->guardians as $guardian)
                <div class="list-group-item">
                    <div class="d-flex align-items-center gap-2">
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">{{ $guardian->full_name }}</div>
                            <div class="text-secondary" style="font-size:.78rem">
                                {{ $guardian->relationship->label() }}@if ($guardian->occupation) · {{ $guardian->occupation }}@endif
                            </div>
                        </div>
                        @if ($guardian->is_emergency_contact)
                            <span class="badge text-bg-danger-subtle text-danger-emphasis border border-danger-subtle">Emergency</span>
                        @endif
                    </div>
                    <div class="small mt-1">
                        <i class="bi bi-telephone me-1 text-secondary"></i>{{ $guardian->phone }}
                        @if ($guardian->email)
                            <i class="bi bi-envelope ms-3 me-1 text-secondary"></i>{{ $guardian->email }}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-page-card>
