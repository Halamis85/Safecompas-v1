@php
    use Illuminate\Support\Facades\Vite;
    $perfNonce = Vite::cspNonce();
@endphp

<script @if($perfNonce) nonce="{{ $perfNonce }}" @endif>
    (function () {
        if (document.documentElement.dataset.themePending === 'dark') {
            document.body.classList.add('dark-mode');
            document.body.setAttribute('data-bs-theme', 'dark');
            delete document.documentElement.dataset.themePending;
        } else {
            document.body.setAttribute('data-bs-theme', 'light');
        }
    })();
</script>
