@props(['title' => null, 'subtitle' => null, 'footer' => null])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title)
        <div class="card-header bg-white d-flex align-items-center gap-2 py-3">
            <div>
                <div class="fw-semibold">{{ $title }}</div>
                @if ($subtitle)<div class="small text-secondary">{{ $subtitle }}</div>@endif
            </div>
            @isset($actions)<div class="ms-auto">{{ $actions }}</div>@endisset
        </div>
    @endif

    {{ $slot }}

    @if ($footer)<div class="card-footer bg-white">{{ $footer }}</div>@endif
</div>
