<?php

declare(strict_types=1);

namespace Omnipay\Tests\Tatra\ComfortPay;

use Omnipay\ComfortPay\CardStatus;
use Omnipay\ComfortPay\Exception\ComfortPaySoapFault;
use Omnipay\ComfortPay\Gateway;
use Omnipay\ComfortPay\Message\CheckCardResponse;
use Omnipay\ComfortPay\TransactionType;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Core\Exception\InvalidParameterException;
use Omnipay\Tests\Tatra\Support\BrokenXmlSoapClient;
use Omnipay\Tests\Tatra\Support\FakeSoapClient;
use Omnipay\Tests\Tatra\Support\TestCase;
use Omnipay\Tests\Tatra\Support\UnexpectedSoapClient;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The ComfortPay web service requests, run through a SoapClient on the bundled WSDL with a fake transport.
 */
final class SoapRequestTest extends TestCase
{
    private const array CHARGE = [
        'transactionId' => '555',
        'transactionType' => 'PURCHASE',
        'referedCardId' => '777',
        'amount' => '10.50',
        'currency' => 'EUR',
        'vs' => '123456',
        'ss' => '789',
    ];

    private const string CHARGE_RESPONSE = '<t:doCardTransactionResponse><res><transactionId>555</transactionId><transactionStatus>00</transactionStatus><transactionApproval>A1B2</transactionApproval></res></t:doCardTransactionResponse>';

    private Gateway $gateway;

    #[\Override]
    protected function setUp(): void
    {
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->initialize([
            'terminalId' => '1234',
            'ws' => '5678',
            'certPath' => '/path/to/cert.pem',
            'certPass' => 'passphrase',
            'soapClient' => new UnexpectedSoapClient(),
        ]);
    }

    public function testSoapClientParameter(): void
    {
        self::assertInstanceOf(UnexpectedSoapClient::class, $this->gateway->getSoapClient());

        $this->gateway->setSoapClient(null);
        self::assertNull($this->gateway->getSoapClient());

        $this->gateway->setParameter('soapClient', 'client');
        self::assertNull($this->gateway->getSoapClient());
    }

    public function testSoapClientVerifiesTlsByDefault(): void
    {
        $options = $this->gateway->charge()->getSoapClientOptions();

        self::assertTrue($options['trace']);
        self::assertTrue($options['exceptions']);
        self::assertArrayNotHasKey('exception', $options);
        self::assertSame('/path/to/cert.pem', $options['local_cert']);
        self::assertSame('passphrase', $options['passphrase']);
        self::assertSame(5, $options['connection_timeout']);
        self::assertFalse($options['keep_alive']);
        self::assertSame(SOAP_1_2, $options['soap_version']);
        self::assertSame(WSDL_CACHE_NONE, $options['cache_wsdl']);
        self::assertArrayNotHasKey('proxy_host', $options);

        self::assertSame(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]], $this->contextOptions($options['stream_context']));
    }

    public function testSoapClientWithCaFile(): void
    {
        $this->gateway->setCaFile('/etc/ssl/tatrabanka.pem');
        self::assertSame('/etc/ssl/tatrabanka.pem', $this->gateway->getCaFile());

        $options = $this->gateway->checkCard()->getSoapClientOptions();

        self::assertSame(
            ['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'cafile' => '/etc/ssl/tatrabanka.pem']],
            $this->contextOptions($options['stream_context']),
        );
    }

    public function testSoapClientWithOwnStreamContext(): void
    {
        $context = stream_context_create(['ssl' => ['capath' => '/etc/ssl/certs']]);
        $this->gateway->setStreamContext($context);
        self::assertSame($context, $this->gateway->getStreamContext());

        self::assertSame($context, $this->gateway->checkTransaction()->getSoapClientOptions()['stream_context']);

        $this->gateway->setStreamContext(null);
        self::assertNull($this->gateway->getStreamContext());
    }

    public function testStreamContextMustBeAStreamContext(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->gateway->setStreamContext('verify_peer=0'); // @phpstan-ignore argument.type (the invalid value is the point of the test)
    }

    public function testStreamContextMustNotBeAnotherResource(): void
    {
        $file = fopen('php://memory', 'r');
        self::assertIsResource($file);

        $this->expectException(\InvalidArgumentException::class);
        try {
            $this->gateway->setStreamContext($file);
        } finally {
            fclose($file);
        }
    }

    public function testSoapClientWithProxy(): void
    {
        $this->gateway->initialize(['proxyHost' => 'proxy.local', 'proxyPort' => '3128']);
        self::assertSame(3128, $this->gateway->getProxyPort());

        $options = $this->gateway->unRegisterCard()->getSoapClientOptions();
        self::assertSame('proxy.local', $options['proxy_host']);
        self::assertSame(3128, $options['proxy_port']);
        self::assertArrayNotHasKey('proxy_login', $options);
        self::assertArrayNotHasKey('proxy_password', $options);

        $this->gateway->setProxyLogin('user')->setProxyPassword('pass');
        self::assertSame('user', $this->gateway->getProxyLogin());
        self::assertSame('pass', $this->gateway->getProxyPassword());
        $options = $this->gateway->listOfExpired()->getSoapClientOptions();
        self::assertSame('user', $options['proxy_login']);
        self::assertSame('pass', $options['proxy_password']);

        $this->gateway->setProxyPort(null);
        self::assertNull($this->gateway->getProxyPort());
    }

    public function testCharge(): void
    {
        $client = $this->useClient(FakeSoapClient::envelope(self::CHARGE_RESPONSE));

        $response = $this->gateway->charge(self::CHARGE)->send();

        self::assertTrue($response->isSuccessful());
        self::assertFalse($response->isPending());
        self::assertSame('555', $response->getTransactionId());
        self::assertSame('00', $response->getTransactionStatus());
        self::assertSame('A1B2', $response->getTransactionApproval());
        self::assertNull($response->getErrorCode());
        self::assertSame('00', $response->getCode());

        self::assertSame('https://comfortpay.tatrabanka.sk:8011/services/Teleplatba_1_0', $client->lastLocation);
        self::assertSame(
            '<ns1:doCardTransaction><transactionType>PURCHASE</transactionType><req><transactionId>555</transactionId>'
            . '<parentTransactionId xsi:nil="true"/><referedCardId>777</referedCardId><merchantId>5678</merchantId>'
            . '<terminalId>1234</terminalId><amount>10.5</amount><transactionIdentificator><symbols>'
            . '<variableSymbol>123456</variableSymbol><specificSymbol>789</specificSymbol></symbols></transactionIdentificator>'
            . '<cc>EUR</cc></req></ns1:doCardTransaction>',
            $client->lastRequestBody(),
        );
    }

    public function testChargeWithE2eReferenceAndIpspData(): void
    {
        $client = $this->useClient(FakeSoapClient::envelope(self::CHARGE_RESPONSE));

        $this->gateway->charge([
            'transactionId' => '556',
            'parentTransactionId' => '555',
            'transactionType' => TransactionType::PreauthConfirm,
            'referedCardId' => '777',
            'amount' => '10.00',
            'currency' => 'EUR',
            'e2eReference' => 'E2E-REF',
            'submerchantId' => 'SUB1',
            'location' => 'Shop',
            'city' => 'Bratislava',
            'alpha2CountryCode' => 'SK',
        ])->send();

        self::assertSame(
            '<ns1:doCardTransaction><transactionType>PREAUTH-CONFIRM</transactionType><req><transactionId>556</transactionId>'
            . '<parentTransactionId>555</parentTransactionId><referedCardId>777</referedCardId><merchantId>5678</merchantId>'
            . '<terminalId>1234</terminalId><amount>10</amount><transactionIdentificator><e2eReference>E2E-REF</e2eReference>'
            . '</transactionIdentificator><cc>EUR</cc><ipspData><submerchantId>SUB1</submerchantId><location>Shop</location>'
            . '<city>Bratislava</city><alpha2CountryCode>SK</alpha2CountryCode></ipspData></req></ns1:doCardTransaction>',
            $client->lastRequestBody(),
        );
    }

    /**
     * Tatra banka declines a charge with a SOAP-ENV:Receiver fault; it is a regular unsuccessful response (#12).
     */
    public function testChargeDeclinedWithReceiverFault(): void
    {
        $this->useClient(FakeSoapClient::fault('SOAP-ENV:Receiver', ComfortPaySoapFault::UNKNOWN_CARD));

        $response = $this->gateway->charge(self::CHARGE)->send();

        self::assertFalse($response->isSuccessful());
        self::assertFalse($response->isPending());
        self::assertFalse($response->getTransactionId());
        self::assertFalse($response->getTransactionStatus());
        self::assertFalse($response->getTransactionApproval());
        self::assertSame(50051, $response->getErrorCode());
        self::assertSame('50051', $response->getCode());
    }

    public function testChargeTimeoutIsPending(): void
    {
        $this->useClient(FakeSoapClient::fault('SOAP-ENV:Receiver', ComfortPaySoapFault::TIMEOUT));

        $response = $this->gateway->charge(self::CHARGE)->send();

        self::assertFalse($response->isSuccessful());
        self::assertTrue($response->isPending());
    }

    public function testChargeReceiverFaultWithoutDetail(): void
    {
        $this->useClient(FakeSoapClient::fault('SOAP-ENV:Receiver', null));

        $response = $this->gateway->charge(self::CHARGE)->send();

        self::assertFalse($response->isSuccessful());
        self::assertNull($response->getErrorCode());
        self::assertNull($response->getCode());
    }

    public function testChargeRethrowsOtherFaults(): void
    {
        $this->useClient(FakeSoapClient::fault('SOAP-ENV:Sender', ComfortPaySoapFault::INVALID_MERCHANT));

        try {
            $this->gateway->charge(self::CHARGE)->send();
            self::fail('ComfortPaySoapFault expected');
        } catch (ComfortPaySoapFault $fault) {
            self::assertSame(50052, $fault->getErrorCode());
            self::assertSame('SOAP-ENV:Sender', $fault->faultcode);
            self::assertSame('fault', $fault->getMessage());
            self::assertFalse($fault->isReceiverFault());
        }
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidChargeProvider(): array
    {
        return [
            'parent for confirm' => [['transactionType' => 'PREAUTH-CONFIRM'], 'The parentTransactionId parameter is required'],
            'parent for cancel' => [['transactionType' => 'PREAUTH-CANCEL'], 'The parentTransactionId parameter is required'],
            'parent for chargeback' => [['transactionType' => TransactionType::Chargeback], 'The parentTransactionId parameter is required'],
            'ss without e2e' => [['ss' => null], 'The ss parameter is required'],
            'vs without e2e' => [['vs' => null], 'The vs parameter is required'],
            'e2e without symbols' => [['vs' => null, 'ss' => null, 'e2eReference' => null], 'The ss parameter is required'],
            'partial ipsp data' => [['city' => 'Bratislava'], 'The submerchantId parameter is required'],
            'missing card' => [['referedCardId' => null], 'The referedCardId parameter is required'],
            'missing terminal' => [['terminalId' => null], 'The terminalId parameter is required'],
        ];
    }

    /**
     * @param array<string, mixed> $override
     */
    #[DataProvider('invalidChargeProvider')]
    public function testChargeValidation(array $override, string $message): void
    {
        $this->useClient(FakeSoapClient::envelope(self::CHARGE_RESPONSE));

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains($message);
        $this->gateway->charge($override + self::CHARGE)->send();
    }

    public function testChargeWithE2eReferenceOnly(): void
    {
        $client = $this->useClient(FakeSoapClient::envelope(self::CHARGE_RESPONSE));

        $request = $this->gateway->charge(['vs' => '', 'ss' => '0', 'e2eReference' => 'E2E'] + self::CHARGE);
        self::assertSame('E2E', $request->getE2eReference());
        $request->send();

        self::assertStringContainsString('<transactionIdentificator><e2eReference>E2E</e2eReference></transactionIdentificator>', $client->lastRequestBody());
    }

    public function testChargeWithSymbolsAndE2eReferenceSendsTheSymbols(): void
    {
        $client = $this->useClient(FakeSoapClient::envelope(self::CHARGE_RESPONSE));

        $this->gateway->charge(['e2eReference' => 'E2E'] + self::CHARGE)->send();

        self::assertStringContainsString('<symbols><variableSymbol>123456</variableSymbol><specificSymbol>789</specificSymbol></symbols>', $client->lastRequestBody());
    }

    public function testChargeRejectsUnknownTransactionType(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessageIsOrContains('Unsupported transactionType: REFUND');
        $this->gateway->charge(['transactionType' => 'REFUND']);
    }

    public function testChargeGetters(): void
    {
        $request = $this->gateway->charge([
            'transactionType' => TransactionType::Preauthorization,
            'parentTransactionId' => '1',
            'submerchantId' => 'S',
            'location' => 'L',
            'city' => 'C',
            'alpha2CountryCode' => 'SK',
        ]);

        self::assertSame('PREAUTHORIZATION', $request->getTransactionType());
        self::assertSame('1', $request->getParentTransactionId());
        self::assertSame('S', $request->getSubmerchantId());
        self::assertSame('L', $request->getLocation());
        self::assertSame('C', $request->getCity());
        self::assertSame('SK', $request->getAlpha2CountryCode());

        $request->setTransactionType(null);
        self::assertNull($request->getTransactionType());
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function chargeInTestModeProvider(): array
    {
        return [
            'odd card approved' => ['777', '00', true],
            'even card declined' => ['778', '02', false],
        ];
    }

    #[DataProvider('chargeInTestModeProvider')]
    public function testChargeInTestMode(string $cardId, string $status, bool $successful): void
    {
        $this->gateway->setTestMode(true);

        $response = $this->gateway->charge(['referedCardId' => $cardId] + self::CHARGE)->send();

        self::assertSame($successful, $response->isSuccessful());
        self::assertSame($status, $response->getTransactionStatus());
        self::assertSame('555', $response->getTransactionId());
        self::assertSame('123', $response->getTransactionApproval());
    }

    /**
     * @return array<string, array{string, CardStatus, bool}>
     */
    public static function checkCardProvider(): array
    {
        return [
            'OK' => ['OK', CardStatus::Ok, true],
            'FAIL' => ['FAIL', CardStatus::Fail, false],
            'UNKNOWN' => ['UNKNOWN', CardStatus::Unknown, false],
        ];
    }

    #[DataProvider('checkCardProvider')]
    public function testCheckCard(string $status, CardStatus $cardStatus, bool $successful): void
    {
        $client = $this->useClient(FakeSoapClient::envelope("<t:checkCardResponse><status>{$status}</status></t:checkCardResponse>"));

        $response = $this->gateway->checkCard(['cardId' => '777'])->send();

        self::assertSame($successful, $response->isSuccessful());
        self::assertSame($status, $response->getStatus());
        self::assertSame($cardStatus, $response->getCardStatus());
        self::assertSame('<ns1:checkCard><idOfCard>777</idOfCard></ns1:checkCard>', $client->lastRequestBody());
    }

    public function testCheckCardFault(): void
    {
        $this->useClient(FakeSoapClient::fault('SOAP-ENV:Receiver', ComfortPaySoapFault::UNKNOWN_CARD, 'checkCard'));

        $this->expectException(ComfortPaySoapFault::class);
        $this->gateway->checkCard(['cardId' => '777'])->send();
    }

    public function testCheckCardRequiresCardId(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('The cardId parameter is required');
        $this->gateway->checkCard()->send();
    }

    public function testCheckCardRequiresTheCertificate(): void
    {
        $this->gateway->setCertPath(null);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('The certPath parameter is required');
        $this->gateway->checkCard(['cardId' => '1'])->send();
    }

    public function testCheckCardInTestMode(): void
    {
        $this->gateway->setTestMode(true);

        self::assertTrue($this->gateway->checkCard(['cardId' => '2'])->send()->isSuccessful());
        $response = $this->gateway->checkCard(['cardId' => '3'])->send();
        self::assertFalse($response->isSuccessful());
        self::assertSame('FAIL', $response->getStatus());
    }

    public function testCheckCardResponseWithoutStatus(): void
    {
        $response = new CheckCardResponse($this->gateway->checkCard(), new \stdClass());

        self::assertNull($response->getStatus());
        self::assertNull($response->getCardStatus());
        self::assertFalse($response->isSuccessful());
    }

    public function testUnRegisterCard(): void
    {
        $client = $this->useClient(FakeSoapClient::envelope('<t:unRegisterCardResponse/>'));

        $response = $this->gateway->unRegisterCard(['cardId' => '777'])->send();

        self::assertTrue($response->isSuccessful());
        self::assertSame('<ns1:unRegisterCard><idOfCard>777</idOfCard></ns1:unRegisterCard>', $client->lastRequestBody());
    }

    public function testUnRegisterCardFault(): void
    {
        $this->useClient(FakeSoapClient::fault('SOAP-ENV:Receiver', ComfortPaySoapFault::UNKNOWN_CARD, 'unRegisterCard'));

        try {
            $this->gateway->unRegisterCard(['cardId' => '777'])->send();
            self::fail('ComfortPaySoapFault expected');
        } catch (ComfortPaySoapFault $fault) {
            self::assertSame(50051, $fault->getErrorCode());
            self::assertTrue($fault->isReceiverFault());
        }
    }

    public function testUnRegisterCardInTestMode(): void
    {
        $this->gateway->setTestMode(true);

        self::assertTrue($this->gateway->unRegisterCard(['cardId' => '3'])->send()->isSuccessful());
        self::assertFalse($this->gateway->unRegisterCard(['cardId' => '2'])->send()->isSuccessful());
    }

    public function testUnRegisterCardRequiresCardId(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('The cardId parameter is required');
        $this->gateway->unRegisterCard()->send();
    }

    public function testCheckTransaction(): void
    {
        $client = $this->useClient(FakeSoapClient::envelope(
            '<t:getTransactionStatusResponse><res><transactionId>555</transactionId><transactionStatus>05</transactionStatus><transactionApproval></transactionApproval></res></t:getTransactionStatusResponse>',
        ));

        $response = $this->gateway->checkTransaction(['transactionId' => 555])->send();

        self::assertFalse($response->isSuccessful());
        self::assertSame('555', $response->getTransactionId());
        self::assertSame('05', $response->getTransactionStatus());
        self::assertSame('', $response->getTransactionApproval());
        self::assertSame('<ns1:getTransactionStatus><transactionId>555</transactionId></ns1:getTransactionStatus>', $client->lastRequestBody());
    }

    public function testCheckTransactionWithUnexpectedResponse(): void
    {
        $this->useClient(FakeSoapClient::envelope('<t:getTransactionStatusResponse/>'));

        $response = $this->gateway->checkTransaction(['transactionId' => '555'])->send();

        self::assertFalse($response->isSuccessful());
        self::assertFalse($response->getTransactionId());
        self::assertFalse($response->getTransactionStatus());
        self::assertNull($response->getCode());
    }

    public function testCheckTransactionInTestMode(): void
    {
        $this->gateway->setTestMode(true);

        $response = $this->gateway->checkTransaction(['transactionId' => '555'])->send();
        self::assertTrue($response->isSuccessful());
        self::assertSame('555', $response->getTransactionId());
        self::assertSame('02', $this->gateway->checkTransaction(['transactionId' => '556'])->send()->getTransactionStatus());
    }

    public function testCheckTransactionRequiresTransactionId(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('The transactionId parameter is required');
        $this->gateway->checkTransaction()->send();
    }

    public function testListOfExpired(): void
    {
        $client = $this->useClient(FakeSoapClient::envelope('<t:getListOfExpiredResponse><list>111</list><list>222</list></t:getListOfExpiredResponse>'));

        $response = $this->gateway->listOfExpired(['expDate' => '2026-12-31'])->send();

        self::assertTrue($response->isSuccessful());
        self::assertSame([['id' => '111'], ['id' => '222']], $response->getExpires());
        self::assertSame('<ns1:getListOfExpired><expDate>2026-12-31</expDate></ns1:getListOfExpired>', $client->lastRequestBody());
    }

    public function testListOfExpiredEmpty(): void
    {
        $this->useClient(FakeSoapClient::envelope('<t:getListOfExpiredResponse/>'));

        $response = $this->gateway->listOfExpired(['expDate' => '2026-12-31'])->send();

        self::assertFalse($response->isSuccessful());
        self::assertSame([], $response->getExpires());
    }

    public function testListOfExpiredInTestMode(): void
    {
        $this->gateway->setTestMode(true);

        self::assertSame([['id' => 12345], ['id' => 12346]], $this->gateway->listOfExpired(['expDate' => '2026-12-31'])->send()->getExpires());
    }

    public function testListOfExpiredRequiresDate(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('The expDate parameter is required');
        $this->gateway->listOfExpired()->send();
    }

    public function testListOfExpiredFault(): void
    {
        $this->useClient(FakeSoapClient::fault('SOAP-ENV:Receiver', ComfortPaySoapFault::GENERAL_ERROR, 'getListOfExpired'));

        $this->expectException(ComfortPaySoapFault::class);
        $this->gateway->listOfExpired(['expDate' => '2026-12-31'])->send();
    }

    public function testListOfExpirePerId(): void
    {
        $client = $this->useClient(FakeSoapClient::envelope(
            '<t:getListOfExpPerIdResponse><list><idOfCard>111</idOfCard><expiration>1226</expiration></list>'
            . '<list><idOfCard>222</idOfCard><expiration>0127</expiration></list></t:getListOfExpPerIdResponse>',
        ));

        $request = $this->gateway->listOfExpirePerId(['cardIds' => [111, '222']]);
        self::assertSame(['111', '222'], $request->getCardIds());
        $response = $request->send();

        self::assertTrue($response->isSuccessful());
        self::assertSame([['id' => '111', 'date' => '1226'], ['id' => '222', 'date' => '0127']], $response->getExpires());
        self::assertSame(
            '<ns1:getListOfExpPerId><listOfIdCards>111</listOfIdCards><listOfIdCards>222</listOfIdCards></ns1:getListOfExpPerId>',
            $client->lastRequestBody(),
        );
    }

    public function testListOfExpirePerIdInTestMode(): void
    {
        $this->gateway->setTestMode(true);

        self::assertSame(
            [['id' => '1', 'date' => '0199'], ['id' => '2', 'date' => '0199']],
            $this->gateway->listOfExpirePerId(['cardIds' => ['1', '2']])->send()->getExpires(),
        );
    }

    public function testListOfExpirePerIdRequiresIds(): void
    {
        self::assertNull($this->gateway->listOfExpirePerId()->getCardIds());

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('The cardIds parameter is required');
        $this->gateway->listOfExpirePerId()->send();
    }

    public function testListOfExpirePerIdWithInvalidXml(): void
    {
        $client = new BrokenXmlSoapClient();
        $this->gateway->setSoapClient($client);
        self::assertSame($client, $this->gateway->getSoapClient());

        $this->expectException(InvalidResponseException::class);
        $this->gateway->listOfExpirePerId(['cardIds' => ['1']])->send();
    }

    private function useClient(string $responseXml): FakeSoapClient
    {
        $client = new FakeSoapClient($responseXml);
        $this->gateway->setSoapClient($client);

        return $client;
    }

    /**
     * @return array<mixed>
     */
    private function contextOptions(mixed $context): array
    {
        self::assertIsResource($context);

        return stream_context_get_options($context);
    }


    public function testRequestData(): void
    {
        $charge = $this->gateway->charge(self::CHARGE);
        $data = $charge->getData();
        self::assertSame('/path/to/cert.pem', $data['certPath']);
        self::assertSame('passphrase', $data['certPass']);
        self::assertSame('777', $charge->getReferedCardId());

        self::assertSame(['certPath' => '/path/to/cert.pem', 'certPass' => 'passphrase', 'cardId' => '1'], $this->gateway->checkCard(['cardId' => 1])->getData());
        self::assertSame('1', $this->gateway->checkCard(['cardId' => 1])->getCardId());
        self::assertSame('2', $this->gateway->unRegisterCard(['cardId' => 2])->getCardId());
        self::assertSame('2026-12-31', $this->gateway->listOfExpired(['expDate' => '2026-12-31'])->getExpDate());
        self::assertSame(['1', '2'], $this->gateway->listOfExpirePerId(['cardIds' => ['a' => 1, 'b' => 2]])->getCardIds());

        $this->gateway->setProxyHost('proxy.local');
        self::assertSame('proxy.local', $this->gateway->getProxyHost());
        self::assertSame('proxy.local', $this->gateway->charge()->getProxyHost());
    }

    public function testChargeRequiresE2eReferenceWhenSymbolsAreEmpty(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('The e2eReference parameter is required');
        $this->gateway->charge(['vs' => '', 'ss' => '0'] + self::CHARGE)->getData();
    }

    public function testChargeWithOneEmptySymbolDoesNotRequireE2eReference(): void
    {
        self::assertSame('', $this->gateway->charge(['ss' => ''] + self::CHARGE)->getData()['ss']);
        self::assertSame('', $this->gateway->charge(['vs' => ''] + self::CHARGE)->getData()['vs']);
    }

    public function testChargeWithOneSymbolSendsTheE2eReference(): void
    {
        $client = $this->useClient(FakeSoapClient::envelope(self::CHARGE_RESPONSE));

        $this->gateway->charge(['ss' => '', 'e2eReference' => 'E2E'] + self::CHARGE)->send();

        self::assertStringContainsString('<transactionIdentificator><e2eReference>E2E</e2eReference></transactionIdentificator>', $client->lastRequestBody());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function ipspFieldProvider(): array
    {
        return [
            'submerchantId' => ['submerchantId'],
            'location' => ['location'],
            'city' => ['city'],
            'alpha2CountryCode' => ['alpha2CountryCode'],
        ];
    }

    #[DataProvider('ipspFieldProvider')]
    public function testChargeRequiresAllIpspFields(string $field): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->gateway->charge([$field => 'X', 'submerchantId' => $field === 'submerchantId' ? 'X' : null] + self::CHARGE)->getData();
    }

    #[DataProvider('ipspFieldProvider')]
    public function testChargeWithAnEmptyIpspFieldSendsNoIpspData(string $field): void
    {
        $client = $this->useClient(FakeSoapClient::envelope(self::CHARGE_RESPONSE));
        $ipsp = ['submerchantId' => 'S', 'location' => 'L', 'city' => 'C', 'alpha2CountryCode' => 'SK'];
        $ipsp[$field] = '';

        $this->gateway->charge($ipsp + self::CHARGE)->send();

        self::assertStringNotContainsString('ipspData', $client->lastRequestBody());
    }

    public function testDeclinedChargeData(): void
    {
        $this->useClient(FakeSoapClient::fault('SOAP-ENV:Receiver', ComfortPaySoapFault::UNKNOWN_CARD));

        self::assertSame(
            ['transactionId' => false, 'transactionStatus' => false, 'transactionApproval' => false, 'errorCode' => 50051],
            $this->gateway->charge(self::CHARGE)->send()->getData(),
        );
    }

    public function testTransactionTypes(): void
    {
        self::assertFalse(TransactionType::Purchase->requiresParentTransaction());
        self::assertFalse(TransactionType::Preauthorization->requiresParentTransaction());
        self::assertTrue(TransactionType::PreauthConfirm->requiresParentTransaction());
        self::assertTrue(TransactionType::PreauthCancel->requiresParentTransaction());
        self::assertTrue(TransactionType::Chargeback->requiresParentTransaction());
    }

    public function testTestModeWithANonScalarId(): void
    {
        $this->gateway->setTestMode(true);

        $response = $this->gateway->checkTransaction(['transactionId' => ['x']])->send();

        self::assertSame('02', $response->getTransactionStatus());
        self::assertFalse($response->getTransactionId());
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function libxmlStateProvider(): array
    {
        return ['internal errors off' => [false], 'internal errors on' => [true]];
    }

    #[DataProvider('libxmlStateProvider')]
    public function testListOfExpiredInvalidXmlLeavesNoLibxmlState(bool $internalErrors): void
    {
        $this->gateway->setSoapClient(new BrokenXmlSoapClient());
        $previous = libxml_use_internal_errors($internalErrors);

        try {
            $this->gateway->listOfExpired(['expDate' => '2026-12-31'])->send();
            self::fail('InvalidResponseException expected');
        } catch (InvalidResponseException) {
            self::assertSame($internalErrors, libxml_use_internal_errors($internalErrors));
            self::assertSame([], libxml_get_errors());
        } finally {
            libxml_use_internal_errors($previous);
        }
    }
}
