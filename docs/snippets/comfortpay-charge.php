<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

$cardId = '5551';

// #region charge
use Omnipay\ComfortPay\Exception\ComfortPaySoapFault;
use Omnipay\ComfortPay\TransactionType;
use Omnipay\Omnipay;

$gateway = Omnipay::create('ComfortPay');
$gateway->initialize([
    'ws' => '1234',                         // merchant id of the web service
    'terminalId' => '5678',
    'certPath' => '/etc/tatrabanka/comfortpay.pem', // client certificate and private key
    'certPass' => 'secret',
    'testMode' => true,                     // test mode answers without calling the bank
]);

try {
    $response = $gateway->charge([
        'transactionId' => '20261122001',   // your unique id
        'transactionType' => TransactionType::Purchase,
        'referedCardId' => $cardId,
        'amount' => '9.90',
        'currency' => 'EUR',
        'vs' => '123457',
        'ss' => '1',
    ])->send();
} catch (ComfortPaySoapFault $fault) {
    // the web service failed (not a decline): $fault->getErrorCode()
    throw $fault;
}

if ($response->isSuccessful()) {
    $approval = $response->getTransactionApproval();
} elseif ($response->isPending()) {
    // error code 50001: check the result later with checkTransaction()
} else {
    $reason = $response->getErrorCode() ?? $response->getTransactionStatus(); // e.g. 50051 unknown card, 05 declined
}
// #endregion charge

assert($response->isSuccessful());
echo "charged, approval {$approval}\n";
