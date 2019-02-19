<?php

use Omnipay\Omnipay;

require_once __DIR__ . '/vendor/autoload.php';

// $gateway = Omnipay::create('ComfortPay');
$gateway = Omnipay::create('TatraPay');
// $gateway = Omnipay::create('CardPay');

$gateway->setMid(1111);
// $gateway->setSharedSecret('11111111');
// $gateway->setSharedSecret('1111111111111111111111111111111111111111111111111111111111111111');
$gateway->setSharedSecret('11111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111');

// $gateway->setTerminalId(1232);
// $gateway->setWs(12323);

// var_dump($gateway->getTerminalId()); die();

$gateway->setTestMode(true);

$response = $gateway->purchase([
    'amount' => '10.00',
    'currency' => 'EUR',
    // 'ipc' => 'a',
    // 'name' => 'ahoj',
    'VS' => '123456',
    'CS' => '0321',
    'rurl' => 'http://localhost:4444/testserver.php',
])->send();

// $response = $gateway->checkCard([
// 	'cardId' => '122',
// ])->send();


// $response = $gateway->listOfExpirePerId([
// 	'cardIds' => ['122', '123421', '2354234'],
// ])->send();

// $response = $gateway->charge([
// 	'transactionId' => '555555',
// 	'cid' => '123',
// 	'amount' => '10.00',
// 	'currency' => 'EUR',
// 	'VS' => '123455',
// 	'CS' => '1234214',
// ])->send();

if ($response->isSuccessful()) {
    
    // Payment was successful
    // print_r($response);
    echo "OK\n";

} elseif ($response->isRedirect()) {
    
    // Redirect to offsite payment gateway
    echo($response->getRedirectUrl() . "\n");
    //$response->redirect();

} else {
	echo "ERROR\n";
    // Payment failed
    echo $response->getMessage();
}
