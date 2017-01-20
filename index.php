<?php

use Omnipay\Omnipay;

require_once __DIR__ . '/vendor/autoload.php';

$tatra = Omnipay::create('Tatra');

$tatra->setMid('aoj');
$tatra->setSharedSecret('87651234');

$response = $tatra->purchase(
    [
        'amt' => '10.00',
        'rurl' => 'http://sme.sk/',
        'curr' => 978,
        'cs' => '0308',
        'vs' => '3232534532',
    ]
)->send();

// Process response
if ($response->isSuccessful()) {
    // Payment was successful
    print_r($response);
} elseif ($response->isRedirect()) {
    // Redirect to offsite payment gateway
    $response->redirect();
} else {
    // Payment failed
    echo $response->getMessage();
}

var_dump($tatra);
