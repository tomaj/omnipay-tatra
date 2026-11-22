<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

$hmacKey = str_repeat('1', 128);

// #region snippet
use Omnipay\Omnipay;

$gateway = Omnipay::create('CardPay');
$gateway->setMid('9999');
$gateway->setSharedSecret($hmacKey);
$gateway->setTestMode(true);

$response = $gateway->purchase([
    'amount' => '10.00',
    'currency' => 'EUR',
    'vs' => '123456',
    'rurl' => 'https://shop.example/payment/return',
    'name' => 'Jan Novak',          // letters without diacritics, digits, space and . - _ @
    'ipc' => '203.0.113.10',        // the customer's IP; taken from REMOTE_ADDR when omitted
    'rem' => 'jan@example.com',     // e-mail notification about the result
    'lang' => 'sk',
])->send();

$url = $response->getRedirectUrl();
// #endregion snippet

assert(str_starts_with($url, 'https://platby.tomaj.sk/payment/cardpay-hmac?PT=CardPay&MID=9999'));
echo $url, "\n";
