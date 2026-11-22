<?php

declare(strict_types=1);

namespace Omnipay\Tests\Tatra\CardPay;

use GuzzleHttp\Psr7\Response;
use Omnipay\CardPay\Gateway;
use Omnipay\CardPay\Message\CancelAuthorizeTxn;
use Omnipay\CardPay\Message\CompleteAuthorizeResponse;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Core\Exception\GatewayException;
use Omnipay\Core\Exception\InvalidParameterException;
use Omnipay\Tests\Tatra\Support\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Expected signatures are computed with the openssl CLI (see CONTRIBUTING.md), not with the library's signers.
 */
final class GatewayTest extends TestCase
{
    private const string PURCHASE_QUERY = 'PT=CardPay&MID=1111&CURR=978&VS=123456&AMT=10.00&RURL=http%3A%2F%2Freturn.sk&IPC=a&NAME=test';

    private const string CANCEL_RESPONSE = '<?xml version="1.0" encoding="UTF-8"?><response><result><res>FAIL</res><errorCode>99</errorCode></result></response>';

    private Gateway $gateway;

    #[\Override]
    protected function setUp(): void
    {
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->setMid('1111');
    }

    public function testGateway(): void
    {
        self::assertSame('CardPay Gateway', $this->gateway->getName());
        self::assertSame(['mid' => '', 'sharedSecret' => '', 'testHost' => ''], $this->gateway->getDefaultParameters());
        self::assertTrue($this->gateway->supportsAuthorize());
        self::assertTrue($this->gateway->supportsCompleteAuthorize());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function purchaseProvider(): array
    {
        return [
            'DES' => [self::DES_KEY, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp?' . self::PURCHASE_QUERY . '&SIGN=A6E11F14B1782C67'],
            'AES' => [self::AES_KEY, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp?' . self::PURCHASE_QUERY . '&SIGN=53819B07462B0E9879342BE729EC5CC1'],
            'HMAC' => [self::HMAC_KEY, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay?' . self::PURCHASE_QUERY . '&TIMESTAMP=01022021214520&HMAC=cc562c09b3d75d8028c09ad541943d92246e5d78ef7ebc9de409229b6c133f8c'],
        ];
    }

    #[DataProvider('purchaseProvider')]
    public function testPurchase(string $secret, string $expectedUrl): void
    {
        if ($secret === self::DES_KEY) {
            self::requireDes();
        }
        $this->gateway->setSharedSecret($secret);

        $request = $this->gateway->purchase([
            'amount' => '10.00',
            'currency' => 'EUR',
            'vs' => 123456,
            'rurl' => 'http://return.sk',
            'ipc' => 'a',
            'name' => 'test',
        ]);
        $request->setTimestamp('01022021214520');

        $response = $request->send();

        self::assertTrue($response->isRedirect());
        self::assertFalse($response->isSuccessful());
        self::assertSame($expectedUrl, $response->getRedirectUrl());
    }

    public function testPurchaseSignsTheConstantSymbolWithAes(): void
    {
        $this->gateway->setSharedSecret(self::AES_KEY);

        $response = $this->gateway->purchase([
            'amount' => '10.00',
            'currency' => 'EUR',
            'vs' => '123456',
            'cs' => '0308',
            'rurl' => 'http://return.sk',
            'ipc' => 'a',
            'name' => 'test',
        ])->send();

        self::assertStringEndsWith('&SIGN=55905167A325029CCD5000F91BD777D5', $response->getRedirectUrl());
    }

    public function testPurchaseTakesTheCustomerIpFromTheRequest(): void
    {
        $gateway = new Gateway($this->getHttpClient(), new Request(server: ['REMOTE_ADDR' => '10.0.0.1']));

        self::assertSame('10.0.0.1', $gateway->purchase()->getIpc());
        self::assertSame('10.0.0.1', $gateway->authorize()->getIpc());
        self::assertSame('10.0.0.2', $gateway->purchase(['ipc' => '10.0.0.2'])->getIpc());
        self::assertSame('10.0.0.2', $gateway->authorize(['ipc' => '10.0.0.2'])->getIpc());
        self::assertNull($this->gateway->purchase()->getIpc());
    }

    public function testPurchaseRequiresTheCustomerName(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('The name parameter is required');
        $this->gateway->purchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '1', 'rurl' => 'http://return.sk', 'ipc' => 'a'])->send();
    }

    /**
     * @return array<string, array{string, bool, ?string, string}>
     */
    public static function endpointProvider(): array
    {
        return [
            'production HMAC' => [self::HMAC_KEY, false, null, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay'],
            'production AES' => [self::AES_KEY, false, null, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp'],
            'production DES' => [self::DES_KEY, false, null, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp'],
            'test HMAC' => [self::HMAC_KEY, true, null, 'https://platby.tomaj.sk/payment/cardpay-hmac'],
            'test AES' => [self::AES_KEY, true, null, 'https://platby.tomaj.sk/payment/cardpay-aes256'],
            'test DES' => [self::DES_KEY, true, null, 'https://platby.tomaj.sk/payment/cardpay-des'],
            'test host' => [self::DES_KEY, true, 'http://localhost', 'http://localhost/payment/cardpay-des'],
        ];
    }

    #[DataProvider('endpointProvider')]
    public function testEndpoint(string $secret, bool $testMode, ?string $testHost, string $expected): void
    {
        $this->gateway->setSharedSecret($secret)->setTestHost($testHost)->setTestMode($testMode);

        self::assertSame($expected, $this->gateway->purchase()->getEndpoint());
    }

    public function testAuthorize(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);

        $request = $this->gateway->authorize([
            'amount' => '1.01',
            'vs' => '0257430862',
            'currency' => 'EUR',
            'rurl' => 'http://return.sk',
            'name' => 'test',
            'tpay' => 'Y',
            'rem' => 'test@example.com',
            'ipc' => '127.0.0.1',
        ]);
        $request->setTimestamp('01022021214520');

        self::assertSame('PA', $request->getTxn());
        $response = $request->send();

        self::assertTrue($response->isRedirect());
        self::assertFalse($response->isSuccessful());
        self::assertSame('GET', $response->getRedirectMethod());
        self::assertSame(
            'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay?PT=CardPay&MID=1111&AMT=1.01&CURR=978&VS=0257430862&RURL=http%3A%2F%2Freturn.sk&IPC=127.0.0.1&NAME=test&TIMESTAMP=01022021214520&TXN=PA&REM=test%40example.com&TPAY=Y&HMAC=a6ae350560717be53682d381ae3abc714f5d7f65bf64a98a94e9110b8e25fb1a',
            $response->getRedirectUrl(),
        );
    }

    /**
     * Every signed 3-D Secure field is also sent, and the cardholder no longer overwrites E2E.
     */
    public function testAuthorizeWithThreeDSecureData(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);

        $request = $this->gateway->authorize([
            'amount' => '1.01',
            'vs' => '0257430862',
            'currency' => 'EUR',
            'rurl' => 'http://return.sk',
            'name' => 'test',
            'tpay' => 'Y',
            'rem' => 'test@example.com',
            'ipc' => '127.0.0.1',
            'e2E' => 'E2E1',
            'cid' => '55',
            'ecid' => 'E55',
            'tdsCardholder' => 'Jan Novak',
            'tdsEmail' => 'jan@example.com',
            'tdsMobilePhone' => '+421900123456',
            'tdsBillCity' => 'Bratislava',
            'tdsBillCountry' => 'SK',
            'tdsBillAddress1' => 'Hlavna 1',
            'tdsBillAddress2' => '',
            'tdsBillZip' => '81101',
            'tdsShipCity' => 'Kosice',
            'tdsShipCountry' => 'SK',
            'tdsShipAddress1' => 'Mlynska 2',
            'tdsShipAddress2' => '',
            'tdsShipZip' => '04001',
            'tdsAddrMatch' => 'N',
            'aredir' => 1,
            'lang' => 'sk',
        ]);
        $request->setTimestamp('01022021214520');

        self::assertSame('E2E1', $request->getE2E());
        self::assertSame('Jan Novak', $request->getTdsCardholder());
        self::assertSame('55', $request->getCid());
        self::assertSame('E55', $request->getEcid());

        parse_str((string) parse_url($request->send()->getRedirectUrl(), PHP_URL_QUERY), $query);

        self::assertSame([
            'PT' => 'CardPay',
            'MID' => '1111',
            'AMT' => '1.01',
            'CURR' => '978',
            'VS' => '0257430862',
            'RURL' => 'http://return.sk',
            'IPC' => '127.0.0.1',
            'NAME' => 'test',
            'TIMESTAMP' => '01022021214520',
            'E2E' => 'E2E1',
            'TXN' => 'PA',
            'REM' => 'test@example.com',
            'TPAY' => 'Y',
            'CID' => '55',
            'ECID' => 'E55',
            'TDS_CARDHOLDER' => 'Jan Novak',
            'TDS_EMAIL' => 'jan@example.com',
            'TDS_MOBILE_PHONE' => '+421900123456',
            'TDS_BILL_CITY' => 'Bratislava',
            'TDS_BILL_COUNTRY' => 'SK',
            'TDS_BILL_ADDRESS1' => 'Hlavna 1',
            'TDS_BILL_ADDRESS2' => '',
            'TDS_BILL_ZIP' => '81101',
            'TDS_SHIP_CITY' => 'Kosice',
            'TDS_SHIP_COUNTRY' => 'SK',
            'TDS_SHIP_ADDRESS1' => 'Mlynska 2',
            'TDS_SHIP_ADDRESS2' => '',
            'TDS_SHIP_ZIP' => '04001',
            'TDS_ADDR_MATCH' => 'N',
            'AREDIR' => '1',
            'LANG' => 'sk',
            'HMAC' => 'a21bdba00ae35f980e8566a1583ce26dc24514bb7521fa164a83d4c6b6b1769f',
        ], $query);
    }

    public function testAuthorizeEndpoint(): void
    {
        self::assertSame('https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay', $this->gateway->authorize()->getEndpoint());

        $this->gateway->setTestMode(true);
        self::assertSame('https://platby.tomaj.sk/payment/cardpay-authorize-hmac', $this->gateway->authorize()->getEndpoint());

        $this->gateway->setTestHost('http://localhost');
        self::assertSame('http://localhost/payment/cardpay-authorize-hmac', $this->gateway->authorize()->getEndpoint());
    }

    public function testAuthorizeRequiresTheTimestamp(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $request = $this->gateway->authorize(['amount' => '1.01', 'vs' => '1', 'currency' => 'EUR', 'rurl' => 'http://return.sk', 'name' => 'test', 'ipc' => '127.0.0.1']);
        $request->setTimestamp(null);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('The timestamp parameter is required');
        $request->send();
    }

    public function testCompleteAuthorize(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $this->setQuery([
            'VS' => '123456',
            'TXN' => 'PA',
            'RES' => 'OK',
            'AC' => '123',
            'TRES' => 'OK',
            'CID' => '55',
            'CC' => '4405****1111',
            'RC' => '00',
            'TID' => '1',
            'TIMESTAMP' => '01022021214520',
            'HMAC' => '0a9426d46f84fef6649c1a698ee57f35fe85013b973678be8fd019858f001fdd',
        ]);

        $response = $this->gateway->completeAuthorize(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '123456'])->send();

        self::assertTrue($response->isSuccessful());
        self::assertSame('OK', $response->getRes());
        self::assertSame('123456', $response->getVs());
        self::assertSame('00', $response->getRc());
        self::assertSame('OK', $response->getTres());
        self::assertSame('55', $response->getCid());
        self::assertSame('1', $response->getTransactionReference());
        self::assertSame('4405****1111', $response->getCc());
        self::assertSame('123', $response->getAc());
    }

    public function testCompleteAuthorizeRejectsAnotherVariableSymbol(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $this->setQuery(['VS' => '123456', 'RES' => 'OK']);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('Variable symbol mismatch');
        $this->gateway->completeAuthorize(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '123457'])->send();
    }

    public function testCompleteAuthorizeResponseWithoutData(): void
    {
        $response = new CompleteAuthorizeResponse($this->gateway->completeAuthorize(), []);

        self::assertFalse($response->isSuccessful());
        self::assertNull($response->getRc());
        self::assertNull($response->getTres());
        self::assertNull($response->getCid());
        self::assertNull($response->getTransactionReference());
        self::assertNull($response->getCc());
        self::assertNull($response->getAc());
    }

    public function testCompletePurchaseReturnsTheReasonCode(): void
    {
        $this->gateway->setSharedSecret(self::AES_KEY);
        $this->setQuery(['VS' => '123456', 'RES' => 'OK', 'AC' => '123', 'RC' => '00', 'SIGN' => 'DD1C9BA10232F3BBF350BEF5C03049DF']);

        $response = $this->gateway->completePurchase(['vs' => '123456'])->send();

        self::assertSame('00', $response->getRc());
        self::assertSame(['RES' => 'OK', 'VS' => '123456', 'RC' => '00'], $response->getData());
    }

    public function testCompletePurchaseHmacErrorContainsTheSignedData(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $this->setQuery(['VS' => '1', 'RES' => 'OK', 'HMAC' => 'abc']);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains("incorrect signature for data: '10.009781OK', sign: 'abc'");
        $this->gateway->completePurchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '1'])->send();
    }

    public function testCancelAuthorize(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $this->getMockClient()->addResponse(new Response(200, [], '<?xml version="1.0" encoding="UTF-8"?><response><result><res>OK</res></result></response>'));

        $request = $this->gateway->cancelAuthorize([
            'amount' => '10.00',
            'vs' => '123456',
            'tid' => '1',
            'txn' => CancelAuthorizeTxn::ClosePreauthorization,
            'rem' => 'rem',
        ]);
        $request->setTimestamp('01022021214520');

        $response = $request->send();

        self::assertTrue($response->isSuccessful());
        self::assertSame('OK', $response->getRes());
        self::assertNull($response->getErrorCode());
        self::assertNull($response->getCode());

        $sent = $this->getMockClient()->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $sent);
        self::assertSame('GET', $sent->getMethod());
        self::assertSame(
            'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/txn_process.jsp?MID=1111&AMT=10.00&TID=1&VS=123456&TXN=CPA&REM=rem&TIMESTAMP=01022021214520&HMAC=1f1da2dde36631976c65f2800d34cfb3cdbc5a73e360161c20d32bb5db5cc623',
            (string) $sent->getUri(),
        );
    }

    public function testCancelAuthorizeFailureWithSignedResponse(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $this->getMockClient()->addResponse(new Response(
            200,
            ['Authorization' => 'ALG=HMAC-SHA256, HMAC=e79365cc55f48cf8d0f2c2afb4e2161c50ac110a3821989ad8800b02635c2390, broken'],
            self::CANCEL_RESPONSE,
        ));

        $response = $this->gateway->cancelAuthorize(['amount' => '10.00', 'vs' => '123456', 'tid' => '1', 'txn' => 'SPA'])->send();

        self::assertFalse($response->isSuccessful());
        self::assertSame('FAIL', $response->getRes());
        self::assertSame('99', $response->getErrorCode());
        self::assertSame('99', $response->getCode());
    }

    public function testCancelAuthorizeRejectsATamperedResponse(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $this->getMockClient()->addResponse(new Response(
            200,
            ['Authorization' => 'HMAC=e79365cc55f48cf8d0f2c2afb4e2161c50ac110a3821989ad8800b02635c2390'],
            str_replace('FAIL', 'OK', self::CANCEL_RESPONSE),
        ));

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessageIsOrContains('Incorrect signature');
        $this->gateway->cancelAuthorize(['amount' => '10.00', 'vs' => '123456', 'tid' => '1', 'txn' => 'CB'])->send();
    }

    public function testCancelAuthorizeHttpError(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY)->setTestMode(true);
        $this->getMockClient()->addResponse(new Response(503, [], 'down'));

        try {
            $this->gateway->cancelAuthorize(['amount' => '10.00', 'vs' => '123456', 'tid' => '1', 'txn' => 'CB'])->send();
            self::fail('GatewayException expected');
        } catch (GatewayException $exception) {
            self::assertSame('Failed to request gateway: 503 Service Unavailable', $exception->getMessage());
        }

        $sent = $this->getMockClient()->getLastRequest();
        self::assertInstanceOf(RequestInterface::class, $sent);
        self::assertStringStartsWith('https://platby.tomaj.sk/payment/cardpay-cancel-purchase?', (string) $sent->getUri());
    }

    public function testCancelAuthorizeInvalidXml(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $this->getMockClient()->addResponse(new Response(200, [], '<html>'));

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessageIsOrContains('The response is not valid XML');
        $this->gateway->cancelAuthorize(['amount' => '10.00', 'vs' => '123456', 'tid' => '1', 'txn' => 'CB'])->send();
    }

    public function testCancelAuthorizeResponseWithoutResult(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $this->getMockClient()->addResponse(new Response(200, [], '<response/>'));

        $response = $this->gateway->cancelAuthorize(['amount' => '10.00', 'vs' => '123456', 'tid' => '1', 'txn' => 'CB'])->send();

        self::assertFalse($response->isSuccessful());
        self::assertSame([], $response->getData());
        self::assertNull($response->getRes());
    }

    public function testCancelAuthorizeTxn(): void
    {
        $request = $this->gateway->cancelAuthorize(['txn' => 'SPA']);
        self::assertSame('SPA', $request->getTxn());

        $request->setTxn(CancelAuthorizeTxn::Chargeback);
        self::assertSame('CB', $request->getTxn());

        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessageIsOrContains('Unsopported value for TXN parameter: XX');
        $request->setTxn('XX');
    }


    public function testRequestsSetTheTimestamp(): void
    {
        self::assertMatchesRegularExpression('/^\d{14}$/', (string) $this->gateway->purchase()->getTimestamp());
        self::assertMatchesRegularExpression('/^\d{14}$/', (string) $this->gateway->authorize()->getTimestamp());
        self::assertMatchesRegularExpression('/^\d{14}$/', (string) $this->gateway->cancelAuthorize()->getTimestamp());
    }

    public function testRequestGetters(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);

        $purchase = $this->gateway->purchase(['name' => 'Jan']);
        self::assertSame('Jan', $purchase->getName());
        self::assertSame('5091e7df8458d90dfad390248373d2cb30289d9ebafdf3b6197bf01736e46bf9', $purchase->generateSignature('10.00978123456OK101022021214520'));

        $authorize = $this->gateway->authorize([
            'name' => 'Jan',
            'tpay' => 'Y',
            'tdsEmail' => 'e',
            'tdsMobilePhone' => 'm',
            'tdsBillCity' => 'bc',
            'tdsBillCountry' => 'bk',
            'tdsBillAddress1' => 'b1',
            'tdsBillAddress2' => 'b2',
            'tdsBillZip' => 'bz',
            'tdsShipCity' => 'sc',
            'tdsShipCountry' => 'sk',
            'tdsShipAddress1' => 's1',
            'tdsShipAddress2' => 's2',
            'tdsShipZip' => 'sz',
            'tdsAddrMatch' => 'Y',
        ]);
        self::assertSame(
            ['Jan', 'Y', 'e', 'm', 'bc', 'bk', 'b1', 'b2', 'bz', 'sc', 'sk', 's1', 's2', 'sz', 'Y'],
            [
                $authorize->getName(),
                $authorize->getTpay(),
                $authorize->getTdsEmail(),
                $authorize->getTdsMobilePhone(),
                $authorize->getTdsBillCity(),
                $authorize->getTdsBillCountry(),
                $authorize->getTdsBillAddress1(),
                $authorize->getTdsBillAddress2(),
                $authorize->getTdsBillZip(),
                $authorize->getTdsShipCity(),
                $authorize->getTdsShipCountry(),
                $authorize->getTdsShipAddress1(),
                $authorize->getTdsShipAddress2(),
                $authorize->getTdsShipZip(),
                $authorize->getTdsAddrMatch(),
            ],
        );
        self::assertSame('5091e7df8458d90dfad390248373d2cb30289d9ebafdf3b6197bf01736e46bf9', $authorize->generateSignature('10.00978123456OK101022021214520'));

        $cancel = $this->gateway->cancelAuthorize(['tid' => 77]);
        self::assertSame('77', $cancel->getTid());
        self::assertSame('https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/txn_process.jsp', $cancel->getEndpoint());
    }

    public function testCancelAuthorizeVerifiesTheWholeHeaderValue(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $this->getMockClient()->addResponse(new Response(
            200,
            ['Authorization' => 'HMAC=e79365cc55f48cf8d0f2c2afb4e2161c50ac110a3821989ad8800b02635c2390=x'],
            self::CANCEL_RESPONSE,
        ));

        $this->expectException(InvalidResponseException::class);
        $this->gateway->cancelAuthorize(['amount' => '10.00', 'vs' => '123456', 'tid' => '1', 'txn' => 'CB'])->send();
    }

    public function testCancelAuthorizeVerifiesTheSignatureAfterOtherHeaderParts(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $this->getMockClient()->addResponse(new Response(
            200,
            ['Authorization' => 'ALG=HMAC-SHA256, HMAC=e79365cc55f48cf8d0f2c2afb4e2161c50ac110a3821989ad8800b02635c2390'],
            str_replace('FAIL', 'OK', self::CANCEL_RESPONSE),
        ));

        $this->expectException(InvalidResponseException::class);
        $this->gateway->cancelAuthorize(['amount' => '10.00', 'vs' => '123456', 'tid' => '1', 'txn' => 'CB'])->send();
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function libxmlStateProvider(): array
    {
        return ['internal errors off' => [false], 'internal errors on' => [true]];
    }

    #[DataProvider('libxmlStateProvider')]
    public function testCancelAuthorizeInvalidXmlLeavesNoLibxmlState(bool $internalErrors): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $this->getMockClient()->addResponse(new Response(200, [], '<html>'));
        $previous = libxml_use_internal_errors($internalErrors);

        try {
            $this->gateway->cancelAuthorize(['amount' => '10.00', 'vs' => '123456', 'tid' => '1', 'txn' => 'CB'])->send();
            self::fail('InvalidResponseException expected');
        } catch (InvalidResponseException) {
            self::assertSame($internalErrors, libxml_use_internal_errors($internalErrors));
            self::assertSame([], libxml_get_errors());
        } finally {
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * Without an expected VS, only a callback without VS passes the VS check (and still needs a valid signature).
     */
    public function testCallbackWithoutVariableSymbol(): void
    {
        $this->gateway->setSharedSecret(self::AES_KEY);
        $this->setQuery(['RES' => 'OK', 'SIGN' => 'X']);

        try {
            $this->gateway->completePurchase()->send();
            self::fail('InvalidRequestException expected');
        } catch (InvalidRequestException $exception) {
            self::assertSame('incorect signature', $exception->getMessage());
        }

        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('incorect signature');
        $this->gateway->completeAuthorize(['amount' => '10.00', 'currency' => 'EUR'])->send();
    }


    /**
     * @return array<string, array{string, bool}>
     */
    public static function customerNameProvider(): array
    {
        return [
            'letters, digits and allowed characters' => ['Jan Novak-2_x.y@z', true],
            'empty' => ['', true],
            'diacritics' => ['Ján Novák', false],
            'comma' => ['Novak, Jan', false],
            'new line' => ["Jan\n", false],
        ];
    }

    #[DataProvider('customerNameProvider')]
    public function testCustomerName(string $name, bool $valid): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $parameters = ['amount' => '1.00', 'currency' => 'EUR', 'vs' => '1', 'rurl' => 'http://return.sk', 'ipc' => '127.0.0.1', 'name' => $name];

        foreach ([$this->gateway->purchase($parameters), $this->gateway->authorize($parameters)] as $request) {
            try {
                $request->getData();
                self::assertTrue($valid, "'{$name}' must be rejected");
            } catch (InvalidRequestException $exception) {
                self::assertFalse($valid, "'{$name}' must be accepted");
                self::assertSame('The name parameter may contain only letters without diacritics, digits, space and the characters . - _ @', $exception->getMessage());
            }
        }
    }
}
