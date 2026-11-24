<?php

declare(strict_types=1);

// Prepended to every inline ```php block of README.md, UPGRADE-5.0.md and docs/ by tests/DocsSnippetsTest.php:
// the variables those blocks take for granted.

require __DIR__ . '/../../vendor/autoload.php';

use Omnipay\ComfortPay\TransactionType;
use Omnipay\Core\Sign\HmacSign;
use Omnipay\Omnipay;

$hmacKey = str_repeat('1', 128);
$order = (object) ['vs' => '123456', 'amount' => '10.00'];

// the bank's signed return for that order, as on the return URL
$_GET = ['VS' => '123456', 'RES' => 'OK', 'AMT' => '10.00', 'CURR' => '978', 'TID' => '1', 'TIMESTAMP' => '24112026101500'];
$_GET['HMAC'] = new HmacSign()->sign('10.00978123456OK124112026101500', $hmacKey);

$gateway = Omnipay::create('TatraPay');
$gateway->initialize(['mid' => '9999', 'sharedSecret' => $hmacKey, 'testMode' => true]);

$comfortPay = Omnipay::create('ComfortPay');
$comfortPay->initialize(['ws' => '1234', 'terminalId' => '5678', 'certPath' => '/dev/null', 'certPass' => 'x', 'testMode' => true]);
$chargeParameters = [
    'transactionId' => '202612001',
    'transactionType' => TransactionType::Purchase,
    'referedCardId' => '111111',
    'amount' => '9.90',
    'currency' => 'EUR',
    'vs' => '2026030',
    'ss' => '202612',
];
