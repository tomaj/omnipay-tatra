<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use GuzzleHttp\Psr7\Response;
use Http\Mock\Client as MockClient;
use Omnipay\CardPay\Gateway;
use Omnipay\Common\Http\Client;
use Omnipay\Core\Sign\HmacSign;
use Symfony\Component\HttpFoundation\Request;

$hmacKey = str_repeat('1', 128);
$mock = new MockClient();
$mock->addResponse(new Response(200, [], '<?xml version="1.0" encoding="UTF-8"?><response><result><res>OK</res></result></response>'));
$gateway = new Gateway(new Client($mock), new Request());
$gateway->setMid('9999');
$gateway->setSharedSecret($hmacKey);
$query = ['VS' => '123456', 'TXN' => 'PA', 'RES' => 'OK', 'AC' => '123', 'TRES' => 'OK', 'CID' => '', 'CC' => '4405****1111', 'RC' => '00', 'TID' => '987', 'TIMESTAMP' => '01022021214520'];
$query['HMAC'] = new HmacSign()->sign('10.00978123456PAOK123OK4405****111100987' . '01022021214520', $hmacKey);
$returnGateway = new Gateway(new Client($mock), new Request($query));
$returnGateway->setMid('9999');
$returnGateway->setSharedSecret($hmacKey);

// #region authorize
$response = $gateway->authorize([
    'amount' => '10.00',
    'currency' => 'EUR',
    'vs' => '123456',
    'rurl' => 'https://shop.example/payment/return',
    'name' => 'Jan Novak',
    'ipc' => '203.0.113.10',
    'tdsEmail' => 'jan@example.com',   // 3-D Secure data, all optional
    'tdsBillCity' => 'Bratislava',
    'tdsBillCountry' => 'SK',
])->send();
$url = $response->getRedirectUrl();
// #endregion authorize

// #region complete
// on the return URL
$authorization = $returnGateway->completeAuthorize(['vs' => '123456', 'amount' => '10.00', 'currency' => 'EUR'])->send();
if ($authorization->isSuccessful()) {
    $tid = $authorization->getTransactionReference(); // keep it to capture or reverse the pre-authorization
    $maskedCard = $authorization->getCc();           // 4405****1111
}
// #endregion complete

// #region capture
use Omnipay\CardPay\Message\CancelAuthorizeTxn;

$result = $gateway->cancelAuthorize([
    'amount' => '10.00',
    'vs' => '123456',
    'tid' => $tid,
    'txn' => CancelAuthorizeTxn::ClosePreauthorization, // capture; StornoPreauthorization reverses, Chargeback refunds
])->send();

if (!$result->isSuccessful()) {
    $error = $result->getErrorCode();
}
// #endregion capture

assert(str_starts_with($url, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay?PT=CardPay'));
assert($tid === '987' && $result->isSuccessful());
echo "captured {$tid}\n";
