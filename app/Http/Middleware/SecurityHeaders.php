<?php


namespace App\Http\Middleware;

use App\Support\CspNonce;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function __construct(private CspNonce $nonce)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isLocal    = app()->environment('local');
        $viteServer = $isLocal ? 'http://127.0.0.1:5173 http://localhost:5173 http://[::1]:5173' : '';
        $viteWs     = $isLocal ? 'ws://127.0.0.1:5173 ws://localhost:5173 ws://[::1]:5173' : '';

        // Nonce vezmeme z singletonu - pokud žádný Blade @vite ho ještě
        // nepoužil, $this->nonce->get() ho teď vyrobí. Pro neutrální
        // odpovědi (např. JSON API) to ničemu neuškodí.
        $nonceValue = $this->nonce->get();

        // === Skripty: žádné 'unsafe-inline'! Jen self + nonce + dev Vite ===
        // 'strict-dynamic' znamená: skripty s nonce mohou dynamicky načíst
        // další skripty (Vite chunky), aniž bychom museli povolovat každý
        // hash. Moderní prohlížeče tomu rozumí.
        $scriptSrc = "'self' 'nonce-{$nonceValue}' 'strict-dynamic' {$viteServer}";

        // === Styly: Bootstrap a některé widgety píší inline style atributy
        // (modal pozicování, tooltips). 'unsafe-inline' je tady realistická
        // cena za použití Bootstrapu. Pokud jednou Bootstrap přepneme za
        // něco vlastního, pak lze přejít na 'nonce-...' i tady.
        $styleSrc = "'self' 'unsafe-inline' {$viteServer}";

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "script-src-elem {$scriptSrc}",
            "style-src {$styleSrc}",
            "style-src-elem {$styleSrc}",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:",
            "connect-src 'self' https://api.openweathermap.org https://date.nager.at {$viteWs}",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // HSTS jen v production přes HTTPS
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Schovat info o serveru
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
