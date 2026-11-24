<?php

declare(strict_types=1);

// CardPay card payment with each key type the simulator knows: HMAC (current keys) and AES (older contracts).
// The same purchase() and completePurchase() code works for both; the length of the key selects the algorithm.

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/../_support/simulator.php';

use Omnipay\CardPay\Gateway;
use Omnipay\Common\Exception\InvalidRequestException;
use Symfony\Component\HttpFoundation\Request;

$keys = [
    'HMAC' => str_repeat('1', 128), // 128 hex characters: HMAC-SHA256
    'AES' => str_repeat('1', 64),   // 64 hex characters: AES, see the Signatures page
];
$order = ['vs' => '2026010', 'amount' => '59.00', 'currency' => 'EUR'];

foreach ($keys as $type => $key) {
    $config = ['mid' => '9999', 'sharedSecret' => $key, 'testMode' => true, 'testHost' => simulatorUrl()];

    // #region purchase
    $gateway = new Gateway();
    $gateway->initialize($config);

    $response = $gateway->purchase([
        'amount' => $order['amount'],
        'currency' => $order['currency'],
        'vs' => $order['vs'],
        'rurl' => 'https://shop.example/payment/return',
        'name' => 'Jan Novak',       // letters without diacritics, digits, space and . - _ @
        'ipc' => '203.0.113.10',     // the customer's IP address; REMOTE_ADDR when omitted
    ])->send();
    // #endregion purchase

    $data = $response->getRedirectData();
    echo "{$type} key\n";
    echo '  redirect:  ', strtok($response->getRedirectUrl(), '?'), "\n";
    echo '  signed in: ', isset($data['HMAC']) ? 'HMAC + TIMESTAMP' : 'SIGN', "\n";

    $page = payAtSimulator($response->getRedirectUrl());
    echo "  simulator: {$page['verdict']}\n";

    foreach (['ok', 'fail'] as $answer) {
        // #region complete
        $returnGateway = new Gateway(null, Request::create((string) $page[$answer]));
        $returnGateway->initialize($config);
        try {
            $result = $returnGateway->completePurchase([
                'vs' => $order['vs'],
                'amount' => $order['amount'],      // signed with HMAC keys only
                'currency' => $order['currency'],
            ])->send();
            $status = $result->isSuccessful() ? 'paid' : "declined (RES={$result->getRes()})";
        } catch (InvalidRequestException $e) {
            $status = 'rejected: ' . $e->getMessage();
        }
        // #endregion complete
        printf("  %-4s -> %s\n", $answer, $status);
    }
}

// #region name
try {
    $gateway->purchase(['name' => 'Ján Novák'] + $order + ['rurl' => 'https://shop.example/payment/return', 'ipc' => '203.0.113.10'])->send();
} catch (InvalidRequestException $e) {
    echo 'name with diacritics: ', $e->getMessage(), "\n";
}

$name = preg_replace('/[^0-9a-zA-Z .\-_@]/', '', (string) iconv('UTF-8', 'ASCII//TRANSLIT', 'Ján Novák'));
echo "transliterated: {$name}\n";
// #endregion name
