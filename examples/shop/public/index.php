<?php

declare(strict_types=1);

// A minimal shop on the PHP built-in server: php -S 127.0.0.1:8081 -t public public/index.php
//   GET /checkout?amount=24.90  creates an order and redirects the customer to TatraPay (payment-simulator)
//   GET /payment/return?...      the bank sends the customer back here: verify, then mark the order paid
//   GET /orders/<vs>             the order's status

require __DIR__ . '/../vendor/autoload.php';

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Omnipay;
use Omnipay\TatraPay\Gateway;
use Shop\Orders;

$shopUrl = rtrim(getenv('SHOP_URL') ?: 'http://127.0.0.1:8081', '/');
$orders = new Orders(__DIR__ . '/../var/orders.json');

// #region gateway
function tatraPay(): Gateway
{
    /** @var Gateway $gateway */
    $gateway = Omnipay::create('TatraPay'); // reads the current request (the bank's query string) from the globals
    $gateway->initialize([
        'mid' => getenv('TATRA_MID') ?: '9999',
        'sharedSecret' => getenv('TATRA_SHARED_SECRET') ?: str_repeat('1', 128), // never commit the real key
        'testMode' => true,
        'testHost' => getenv('SIMULATOR_URL') ?: 'http://127.0.0.1:8080',
    ]);

    return $gateway;
}
// #endregion gateway

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
header('Content-Type: text/plain; charset=utf-8');

// #region checkout
if ($path === '/checkout') {
    $order = $orders->create(is_string($_GET['amount'] ?? null) ? $_GET['amount'] : '10.00');

    $response = tatraPay()->purchase([
        'amount' => $order['amount'],
        'currency' => $order['currency'],
        'vs' => $order['vs'],
        'rurl' => "{$shopUrl}/payment/return",
    ])->send();

    $response->redirect(); // 302 to the bank's payment page, then exit
}
// #endregion checkout

// #region return
if ($path === '/payment/return') {
    // find the order by the VS the bank returned, then verify the callback against that order
    $order = $orders->find(is_string($_GET['VS'] ?? null) ? $_GET['VS'] : '');
    if ($order === null) {
        http_response_code(404);
        exit("unknown order\n");
    }

    try {
        $result = tatraPay()->completePurchase([
            'vs' => $order['vs'],
            'amount' => $order['amount'],
            'currency' => $order['currency'],
        ])->send();
    } catch (InvalidRequestException $e) {
        // forged, edited or broken callback: change nothing
        http_response_code(400);
        exit("rejected: {$e->getMessage()}\n");
    }

    if ($order['status'] === 'paid') {
        exit("order {$order['vs']} was already paid\n"); // the customer reloaded the page: do not ship twice
    }

    $status = match (true) {
        $result->isSuccessful() => 'paid',
        $result->isPending() => 'pending',
        default => 'failed',
    };
    $orders->setStatus($order['vs'], $status);
    exit("order {$order['vs']}: {$status}\n");
}
// #endregion return

if (preg_match('~^/orders/(\d+)$~', $path, $match) === 1) {
    $order = $orders->find($match[1]);
    exit($order === null ? "unknown order\n" : "order {$order['vs']}: {$order['status']}, {$order['amount']} {$order['currency']}\n");
}

http_response_code(404);
echo "not found\n";
