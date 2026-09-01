@php($isEdit = $student->exists)

<h3 class="h6 text-secondary text-uppercase mb-3" style="letter-spacing:.06em">Identity</h3>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <label for="student_id_no" class="form-label">Student number <span class="text-danger">*</span></label>
        <input type="text" id="student_id_no" name="student_id_no" maxlength="30"
               class="form-control text-uppercase font-monospace @error('student_id_no') is-invalid @enderror"
               value="{{ old('student_id_no', $student->student_id_no ?? ($suggestedNumber ?? '')) }}" required>
        @error('student_id_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="first_name" class="form-label">First name <span class="text-danger">*</span></label>
        <input type="text" id="first_name" name="first_name" maxlength="80"
               class="form-control @error('first_name') is-invalid @enderror"
               value="{{ old('first_name', $student->first_name) }}" required>
        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-5">
        <label for="last_name" class="form-label">Last name <span class="text-danger">*</span></label>
        <input type="text" id="last_name" name="last_name" maxlength="80"
               class="form-control @error('last_name') is-invalid @enderror"
               value="{{ old('last_name', $student->last_name) }}" required>
        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="date_of_birth" class="form-label">Date of birth <span class="text-danger">*</span></label>
        <input type="date" id="date_of_birth" name="date_of_birth"
               class="form-control @error('date_of_birth') is-invalid @enderror"
               value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}" required>
        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
        <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror" required>
            @foreach (\App\Enums\Gender::cases() as $case)
                <option value="{{ $case->value }}" @selected(old('gender', $student->gender?->value ?? 'undisclosed') === $case->value)>
                    {{ $case->label() }}
                </option>
            @endforeach
        </select>
        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="nationality" class="form-label">Nationality</label>
        <input type="text" id="nationality" name="nationality" maxlength="60"
               class="form-control @error('nationality') is-invalid @enderror"
               value="{{ old('nationality', $student->nationality) }}">
        @error('nationality')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="national_id" class="form-label">National ID</label>
        <input type="text" id="national_id" name="national_id" maxlength="40"
               class="form-control font-monospace @error('national_id') is-invalid @enderror"
               value="{{ old('national_id', $student->national_id) }}">
        @error('national_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<h3 class="h6 text-secondary text-uppercase mb-3" style="letter-spacing:.06em">Contact and photograph</h3>
<div class="row g-3 mb-4">
    <div class="col-md-5">
        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" id="email" name="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $student->email) }}" required placeholder="name@student.eamu.edu">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="phone" class="form-label">Phone</label>
        <input type="text" id="phone" name="phone" maxlength="30"
               class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone', $student->phone) }}" placeholder="012 345 678">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="photo" class="form-label">Photograph</label>
        <input type="file" id="photo" name="photo" accept="image/*"
               class="form-control @error('photo') is-invalid @enderror">
        <div class="form-text">
            {{ strtoupper(implode(', ', config('mis.photo_mimes'))) }}, up to {{ round(config('mis.photo_max_kb') / 1024, 1) }} MB.
        </div>
        @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror

        @if ($isEdit && $student->photo_path)
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="remove_photo" name="remove_photo" value="1">
                <label class="form-check-label small" for="remove_photo">Remove the current photograph</label>
            </div>
        @endif
    </div>
</div>

<h3 class="h6 text-secondary text-uppercase mb-3" style="letter-spacing:.06em">Enrolment</h3>
<div class="row g-3 mb-4">
    <div class="col-md-5">
        <label for="program_id" class="form-label">Program <span class="text-danger">*</span></label>
        <select id="program_id" name="program_id" class="form-select @error('program_id') is-invalid @enderror" required>
            <option value="">Choose a program...</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(old('program_id', $student->program_id) == $program->id)>
                    {{ $program->code }} - {{ $program->name }}
                </option>
            @endforeach
        </select>
        @error('program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label for="intake_year" class="form-label">Intake year <span class="text-danger">*</span></label>
        <input type="number" id="intake_year" name="intake_year" min="2000" max="{{ date('Y') + 1 }}"
               class="form-control @error('intake_year') is-invalid @enderror"
               value="{{ old('intake_year', $student->intake_year) }}" required>
        @error('intake_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="admission_date" class="form-label">Admission date <span class="text-danger">*</span></label>
        <input type="date" id="admission_date" name="admission_date"
               class="form-control @error('admission_date') is-invalid @enderror"
               value="{{ old('admission_date', $student->admission_date?->format('Y-m-d')) }}" required>
        @error('admission_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach (\App\Enums\StudentStatus::cases() as $case)
                <option value="{{ $case->value }}" @selected(old('status', $student->status?->value ?? 'active') === $case->value)>
                    {{ $case->label() }}
                </option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@php($addressRows = old('addresses', $addresses ?: [[]]))
@php($addressRows = count($addressRows) ? $addressRows : [[]])

<h3 class="h6 text-secondary text-uppercase mb-1" style="letter-spacing:.06em">Addresses</h3>
<p class="small text-secondary">The first address entered becomes the primary one. Leave a row blank to skip it.</p>

<div id="addressRows" class="mb-2">
    @foreach ($addressRows as $i => $row)
        <div class="repeat-row p-3 mb-2 address-row">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small">Type</label>
                    <select name="addresses[{{ $i }}][address_type_id]" class="form-select form-select-sm">
                        <option value="">-- none --</option>
                        @foreach ($addressTypes as $type)
                            <option value="{{ $type->id }}" @selected(($row['address_type_id'] ?? null) == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small">Address line 1</label>
                    <input type="text" name="addresses[{{ $i }}][line1]" class="form-control form-control-sm"
                           value="{{ $row['line1'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Address line 2</label>
                    <input type="text" name="addresses[{{ $i }}][line2]" class="form-control form-control-sm"
                           value="{{ $row['line2'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">City</label>
                    <input type="text" name="addresses[{{ $i }}][city]" class="form-control form-control-sm"
                           value="{{ $row['city'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Province</label>
                    <input type="text" name="addresses[{{ $i }}][province]" class="form-control form-control-sm"
                           value="{{ $row['province'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Postal code</label>
                    <input type="text" name="addresses[{{ $i }}][postal_code]" class="form-control form-control-sm"
                           value="{{ $row['postal_code'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Country</label>
                    <input type="text" name="addresses[{{ $i }}][country]" class="form-control form-control-sm"
                           value="{{ $row['country'] ?? 'Cambodia' }}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-row" title="Remove">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    @endforeach
</div>
<button type="button" id="addAddressRow" class="btn btn-sm btn-outline-secondary mb-4">
    <i class="bi bi-plus-lg me-1"></i>Add another address
</button>

@php($guardianRows = old('guardians', $guardians ?: [[]]))
@php($guardianRows = count($guardianRows) ? $guardianRows : [[]])

<h3 class="h6 text-secondary text-uppercase mb-1" style="letter-spacing:.06em">Parent or guardian</h3>
<p class="small text-secondary">The first contact entered is recorded as the emergency contact.</p>

<div id="guardianRows" class="mb-2">
    @foreach ($guardianRows as $i => $row)
        <div class="repeat-row p-3 mb-2 guardian-row">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label small">Full name</label>
                    <input type="text" name="guardians[{{ $i }}][full_name]" class="form-control form-control-sm"
                           value="{{ $row['full_name'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Relationship</label>
                    <select name="guardians[{{ $i }}][relationship]" class="form-select form-select-sm">
                        @foreach (\App\Enums\GuardianRelationship::cases() as $case)
                            <option value="{{ $case->value }}" @selected(($row['relationship'] ?? 'guardian') == $case->value)>
                                {{ $case->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Phone</label>
                    <input type="text" name="guardians[{{ $i }}][phone]" class="form-control form-control-sm"
                           value="{{ $row['phone'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Email</label>
                    <input type="email" name="guardians[{{ $i }}][email]" class="form-control form-control-sm"
                           value="{{ $row['email'] ?? '' }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Occupation</label>
                    <input type="text" name="guardians[{{ $i }}][occupation]" class="form-control form-control-sm"
                           value="{{ $row['occupation'] ?? '' }}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100 remove-row" title="Remove">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    @endforeach
</div>
<button type="button" id="addGuardianRow" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-plus-lg me-1"></i>Add another contact
</button>

@push('scripts')
<script>
(function () {
    // Clone the first row of a repeating group, renumbering its field names so
    // the array indexes stay unique when the form is re-submitted.
    function wire(containerId, buttonId, rowClass) {
        const container = document.getElementById(containerId);
        const button = document.getElementById(buttonId);
        if (!container || !button) return;

        let next = container.querySelectorAll('.' + rowClass).length;
        const group = rowClass === 'address-row' ? 'addresses' : 'guardians';
        const pattern = new RegExp(group + '\[\d+\]');

        button.addEventListener('click', function () {
            const first = container.querySelector('.' + rowClass);
            const clone = first.cloneNode(true);

            clone.querySelectorAll('input, select').forEach(function (field) {
                field.name = field.name.replace(pattern, group + '[' + next + ']');
                if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0;
                } else {
                    field.value = field.name.endsWith('[country]') ? 'Cambodia' : '';
                }
            });

            container.appendChild(clone);
            next++;
        });

        container.addEventListener('click', function (event) {
            const remove = event.target.closest('.remove-row');
            if (!remove) return;

            const rows = container.querySelectorAll('.' + rowClass);
            if (rows.length === 1) {
                // Always keep one row so there is something to clone from.
                rows[0].querySelectorAll('input, select').forEach(function (f) {
                    if (f.tagName === 'SELECT') { f.selectedIndex = 0; } else { f.value = ''; }
                });
                return;
            }
            remove.closest('.' + rowClass).remove();
        });
    }

    wire('addressRows', 'addAddressRow', 'address-row');
    wire('guardianRows', 'addGuardianRow', 'guardian-row');
})();
</script>
@endpush
