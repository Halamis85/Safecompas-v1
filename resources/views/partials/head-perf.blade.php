@php
    use Illuminate\Support\Facades\Vite;
    $perfNonce = Vite::cspNonce();
@endphp

{{-- DNS preconnect pro externí API z dashboardu (počasí + svátky).
     Prohlížeč začne navazovat TCP+TLS spojení paralelně s parsováním HTML. --}}
<link rel="preconnect" href="https://api.openweathermap.org" crossorigin>
<link rel="preconnect" href="https://date.nager.at" crossorigin>

{{-- POZNÁMKA: Preload pro Inter font není zařazen, protože @font-face
     v style.css používá relativní cestu, kterou Vite v produkci hashuje.
     URL preloadu by se neshodlo a prohlížeč by font stáhl dvakrát.
     Font má font-display: swap — renderování neblokuje. --}}

{{-- Anti-FOUC: nastaví data-bs-theme na <html> dřív, než se vykreslí cokoliv.
     Druhá fáze (body.dark-mode) běží v partials/body-perf.blade.php. --}}
<script @if($perfNonce) nonce="{{ $perfNonce }}" @endif>
    (function () {
        try {
            var stored = localStorage.getItem('theme');
            var prefersDark = window.matchMedia &&
                window.matchMedia('(prefers-color-scheme: dark)').matches;
            var theme = stored || (prefersDark ? 'dark' : 'light');

            document.documentElement.setAttribute('data-bs-theme', theme);
            if (theme === 'dark') {
                document.documentElement.dataset.themePending = 'dark';
            }
        } catch (e) { /* localStorage zablokovaný (Safari private) — necháme světlé */ }
    })();
</script>