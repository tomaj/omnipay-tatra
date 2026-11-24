<?php

declare(strict_types=1);

// CardPay pre-authorization: block the amount on the card when the order is placed, capture it when the goods ship
// (or release it), and refund a captured payment. authorize() and cancelAuthorize() need an HMAC key.

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/../_support/simulator.php';

use Omnipay\CardPay\Gateway;
use Omnipay\CardPay\Message\CancelAuthorizeTxn;
use Symfony\Component\HttpFoundation\Request;

$config = ['mid' => '9999', 'sharedSecret' => str_repeat('1', 128), 'testMode' => true, 'testHost' => simulatorUrl()];
$order = ['vs' => '2026020', 'amount' => '120.00', 'currency' => 'EUR'];

// #region authorize
$gateway = new Gateway();
$gateway->initialize($config);

$response = $gateway->authorize([
    'amount' => $order['amount'],
    'currency' => $order['currency'],
    'vs' => $order['vs'],
    'rurl' => 'https://shop.example/payment/authorized',
    'name' => 'Jan Novak',
    'ipc' => '203.0.113.10',
    'tpay' => 'Y',                          // also register the card (ComfortPay contract)
    'tdsEmail' => 'jan.novak@example.com',  // 3-D Secure data: optional, every value you set is signed and sent
    'tdsBillCity' => 'Bratislava',
    'tdsBillCountry' => '703',
    'tdsBillZip' => '81101',
])->send();
// #endregion authorize

$data = $response->getRedirectData();
echo 'redirect: ', strtok($response->getRedirectUrl(), '?'), "\n";
echo "  TXN={$data['TXN']} TPAY={$data['TPAY']} TDS_EMAIL={$data['TDS_EMAIL']}\n";

$page = payAtSimulator($response->getRedirectUrl());
echo "simulator: {$page['verdict']}\n";

// #region complete
// on https://shop.example/payment/authorized
$returnGateway = new Gateway(null, Request::create((string) $page['ok']));
$returnGateway->initialize($config);

$authorization = $returnGateway->completeAuthorize([
    'vs' => $order['vs'],
    'amount' => $order['amount'],
    'currency' => $order['currency'],
])->send();

if ($authorization->isSuccessful()) {
    $tid = $authorization->getTransactionReference(); // store it with the order: capture and refund need it
}
// #endregion complete

printf("authorized: %s, TID %s, approval code %s, card %s, card id %s\n", $authorization->getRes(), $authorization->getTransactionReference(), $authorization->getAc(), $authorization->getCc(), $authorization->getCid());

// #region capture
// the goods ship: capture the blocked amount (the same or a lower one)
$capture = $gateway->cancelAuthorize([
    'amount' => '110.00',
    'vs' => $order['vs'],
    'tid' => $tid,
    'txn' => CancelAuthorizeTxn::ClosePreauthorization, // CPA
])->send();

echo 'capture 110.00: ', $capture->isSuccessful() ? 'OK' : "FAIL, error {$capture->getErrorCode()}", "\n";
// #endregion capture

// #region release
// or the order is cancelled: release the blocked amount
$release = $gateway->cancelAuthorize([
    'amount' => $order['amount'],
    'vs' => $order['vs'],
    'tid' => $tid,
    'txn' => CancelAuthorizeTxn::StornoPreauthorization, // SPA
])->send();
echo 'release: ', $release->getRes(), "\n";

// the customer returns the goods: refund the captured payment
$refund = $gateway->cancelAuthorize([
    'amount' => '110.00',
    'vs' => $order['vs'],
    'tid' => $tid,
    'txn' => 'CB', // the string value of CancelAuthorizeTxn::Chargeback works too
])->send();
echo 'refund: ', $refund->getRes(), "\n";
// #endregion release

// #region wrong-key
// a request signed with another key: the bank answers FAIL with an error code
$wrongKey = new Gateway();
$wrongKey->initialize(['sharedSecret' => str_repeat('2', 128)] + $config);
$failed = $wrongKey->cancelAuthorize(['amount' => '110.00', 'vs' => $order['vs'], 'tid' => $tid, 'txn' => CancelAuthorizeTxn::ClosePreauthorization])->send();
echo "wrong key: {$failed->getRes()}, error code {$failed->getErrorCode()}\n";
// #endregion wrong-key
