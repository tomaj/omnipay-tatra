<?php

declare(strict_types=1);

// The customer's browser: buys at the shop, pays (or not) at payment-simulator and comes back to the return URL.
// Prints what the shop answers at each step.

require __DIR__ . '/../_support/simulator.php';

$shop = rtrim(getenv('SHOP_URL') ?: 'http://127.0.0.1:8081', '/');

/**
 * @return array{status: int, location: ?string, body: string}
 */
function browse(string $url): array
{
    $body = (string) file_get_contents($url, false, stream_context_create(['http' => ['follow_location' => 0, 'ignore_errors' => true]]));
    $status = 0;
    $location = null;
    foreach ($http_response_header as $header) {
        if (preg_match('~^HTTP/\S+ (\d{3})~', $header, $match) === 1) {
            $status = (int) $match[1];
        } elseif (stripos($header, 'Location:') === 0) {
            $location = trim(substr($header, 9));
        }
    }

    return ['status' => $status, 'location' => $location, 'body' => trim($body)];
}

function checkout(string $shop, string $amount): array
{
    $checkout = browse("{$shop}/checkout?amount={$amount}");
    echo "checkout {$amount}: HTTP {$checkout['status']} to ", strtok((string) $checkout['location'], '?'), "\n";
    $page = payAtSimulator((string) $checkout['location']);
    echo "  simulator: {$page['verdict']}\n";

    return $page;
}

function back(string $label, string $url): void
{
    $answer = browse($url);
    printf("  %-22s HTTP %d %s\n", $label, $answer['status'], $answer['body']);
}

// 1. pays
$paid = checkout($shop, '24.90');
back('return (ok):', (string) $paid['ok']);
back('reload the return URL:', (string) $paid['ok']);
back('order page:', $shop . '/orders/2026101');

// 2. the payment fails, the customer edits RES=FAIL to RES=OK
$page = checkout($shop, '5.00');
back('edited to RES=OK:', str_replace('RES=FAIL', 'RES=OK', (string) $page['fail']));
back('return (fail):', (string) $page['fail']);

// 3. the bank times out
$page = checkout($shop, '7.50');
back('return (timeout):', (string) $page['timeout']);

// 4. someone sends order 2026101's successful callback with the VS of the failed order 2026102
echo "replay\n";
back('VS changed to 2026102:', str_replace('VS=2026101', 'VS=2026102', (string) $paid['ok']));
back('order page:', $shop . '/orders/2026102');
