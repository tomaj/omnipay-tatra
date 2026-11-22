<?php

declare(strict_types=1);

namespace Omnipay\Tests\Tatra\ComfortPay;

use Omnipay\ComfortPay\Gateway;
use Omnipay\ComfortPay\Message\CompletePurchaseResponse;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Exception\UnsupportedKeyException;
use Omnipay\Tests\Tatra\Support\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * Expected signatures are computed with the openssl CLI (see CONTRIBUTING.md), not with the library's signers.
 */
final class GatewayTest extends TestCase
{
    private Gateway $gateway;

    #[\Override]
    protected function setUp(): void
    {
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->setMid('1111');
    }

    public function testGateway(): void
    {
        self::assertSame('ComfortPay Gateway', $this->gateway->getName());
        self::assertSame(
            ['mid' => '', 'sharedSecret' => '', 'testHost' => '', 'terminalId' => '', 'ws' => '', 'certPath' => '', 'certPass' => ''],
            $this->gateway->getDefaultParameters(),
        );

        $this->gateway->initialize(['terminalId' => 123, 'ws' => 456, 'certPath' => '/cert.pem', 'certPass' => 'secret']);
        self::assertSame('123', $this->gateway->getTerminalId());
        self::assertSame('456', $this->gateway->getWs());
        self::assertSame('/cert.pem', $this->gateway->getCertPath());
        self::assertSame('secret', $this->gateway->getCertPass());

        $charge = $this->gateway->charge();
        self::assertSame('123', $charge->getTerminalId());
        self::assertSame('456', $charge->getWs());
        self::assertSame('/cert.pem', $charge->getCertPath());
        self::assertSame('secret', $charge->getCertPass());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function purchaseProvider(): array
    {
        return [
            'AES' => [self::AES_KEY, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp?PT=CardPay&MID=1111&CURR=978&VS=123456&AMT=10.00&AREDIR=1&RURL=http%3A%2F%2Freturn.sk&IPC=a&NAME=test&TPAY=Y&TEM=1&SIGN=CA6D5581146815F7BCDD3072D39B481A'],
            'HMAC' => [self::HMAC_KEY, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay?PT=CardPay&MID=1111&CURR=978&VS=123456&AMT=10.00&AREDIR=1&RURL=http%3A%2F%2Freturn.sk&IPC=a&NAME=test&TPAY=Y&TEM=1&TIMESTAMP=01022021214520&HMAC=ffa9b92506ae87bff4bdbd7ef95d4877a37af79a1dc839585add56068083dc03'],
        ];
    }

    #[DataProvider('purchaseProvider')]
    public function testPurchase(string $secret, string $expectedUrl): void
    {
        $this->gateway->setSharedSecret($secret);

        $request = $this->gateway->purchase([
            'amount' => '10.00',
            'currency' => 'EUR',
            'vs' => 123456,
            'rurl' => 'http://return.sk',
            'ipc' => 'a',
            'name' => 'test',
            'terminalId' => 1,
        ]);
        $request->setTimestamp('01022021214520');

        self::assertSame('Y', $request->getTpay());
        $response = $request->send();

        self::assertTrue($response->isRedirect());
        self::assertSame($expectedUrl, $response->getRedirectUrl());
    }

    public function testPurchaseSendsTheRemark(): void
    {
        $this->gateway->setSharedSecret(self::AES_KEY);

        $data = $this->gateway->purchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '1', 'rurl' => 'http://return.sk', 'rem' => 'a@b.sk'])->getData();
        self::assertSame('a@b.sk', $data['REM']);

        $data = $this->gateway->purchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '1', 'rurl' => 'http://return.sk', 'rem' => '0'])->getData();
        self::assertArrayNotHasKey('REM', $data);
        self::assertArrayNotHasKey('TIMESTAMP', $data);
    }

    public function testPurchaseRejectsDesKeys(): void
    {
        $this->gateway->setSharedSecret(self::DES_KEY);

        $this->expectException(UnsupportedKeyException::class);
        $this->gateway->purchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '1', 'rurl' => 'http://return.sk'])->send();
    }

    public function testEndpointRejectsDesKeys(): void
    {
        $this->gateway->setSharedSecret(self::DES_KEY);

        $this->expectException(UnsupportedKeyException::class);
        $this->gateway->purchase()->getEndpoint();
    }

    /**
     * @return array<string, array{string, bool, ?string, string}>
     */
    public static function endpointProvider(): array
    {
        return [
            'production HMAC' => [self::HMAC_KEY, false, null, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay'],
            'production AES' => [self::AES_KEY, false, null, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp'],
            'test HMAC' => [self::HMAC_KEY, true, null, 'https://platby.tomaj.sk/payment/comfortpay-hmac'],
            'test AES' => [self::AES_KEY, true, null, 'https://platby.tomaj.sk/payment/comfortpay-aes256'],
            'test host' => [self::AES_KEY, true, 'http://localhost', 'http://localhost/payment/comfortpay-aes256'],
        ];
    }

    #[DataProvider('endpointProvider')]
    public function testEndpoint(string $secret, bool $testMode, ?string $testHost, string $expected): void
    {
        $this->gateway->setSharedSecret($secret)->setTestHost($testHost)->setTestMode($testMode);

        self::assertSame($expected, $this->gateway->purchase()->getEndpoint());
    }

    public function testCompletePurchase(): void
    {
        $this->gateway->setSharedSecret(self::AES_KEY);
        $this->setQuery(['VS' => '123456', 'RES' => 'OK', 'AC' => '123', 'TRES' => 'OK', 'CID' => '55', 'CC' => '4405****1111', 'RC' => '00', 'SIGN' => '8A3EF58174EA8F1CE2BE4F57F22B90A2']);

        $response = $this->gateway->completePurchase(['vs' => '123456'])->send();

        self::assertTrue($response->isSuccessful());
        self::assertSame('55', $response->getCid());
        self::assertSame('4405****1111', $response->getCc());
        self::assertSame('OK', $response->getTres());
        self::assertSame('00', $response->getRc());
        self::assertSame('123456', $response->getVs());
    }

    public function testCompletePurchaseRejectsDesKeys(): void
    {
        $this->gateway->setSharedSecret(self::DES_KEY);
        $this->setQuery(['VS' => '123456', 'RES' => 'OK']);

        $this->expectException(UnsupportedKeyException::class);
        $this->gateway->completePurchase(['vs' => '123456'])->send();
    }

    public function testCompletePurchaseResponseWithoutData(): void
    {
        $response = new CompletePurchaseResponse($this->gateway->completePurchase(), []);

        self::assertNull($response->getCid());
        self::assertNull($response->getCc());
        self::assertNull($response->getTres());
        self::assertNull($response->getRc());
    }


    public function testPurchaseRequestDefaults(): void
    {
        $gateway = new Gateway($this->getHttpClient(), new Request(server: ['REMOTE_ADDR' => '10.0.0.1']));

        $request = $gateway->purchase(['name' => 'Jan']);
        self::assertSame('10.0.0.1', $request->getIpc());
        self::assertSame('Jan', $request->getName());
        self::assertSame(1, $request->getAredir());
        self::assertMatchesRegularExpression('/^\d{14}$/', (string) $request->getTimestamp());
        self::assertSame('10.0.0.2', $gateway->purchase(['ipc' => '10.0.0.2'])->getIpc());
        self::assertNull($this->gateway->purchase()->getIpc());

        $this->gateway->setSharedSecret(self::AES_KEY);
        self::assertSame('C0E5FDD5CCB9D8B680A32AD22E748878', $this->gateway->purchase()->generateSignature('123456OK'));
    }

    public function testPurchaseRequiresParameters(): void
    {
        $this->gateway->setSharedSecret(self::AES_KEY);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('The rurl parameter is required');
        $this->gateway->purchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '1'])->send();
    }

    public function testCompletePurchaseWithoutVariableSymbol(): void
    {
        $this->gateway->setSharedSecret(self::AES_KEY);
        $this->setQuery(['RES' => 'OK', 'SIGN' => 'X']);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('incorect signature');
        $this->gateway->completePurchase()->send();
    }


    public function testPurchaseRejectsNamesWithDiacritics(): void
    {
        $this->gateway->setSharedSecret(self::AES_KEY);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('The name parameter may contain only');
        $this->gateway->purchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '1', 'rurl' => 'http://return.sk', 'name' => 'Ján'])->getData();
    }
}
