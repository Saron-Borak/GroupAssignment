@foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'status' => 'info'] as $key => $variant)
    @if (session($key))
        <div class="alert alert-{{ $variant }} alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
            <i class="bi bi-{{ $variant === 'success' ? 'check-circle-fill' : ($variant === 'danger' ? 'x-octagon-fill' : 'info-circle-fill') }} mt-1"></i>
            <div>{{ session($key) }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
@endforeach

@if ($errors->any() && ! $errors->has('email'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <div class="fw-semibold mb-1"><i class="bi bi-x-octagon-fill me-1"></i>Please correct the following:</div>
        <ul class="mb-0 ps-4">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
