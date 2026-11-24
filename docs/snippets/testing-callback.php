<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

// #region snippet
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Sign\HmacSign;
use Omnipay\TatraPay\Gateway;
use Symfony\Component\HttpFoundation\Request;

const TEST_KEY = '1111111111111111111111111111111111111111111111111111111111111111'
    . '1111111111111111111111111111111111111111111111111111111111111111';

/**
 * The query string TatraPay appends to the return URL, signed like the bank signs it (HMAC: AMT CURR VS SS CS RES TID TIMESTAMP).
 *
 * @return array<string, string>
 */
function tatraPayReturn(string $vs, string $amount, string $res): array
{
    $query = ['VS' => $vs, 'RES' => $res, 'AMT' => $amount, 'CURR' => '978', 'TID' => '1', 'TIMESTAMP' => '24112026101500'];
    $query['HMAC'] = new HmacSign()->sign("{$amount}978{$vs}{$res}1{$query['TIMESTAMP']}", TEST_KEY);

    return $query;
}

/**
 * Your return URL handler, reduced to the part under test.
 */
function handleReturn(Request $request, string $expectedVs, string $amount): string
{
    $gateway = new Gateway(null, $request);
    $gateway->initialize(['mid' => '9999', 'sharedSecret' => TEST_KEY]);
    try {
        $response = $gateway->completePurchase(['vs' => $expectedVs, 'amount' => $amount, 'currency' => 'EUR'])->send();
    } catch (InvalidRequestException) {
        return 'rejected';
    }

    return $response->isSuccessful() ? 'paid' : 'not paid';
}

// a test per branch, here with assert(); the same lines work in a PHPUnit test case
assert(handleReturn(Request::create('/return', 'GET', tatraPayReturn('2026001', '24.90', 'OK')), '2026001', '24.90') === 'paid');
assert(handleReturn(Request::create('/return', 'GET', tatraPayReturn('2026001', '24.90', 'FAIL')), '2026001', '24.90') === 'not paid');

$forged = tatraPayReturn('2026001', '24.90', 'FAIL');
$forged['RES'] = 'OK';
assert(handleReturn(Request::create('/return', 'GET', $forged), '2026001', '24.90') === 'rejected');
assert(handleReturn(Request::create('/return', 'GET', tatraPayReturn('2026001', '1.00', 'OK')), '2026001', '24.90') === 'rejected');
assert(handleReturn(Request::create('/return', 'GET', tatraPayReturn('2026002', '24.90', 'OK')), '2026001', '24.90') === 'rejected');
// #endregion snippet

echo "ok\n";
