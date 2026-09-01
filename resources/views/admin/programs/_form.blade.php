<div class="row g-3">
    <div class="col-md-3">
        <label for="code" class="form-label">Program code <span class="text-danger">*</span></label>
        <input type="text" id="code" name="code" maxlength="20"
               class="form-control text-uppercase @error('code') is-invalid @enderror"
               value="{{ old('code', $program->code) }}" required placeholder="BSCS">
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-9">
        <label for="name" class="form-label">Program name <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $program->name) }}" required placeholder="BSc Computer Science">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-5">
        <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
        <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
            <option value="">Choose a department...</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $program->department_id) == $department->id)>
                    {{ $department->code }} - {{ $department->name }}
                </option>
            @endforeach
        </select>
        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="level" class="form-label">Level <span class="text-danger">*</span></label>
        <select id="level" name="level" class="form-select @error('level') is-invalid @enderror" required>
            @foreach (\App\Enums\ProgramLevel::cases() as $case)
                <option value="{{ $case->value }}" @selected(old('level', $program->level?->value ?? 'bachelor') === $case->value)>
                    {{ $case->label() }}
                </option>
            @endforeach
        </select>
        @error('level')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="duration_years" class="form-label">Duration (years) <span class="text-danger">*</span></label>
        <input type="number" id="duration_years" name="duration_years" min="1" max="8"
               class="form-control @error('duration_years') is-invalid @enderror"
               value="{{ old('duration_years', $program->duration_years ?? 4) }}" required>
        @error('duration_years')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
