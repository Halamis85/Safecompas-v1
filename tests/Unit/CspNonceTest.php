<?php
// tests/Unit/CspNonceTest.php

namespace Tests\Unit;

use App\Support\CspNonce;
use PHPUnit\Framework\TestCase;

class CspNonceTest extends TestCase
{
    public function test_get_returns_non_empty_string(): void
    {
        $nonce = new CspNonce();
        $value = $nonce->get();

        $this->assertIsString($value);
        $this->assertNotEmpty($value);
    }

    public function test_get_is_idempotent_within_single_instance(): void
    {
        $nonce = new CspNonce();
        $a = $nonce->get();
        $b = $nonce->get();

        $this->assertSame($a, $b,
            'Stejná instance musí vrátit identickou nonce - jinak script <-> CSP header by se rozcházely.');
    }

    public function test_different_instances_produce_different_nonces(): void
    {
        $a = (new CspNonce())->get();
        $b = (new CspNonce())->get();

        // Random kolize 32-znakové random stringu jsou statisticky nemožné
        $this->assertNotSame($a, $b,
            'Různé instance (= různé requesty) musí mít různé nonces.');
    }

    public function test_was_issued_reflects_state(): void
    {
        $nonce = new CspNonce();
        $this->assertFalse($nonce->wasIssued());

        $nonce->get();
        $this->assertTrue($nonce->wasIssued());
    }

    public function test_nonce_is_long_enough(): void
    {
        $value = (new CspNonce())->get();
        // Str::random(32) generuje 32 znaků (URL-safe base64-like)
        $this->assertGreaterThanOrEqual(32, strlen($value));
    }
}
