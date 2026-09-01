@props(['student', 'size' => null])

@if ($student->photoUrl())
    <img src="{{ $student->photoUrl() }}" alt="Photograph of {{ $student->fullName() }}"
         class="student-photo" @if($size) style="width:{{ $size }}px;height:{{ $size }}px" @endif>
@else
    <div class="student-photo-empty" @if($size) style="width:{{ $size }}px;height:{{ $size }}px;font-size:{{ round($size/3.5) }}px" @endif
         aria-label="No photograph on file">{{ $student->initials() }}</div>
@endif
