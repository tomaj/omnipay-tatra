<?php

use Omnipay\Omnipay;

require_once __DIR__ . '/vendor/autoload.php';

$gateway = Omnipay::create('TatraPay');

$gateway->setMid(1111);
$gateway->setSharedSecret('11111111');

$gateway->setTestMode(true);


$response = $gateway->purchase([
	'amount' => '10.00',
	'currency' => 'EUR',
	'VS' => '123456',
	'CS' => '0321',
	'rurl' => 'http://test.localhost.sk/'
])->send();

if ($response->isSuccessful()) {
    
    // Payment was successful
    print_r($response);

} elseif ($response->isRedirect()) {
    
    // Redirect to offsite payment gateway
    echo($response->getRedirectUrl() . "\n");
    //$response->redirect();

} else {

    // Payment failed
    echo $response->getMessage();
}


