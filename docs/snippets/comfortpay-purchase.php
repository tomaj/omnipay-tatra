<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Omnipay\Core\Sign\HmacSign;

$hmacKey = str_repeat('1', 128);

// #region purchase
use Omnipay\Omnipay;

$gateway = Omnipay::create('ComfortPay');
$gateway->setMid('9999');
$gateway->setSharedSecret($hmacKey);
$gateway->setTestMode(true);

$response = $gateway->purchase([
    'amount' => '10.00',
    'currency' => 'EUR',
    'vs' => '123456',
    'rurl' => 'https://shop.example/payment/return',
    'name' => 'Jan Novak',
    'ipc' => '203.0.113.10',
])->send();
$url = $response->getRedirectUrl(); // TPAY=Y: the bank registers the card
// #endregion purchase

$_GET = ['VS' => '123456', 'RES' => 'OK', 'AC' => '123', 'TRES' => 'OK', 'CID' => '5551', 'CC' => '4405****1111', 'RC' => '00', 'TID' => '1', 'TIMESTAMP' => '01022021214520'];
$_GET['HMAC'] = new HmacSign()->sign('10.00978123456OK123OK55514405****1111001' . '01022021214520', $hmacKey);
$gateway = Omnipay::create('ComfortPay');
$gateway->setMid('9999');
$gateway->setSharedSecret($hmacKey);

// #region complete
$response = $gateway->completePurchase(['vs' => '123456', 'amount' => '10.00', 'currency' => 'EUR'])->send();
if ($response->isSuccessful()) {
    $cardId = $response->getCid(); // store it with the customer to charge the card later
}
// #endregion complete

assert(str_starts_with($url, 'https://platby.tomaj.sk/payment/comfortpay-hmac?') && $cardId === '5551');
echo "card {$cardId}\n";
