<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Omnipay\Omnipay;

$gateway = Omnipay::create('ComfortPay');
$gateway->initialize(['ws' => '1234', 'terminalId' => '5678', 'certPath' => '/etc/tatrabanka/comfortpay.pem', 'certPass' => 'secret', 'testMode' => true]);

// #region cards
use Omnipay\ComfortPay\CardStatus;

// can the card still be charged?
$status = $gateway->checkCard(['cardId' => '5552'])->send()->getCardStatus(); // CardStatus::Ok, Fail or Unknown

// the result of an earlier charge
$transaction = $gateway->checkTransaction(['transactionId' => '20261122001'])->send();
$approved = $transaction->isSuccessful(); // transactionStatus 00

// cards that expire in a month (the day is ignored), and the expiration of given cards
$expired = $gateway->listOfExpired(['expDate' => '2026-12-31'])->send()->getExpires();         // [['id' => ...], ...]
$expirations = $gateway->listOfExpirePerId(['cardIds' => ['5551', '5552']])->send()->getExpires(); // [['id' => ..., 'date' => 'MMYY'], ...]

// forget a card
$removed = $gateway->unRegisterCard(['cardId' => '5551'])->send()->isSuccessful();
// #endregion cards

assert($status === CardStatus::Ok && $approved && count($expirations) === 2 && $removed);
echo "ok\n";
