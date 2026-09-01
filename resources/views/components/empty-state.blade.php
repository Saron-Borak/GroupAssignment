@props(['icon' => 'bi-inbox', 'title' => 'Nothing here yet', 'message' => null])

<div {{ $attributes->merge(['class' => 'text-center text-secondary py-5']) }}>
    <i class="bi {{ $icon }} d-block mb-2" style="font-size:2.2rem; opacity:.45"></i>
    <div class="fw-semibold text-body">{{ $title }}</div>
    @if ($message)<div class="small mt-1">{{ $message }}</div>@endif
    @if (trim($slot) !== '')<div class="mt-3">{{ $slot }}</div>@endif
</div>
