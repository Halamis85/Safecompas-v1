<?php
// app/Support/CspNonce.php
// Per-request CSP nonce. Generuje se LAZY při prvním přístupu a pak je
// dostupný v celém request cyklu - middleware, Blade, AppServiceProvider
// (pro Vite tagy).

namespace App\Support;

use Illuminate\Support\Str;

class CspNonce
{
    private ?string $value = null;

    /**
     * Vrátí aktuální nonce pro daný request.
     * Pokud ještě neexistuje, vygeneruje ho. Druhé volání vrací stejnou hodnotu.
     */
    public function get(): string
    {
        if ($this->value === null) {
            // 32 bajtů = ~43 znaků base64. Dostatečně silné proti hádání.
            $this->value = Str::random(32);
        }
        return $this->value;
    }

    /**
     * Pro testy / debug - umožní zjistit, zda byl nonce už použit
     * (a tedy je v odpovědi).
     */
    public function wasIssued(): bool
    {
        return $this->value !== null;
    }
}
