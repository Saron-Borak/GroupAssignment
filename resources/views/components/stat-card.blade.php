@props(['label', 'value', 'icon' => 'bi-graph-up', 'variant' => 'primary', 'hint' => null, 'href' => null])

<div {{ $attributes->merge(['class' => 'card stat-card h-100']) }}>
    <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-{{ $variant }} bg-opacity-10 text-{{ $variant }}">
            <i class="bi {{ $icon }}"></i>
        </div>
        <div class="min-w-0">
            <div class="text-secondary text-uppercase fw-semibold" style="font-size:.72rem; letter-spacing:.06em">{{ $label }}</div>
            <div class="fs-4 fw-semibold lh-1 mt-1">{{ $value }}</div>
            @if ($hint)<div class="small text-secondary mt-1">{{ $hint }}</div>@endif
        </div>
        @if ($href)
            <a href="{{ $href }}" class="stretched-link ms-auto text-secondary" aria-label="{{ $label }}">
                <i class="bi bi-chevron-right"></i>
            </a>
        @endif
    </div>
</div>
