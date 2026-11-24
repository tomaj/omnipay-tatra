<?php

declare(strict_types=1);

// TatraPay with an HMAC key: redirect the customer to payment-simulator, follow each of the bank's answers back to
// the return URL and verify it with completePurchase(). Then try a forged and a foreign callback, and pay with a
// specific symbol (SS) on an HMAC and an AES key.

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/../_support/simulator.php';

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\TatraPay\Gateway;
use Symfony\Component\HttpFoundation\Request;

// #region config
$config = [
    'mid' => '9999',                        // merchant id from Tatra banka
    'sharedSecret' => str_repeat('1', 128), // the simulator's HMAC test key
    'testMode' => true,                     // redirect to the simulator
    'testHost' => simulatorUrl(),           // http://127.0.0.1:8080
];
$order = ['vs' => '2026001', 'amount' => '24.90', 'currency' => 'EUR'];
// #endregion config

// #region purchase
$gateway = new Gateway();
$gateway->initialize($config);

$response = $gateway->purchase([
    'amount' => $order['amount'],
    'currency' => $order['currency'],
    'vs' => $order['vs'],
    'rurl' => 'https://shop.example/payment/return',
    'rem' => 'orders@shop.example',
    'lang' => 'sk',
])->send();

$redirectUrl = $response->getRedirectUrl(); // $response->redirect() in a web request
// #endregion purchase

echo 'redirect: ', strtok($redirectUrl, '?'), "\n";
$redirectData = $response->getRedirectData();
foreach (['PT', 'MID', 'AMT', 'CURR', 'VS', 'RURL', 'REM', 'LANG'] as $field) {
    printf("  %-9s %s\n", $field, $redirectData[$field]);
}
printf("  %-9s %d hex characters\n", 'HMAC', strlen($redirectData['HMAC']));

$page = payAtSimulator($redirectUrl);
echo "simulator: {$page['verdict']}\n";

// #region complete
function completeTatraPay(array $config, array $order, string $returnUrl): string
{
    // the customer's request to the return URL; in a web application Omnipay reads it from the globals
    $gateway = new Gateway(null, Request::create($returnUrl));
    $gateway->initialize($config);

    try {
        $response = $gateway->completePurchase([
            'vs' => $order['vs'],               // the order you expect
            'ss' => $order['ss'] ?? null,       // the specific symbol sent with purchase(), signed in the return
            'amount' => $order['amount'],       // signed with HMAC keys
            'currency' => $order['currency'],
        ])->send();
    } catch (InvalidRequestException $e) {
        return 'rejected: ' . $e->getMessage();
    }

    return match (true) {
        $response->isSuccessful() => "paid, VS {$response->getVs()}",
        $response->isPending() => 'pending (RES=TOUT), wait for the bank',
        default => "not paid (RES={$response->getRes()})",
    };
}
// #endregion complete

foreach (['ok', 'fail', 'timeout'] as $answer) {
    printf("%-8s -> %s\n", $answer, completeTatraPay($config, $order, (string) $page[$answer]));
}

// #region forged
// the customer edits the query string: RES=FAIL becomes RES=OK
$forged = str_replace('RES=FAIL', 'RES=OK', (string) $page['fail']);
echo 'forged   -> ', completeTatraPay($config, $order, $forged), "\n";

// a correctly signed answer, but for another order
echo 'foreign  -> ', completeTatraPay($config, ['vs' => '2026002'] + $order, (string) $page['ok']), "\n";
// #endregion forged

// #region specific-symbol
// a specific symbol is signed in the request and in the return (AES/DES: VS SS RES, HMAC: AMT CURR VS SS CS RES ...)
$aesConfig = ['sharedSecret' => str_repeat('1', 64)] + $config; // the simulator's AES test key
foreach (['HMAC' => $config, 'AES' => $aesConfig] as $keyType => $keyConfig) {
    $withSs = ['ss' => '0308'] + $order;
    $gateway = new Gateway();
    $gateway->initialize($keyConfig);
    $response = $gateway->purchase($withSs + ['rurl' => 'https://shop.example/payment/return'])->send();

    $page = payAtSimulator($response->getRedirectUrl());
    echo "{$keyType} with SS {$response->getRedirectData()['SS']}: {$page['verdict']}\n";
    foreach (['ok', 'fail'] as $answer) {
        printf("  %-6s -> %s\n", $answer, completeTatraPay($keyConfig, $withSs, (string) $page[$answer]));
    }
    // the same return verified without the specific symbol fails: SS is part of the signature
    printf("  %-6s -> %s\n", 'no SS', completeTatraPay($keyConfig, $order, (string) $page['ok']));
}
// #endregion specific-symbol
