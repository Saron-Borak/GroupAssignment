@props(['percentage', 'threshold' => null, 'showValue' => true])

@php
    $value = round((float) $percentage, 1);
    $variant = $threshold === null
        ? ($value >= 80 ? 'success' : ($value >= 50 ? 'warning' : 'danger'))
        : ($value >= $threshold ? 'success' : ($value >= $threshold - 10 ? 'warning' : 'danger'));
@endphp

<div {{ $attributes->merge(['class' => 'd-flex align-items-center gap-2']) }}>
    <div class="meter flex-grow-1" style="min-width:60px"
         role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100"
         aria-label="{{ $value }} percent">
        <span class="bg-{{ $variant }}" style="width: {{ min(100, max(0, $value)) }}%"></span>
    </div>
    @if ($showValue)
        <span class="small fw-semibold text-{{ $variant }}" style="min-width:46px">{{ number_format($value, 1) }}%</span>
    @endif
</div>
