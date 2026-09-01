@csrf
<div class="card-body">
    <div class="row g-3">
        <div class="col-md-8">
            <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
            <select id="course_id" name="course_id" required class="form-select @error('course_id') is-invalid @enderror">
                <option value="">Choose a course...</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" @selected(old('course_id', $section->course_id) == $course->id)>
                        {{ $course->code }} — {{ $course->title }}
                    </option>
                @endforeach
            </select>
            @error('course_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label for="section_code" class="form-label">Section code <span class="text-danger">*</span></label>
            <input id="section_code" name="section_code" required maxlength="10"
                   class="form-control text-uppercase @error('section_code') is-invalid @enderror"
                   value="{{ old('section_code', $section->section_code) }}" placeholder="A">
            @error('section_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="lecturer_id" class="form-label">Lecturer <span class="text-danger">*</span></label>
            <select id="lecturer_id" name="lecturer_id" required class="form-select @error('lecturer_id') is-invalid @enderror">
                <option value="">Choose a lecturer...</option>
                @foreach ($lecturers as $lecturer)
                    <option value="{{ $lecturer->id }}" @selected(old('lecturer_id', $section->lecturer_id) == $lecturer->id)>
                        {{ $lecturer->name }}
                    </option>
                @endforeach
            </select>
            @error('lecturer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label for="term" class="form-label">Term <span class="text-danger">*</span></label>
            <input id="term" name="term" required maxlength="30"
                   class="form-control @error('term') is-invalid @enderror"
                   value="{{ old('term', $section->term ?? '2026 Semester 2') }}">
            @error('term')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label for="room" class="form-label">Room</label>
            <input id="room" name="room" maxlength="50"
                   class="form-control @error('room') is-invalid @enderror"
                   value="{{ old('room', $section->room) }}" placeholder="Optional">
            @error('room')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
