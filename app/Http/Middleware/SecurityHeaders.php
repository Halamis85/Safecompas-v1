<?php


namespace App\Http\Middleware;

use App\Support\CspNonce;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecurityHeaders
{
    public function __construct(private CspNonce $nonce)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // === Detekce běžícího Vite dev serveru ===
        // Soubor public/hot vytváří Vite POUZE když běží `npm run dev`.
        // Když neběží, dev Vite zdroje vůbec nemá smysl do CSP přidávat —
        // jen by zbytečně rozšiřovaly povolený scope.
        $hotFile      = public_path('hot');
        $viteRunning  = app()->environment('local') && is_file($hotFile);

        // Vite se na Windows/localhost může občas přihlásit přes IPv6 `[::1]`.
        // Povolujeme všechny lokální varianty, ale jen když existuje public/hot.
        $viteServer = $viteRunning ? 'http://127.0.0.1:5173 http://localhost:5173 http://[::1]:5173' : '';
        $viteWs     = $viteRunning ? 'ws://127.0.0.1:5173 ws://localhost:5173 ws://[::1]:5173'   : '';

        // Nonce vezmeme z singletonu - pokud žádný Blade @vite ho ještě
        // nepoužil, $this->nonce->get() ho teď vyrobí. Pro neutrální
        // odpovědi (např. JSON API) to ničemu neuškodí.
        $nonceValue = $this->nonce->get();

        // === Skripty: žádné 'unsafe-inline'! Jen self + nonce + dev Vite ===
        // 'strict-dynamic' znamená: skripty s nonce mohou dynamicky načíst
        // další skripty (Vite chunky), aniž bychom museli povolovat každý
        // hash. Moderní prohlížeče tomu rozumí.
        $scriptSrc = trim("'self' 'nonce-{$nonceValue}' 'strict-dynamic' {$viteServer}");

        // === Styly: Bootstrap a některé widgety píší inline style atributy
        // (modal pozicování, tooltips). 'unsafe-inline' je tady realistická
        // cena za použití Bootstrapu. Pokud jednou Bootstrap přepneme za
        // něco vlastního, pak lze přejít na 'nonce-...' i tady.
        $styleSrc = trim("'self' 'unsafe-inline' {$viteServer}");

        $connectSrc = trim(
            "'self' https://api.openweathermap.org https://date.nager.at {$viteServer} {$viteWs}"
        );

        $imgSrc  = trim("'self' data: blob: https: {$viteServer}");
        $fontSrc = trim("'self' data: {$viteServer}");

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "script-src-elem {$scriptSrc}",
            "style-src {$styleSrc}",
            "style-src-elem {$styleSrc}",
            "img-src {$imgSrc}",
            "font-src {$fontSrc}",
            "connect-src {$connectSrc}",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);

        // CSP nasazujeme JEN na HTML odpovědi.
        // Důvody:
        //   1) JSON API odpovědi nemají DOM, CSP nemá co prosadit.
        //   2) Binární downloady (XLSX/PDF/CSV) by neměly mít navíc CSP
        //      header — některé prohlížeče si na tom zakládají kontrolu
        //      Content-Disposition a CSP je v tomto kontextu šum.
        //   3) StreamedResponse (CSV apod.) — stejný důvod.
        if ($this->shouldApplyCsp($response)) {
            $response->headers->set('Content-Security-Policy', $csp);
        }

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

    /**
     * CSP má smysl jen pro HTML odpovědi (a starší fallback pro responses
     * bez explicitního Content-Type, což jsou typicky redirecty).
     *
     * Binární a JSON odpovědi se přeskočí.
     */
    private function shouldApplyCsp(Response $response): bool
    {
        // Binary file download (Excel exporty atd.) — CSP nemá co prosadit
        if ($response instanceof BinaryFileResponse) {
            return false;
        }

        // Streamed response (např. velké CSV, čištění logů) — totéž
        if ($response instanceof StreamedResponse) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        // Žádný content-type → nech projít (bývá to redirect 302/303)
        if ($contentType === '') {
            return true;
        }

        // JSON API → CSP nemá smysl
        if (str_contains($contentType, 'application/json')) {
            return false;
        }

        // Cokoli ostatní co není HTML (text/plain, application/xml,
        // application/octet-stream, image/* atd.) také vynech.
        if (
            !str_contains($contentType, 'text/html') &&
            !str_contains($contentType, 'application/xhtml')
        ) {
            return false;
        }

        return true;
    }
}
