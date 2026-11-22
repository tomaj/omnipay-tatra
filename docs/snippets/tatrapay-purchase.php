<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

$hmacKey = str_repeat('1', 128); // the 128-character key from Tatra banka

// #region snippet
use Omnipay\Omnipay;

$gateway = Omnipay::create('TatraPay');
$gateway->setMid('9999');
$gateway->setSharedSecret($hmacKey);
$gateway->setTestMode(true); // remove in production

$response = $gateway->purchase([
    'amount' => '10.00',
    'currency' => 'EUR',
    'vs' => '123456',
    'rurl' => 'https://shop.example/payment/return',
])->send();

if ($response->isRedirect()) {
    // $response->redirect(); sends the Location header and exits
    $url = $response->getRedirectUrl();
}
// #endregion snippet

assert(str_starts_with($url, 'https://platby.tomaj.sk/payment/tatrapay-hmac?PT=TatraPay&MID=9999&CURR=978&VS=123456'));
echo $url, "\n";
