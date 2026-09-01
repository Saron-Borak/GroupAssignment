@csrf
<div class="card-body">
    <div class="row g-3">
        <div class="col-md-4">
            <label for="code" class="form-label">Course code <span class="text-danger">*</span></label>
            <input id="code" name="code" required maxlength="20"
                   class="form-control text-uppercase @error('code') is-invalid @enderror"
                   value="{{ old('code', $course->code) }}" placeholder="CS101">
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-8">
            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
            <input id="title" name="title" required maxlength="255"
                   class="form-control @error('title') is-invalid @enderror"
                   value="{{ old('title', $course->title) }}">
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-8">
            <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
            <select id="department_id" name="department_id" required
                    class="form-select @error('department_id') is-invalid @enderror">
                <option value="">Choose a department...</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected(old('department_id', $course->department_id) == $department->id)>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>
            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="credit_hours" class="form-label">Credit hours <span class="text-danger">*</span></label>
            <input type="number" id="credit_hours" name="credit_hours" min="1" max="12" required
                   class="form-control @error('credit_hours') is-invalid @enderror"
                   value="{{ old('credit_hours', $course->credit_hours ?? 3) }}">
            @error('credit_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
