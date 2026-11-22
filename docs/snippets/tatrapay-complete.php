<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Omnipay\Core\Sign\HmacSign;

$hmacKey = str_repeat('1', 128);
// What the bank appends to the return URL (signed here so that the example runs).
$_GET = ['VS' => '123456', 'RES' => 'OK', 'TID' => '1', 'TIMESTAMP' => '01022021214520'];
$_GET['HMAC'] = new HmacSign()->sign('10.00978123456OK101022021214520', $hmacKey);
$order = (object) ['vs' => '123456', 'amount' => '10.00'];

// #region snippet
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Omnipay;

// on https://shop.example/payment/return
$gateway = Omnipay::create('TatraPay');
$gateway->setMid('9999');
$gateway->setSharedSecret($hmacKey);

try {
    $response = $gateway->completePurchase([
        'vs' => $order->vs,         // the order you expect
        'amount' => $order->amount, // signed in HMAC mode
        'currency' => 'EUR',
    ])->send();
} catch (InvalidRequestException $e) {
    // wrong signature or another order's variable symbol: do not trust the request
    http_response_code(400);
    exit;
}

if ($response->isSuccessful()) {
    // RES=OK: mark the order as paid
} elseif ($response->isPending()) {
    // RES=TOUT: the bank confirms the result later
} else {
    // RES=FAIL
}
// #endregion snippet

assert($response->isSuccessful() && $response->getVs() === '123456');
echo $response->getRes(), "\n";
