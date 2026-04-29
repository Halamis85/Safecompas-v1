<?php
// tests/Feature/SecurityHeadersTest.php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_csp_header_is_set(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }

    public function test_csp_does_not_use_unsafe_inline_for_scripts(): void
    {
        $csp = $this->get('/login')->headers->get('Content-Security-Policy');

        // Najdeme script-src direktivu (ne script-src-elem)
        $this->assertMatchesRegularExpression(
            '/script-src\s+(?!-elem)[^;]*/',
            $csp,
            'CSP musí mít script-src direktivu'
        );

        // Vytáhneme jen script-src (ne script-src-elem)
        preg_match('/(?:^|;\s*)script-src\s+([^;]+)/', $csp, $matches);
        $scriptSrc = $matches[1] ?? '';

        $this->assertStringNotContainsString("'unsafe-inline'", $scriptSrc,
            "script-src nesmí obsahovat 'unsafe-inline' (#3 z analýzy)");
    }

    public function test_csp_contains_nonce_for_scripts(): void
    {
        $csp = $this->get('/login')->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression(
            "/script-src[^;]*'nonce-[A-Za-z0-9_-]+'/",
            $csp,
            "script-src musí obsahovat 'nonce-...' direktivu"
        );
    }

    public function test_csp_uses_strict_dynamic(): void
    {
        $csp = $this->get('/login')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("'strict-dynamic'", $csp,
            "script-src by měl používat 'strict-dynamic' pro chunky Vite");
    }

    public function test_required_security_headers_are_present(): void
    {
        $response = $this->get('/login');

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertNotEmpty($response->headers->get('Referrer-Policy'));
        $this->assertNotEmpty($response->headers->get('Permissions-Policy'));
    }

    public function test_powered_by_header_is_removed(): void
    {
        $response = $this->get('/login');
        $this->assertNull($response->headers->get('X-Powered-By'));
    }

    public function test_csp_blocks_objects(): void
    {
        $csp = $this->get('/login')->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("object-src 'none'", $csp,
            "object-src 'none' chrání před plugin-based XSS");
    }
}
