<?php

declare(strict_types=1);

// A monthly subscription with ComfortPay: the first payment registers the card at payment-simulator, the following
// months charge the registered card through the web service (answered locally in test mode), and the card is removed
// when the customer cancels.

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/../_support/simulator.php';

use Omnipay\ComfortPay\Gateway;
use Omnipay\ComfortPay\TransactionType;
use Symfony\Component\HttpFoundation\Request;

// #region config
$config = [
    'mid' => '9999',
    'sharedSecret' => str_repeat('1', 128),   // HMAC key for the redirect payment
    'ws' => '1234',                           // merchant id of the web service
    'terminalId' => '5678',                   // terminal id of the web service
    'certPath' => '/etc/tatrabanka/comfortpay.pem', // client certificate + private key (not read in test mode)
    'certPass' => 'secret',
    'testMode' => true,
    'testHost' => simulatorUrl(),
];
// #endregion config
$subscription = ['vs' => '2026030', 'amount' => '9.90', 'currency' => 'EUR'];

// #region register
$gateway = new Gateway();
$gateway->initialize($config);

$response = $gateway->purchase([
    'amount' => $subscription['amount'],
    'currency' => $subscription['currency'],
    'vs' => $subscription['vs'],
    'rurl' => 'https://shop.example/subscription/return',
    'name' => 'Jan Novak',
    'ipc' => '203.0.113.10',
])->send(); // TPAY=Y: the bank registers the card
// #endregion register

$data = $response->getRedirectData();
echo 'redirect: ', strtok($response->getRedirectUrl(), '?'), " (TPAY={$data['TPAY']})\n";
$page = payAtSimulator($response->getRedirectUrl());
echo "simulator: {$page['verdict']}\n";

// #region return
$returnGateway = new Gateway(null, Request::create((string) $page['ok']));
$returnGateway->initialize($config);

$first = $returnGateway->completePurchase([
    'vs' => $subscription['vs'],
    'amount' => $subscription['amount'],
    'currency' => $subscription['currency'],
])->send();

if ($first->isSuccessful()) {
    $cardId = $first->getCid(); // store it with the subscription, never the card number
}
// #endregion return
echo "first payment: {$first->getRes()}, card {$first->getCc()} registered as {$cardId}\n";

// #region charge
// a month later, from a cron job: charge the registered card
$charge = $gateway->charge([
    'transactionId' => '202612001',          // your unique id of this charge
    'transactionType' => TransactionType::Purchase,
    'referedCardId' => $cardId,
    'amount' => $subscription['amount'],
    'currency' => $subscription['currency'],
    'vs' => $subscription['vs'],
    'ss' => '202612',                        // vs and ss, or e2eReference
])->send();

if ($charge->isSuccessful()) {
    echo "charged {$subscription['amount']}: transaction {$charge->getTransactionId()}, status {$charge->getTransactionStatus()}, approval {$charge->getTransactionApproval()}\n";
} elseif ($charge->isPending()) {
    echo "timeout: ask checkTransaction() later\n";
} else {
    echo 'declined: ', $charge->getErrorCode() ?? $charge->getTransactionStatus(), "\n";
}
// #endregion charge

// test mode declines an even card id, so the decline branch runs too
$declined = $gateway->charge(['referedCardId' => '111112', 'transactionId' => '202612002'] + $charge->getRequest()->getParameters())->send();
echo "even card id: declined, status {$declined->getTransactionStatus()}\n";

// #region manage
$transaction = $gateway->checkTransaction(['transactionId' => '202612001'])->send();
echo 'check transaction: ', $transaction->isSuccessful() ? 'approved' : 'not approved', "\n";

$card = $gateway->checkCard(['cardId' => $cardId])->send();
echo "check card: {$card->getCardStatus()?->name}\n"; // test mode: Ok for an even card id, Fail for an odd one

$expirations = $gateway->listOfExpirePerId(['cardIds' => [$cardId]])->send()->getExpires();
echo "card expires: {$expirations[0]['date']} (MMYY)\n";

// the customer cancels the subscription
$removed = $gateway->unRegisterCard(['cardId' => $cardId])->send();
echo 'unregister card: ', $removed->isSuccessful() ? 'removed' : 'not removed', "\n";
// #endregion manage
