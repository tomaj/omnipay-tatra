<?php

declare(strict_types=1);

// Plays the customer at a payment-simulator (https://github.com/tomaj/payment-simulator): opens the payment page the
// shop redirected to and picks one of the bank's answers. Shared by the examples; not part of the library.

/**
 * The simulator the examples send their payments to (SIMULATOR_URL, default http://127.0.0.1:8080).
 */
function simulatorUrl(): string
{
    $url = getenv('SIMULATOR_URL');

    return rtrim(is_string($url) && $url !== '' ? $url : 'http://127.0.0.1:8080', '/');
}

// #region pay
/**
 * Opens the simulator's payment page and returns what the customer sees: whether the simulator accepted the request
 * signature, and the three signed links back to the shop.
 *
 * @return array{verdict: string, ok: ?string, fail: ?string, timeout: ?string}
 */
function payAtSimulator(string $redirectUrl): array
{
    $html = @file_get_contents($redirectUrl, false, stream_context_create(['http' => ['ignore_errors' => true]]));
    if ($html === false) {
        throw new RuntimeException("Cannot open {$redirectUrl}: is payment-simulator running at " . simulatorUrl() . '?');
    }

    $verdict = 'no verdict';
    foreach (['Sign OK', 'Wrong sign', 'Missing parameters'] as $candidate) {
        if (str_contains($html, $candidate)) {
            $verdict = $candidate;
            break;
        }
    }

    $links = ['ok' => null, 'fail' => null, 'timeout' => null];
    preg_match_all('~<a href="([^"]+)"[^>]*>continue (ok|fail|timeout)</a>~', $html, $matches, PREG_SET_ORDER);
    foreach ($matches as [, $href, $result]) {
        $links[$result] = html_entity_decode($href, ENT_QUOTES | ENT_HTML5);
    }

    return ['verdict' => $verdict] + $links;
}
// #endregion pay

