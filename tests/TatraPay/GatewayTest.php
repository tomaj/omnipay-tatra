<?php

declare(strict_types=1);

namespace Omnipay\Tests\Tatra\TatraPay;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Exception\UnsupportedKeyException;
use Omnipay\TatraPay\Gateway;
use Omnipay\TatraPay\Message\CompletePurchaseResponse;
use Omnipay\Tests\Tatra\Support\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Expected signatures are computed with the openssl CLI (see CONTRIBUTING.md), not with the library's signers.
 */
final class GatewayTest extends TestCase
{
    private const string PURCHASE_QUERY = 'PT=TatraPay&MID=1111&CURR=978&VS=123456&AMT=10.00&RURL=http%3A%2F%2Freturn.sk';

    private Gateway $gateway;

    #[\Override]
    protected function setUp(): void
    {
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->setMid('1111');
    }

    public function testGateway(): void
    {
        self::assertSame('TatraPay Gateway', $this->gateway->getName());
        self::assertSame(['mid' => '', 'sharedSecret' => '', 'testHost' => ''], $this->gateway->getDefaultParameters());
        self::assertTrue($this->gateway->supportsPurchase());
        self::assertTrue($this->gateway->supportsCompletePurchase());

        $this->gateway->setSharedSecret(self::AES_KEY)->setTestHost('http://localhost');
        self::assertSame('1111', $this->gateway->getMid());
        self::assertSame(self::AES_KEY, $this->gateway->getSharedSecret());
        self::assertSame('http://localhost', $this->gateway->getTestHost());
        self::assertSame('http://localhost', $this->gateway->purchase()->getTestHost());
    }

    public function testIntegerParametersAreReadAsStrings(): void
    {
        $this->gateway->initialize(['mid' => 9999]);

        self::assertSame('9999', $this->gateway->getMid());
        self::assertSame('', $this->gateway->getSharedSecret());

        $this->gateway->setParameter('mid', ['9999']);
        self::assertNull($this->gateway->getMid());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function purchaseProvider(): array
    {
        return [
            'DES' => [self::DES_KEY, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp?' . self::PURCHASE_QUERY . '&SIGN=C758BF5F3A60FC3B'],
            'AES' => [self::AES_KEY, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp?' . self::PURCHASE_QUERY . '&SIGN=A50341DBBE65CB0A0E67F487C463DAF9'],
            'HMAC' => [self::HMAC_KEY, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/tatrapay?' . self::PURCHASE_QUERY . '&TIMESTAMP=01022021214520&HMAC=949f80a0b9a5626bc4a742de1835d7f307e8690a0eeb43473c5f79405023f43a'],
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
        ]);
        $request->setTimestamp('01022021214520');

        $response = $request->send();

        self::assertFalse($response->isSuccessful());
        self::assertTrue($response->isRedirect());
        self::assertSame('GET', $response->getRedirectMethod());
        self::assertSame($request, $response->getRequest());
        self::assertSame($expectedUrl, $response->getRedirectUrl());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function purchaseWithSymbolsProvider(): array
    {
        return [
            'DES' => [self::DES_KEY, 'SIGN=527DCF093C33F666'],
            'AES' => [self::AES_KEY, 'SIGN=811A01012EFC7948C79D6061713734F3'],
            'HMAC' => [self::HMAC_KEY, 'TIMESTAMP=01022021214520&HMAC=afa853ec0df36106d794eb3791adab3f9db7f8b9d3e5056219b4cd2383f4f8ce'],
        ];
    }

    /**
     * The specific symbol is signed and must also be sent, otherwise the bank cannot verify the signature.
     */
    #[DataProvider('purchaseWithSymbolsProvider')]
    public function testPurchaseSendsTheSignedSpecificSymbol(string $secret, string $expectedSignature): void
    {
        if ($secret === self::DES_KEY) {
            self::requireDes();
        }
        $this->gateway->setSharedSecret($secret);

        $request = $this->gateway->purchase([
            'amount' => '10.00',
            'currency' => 'EUR',
            'vs' => '123456',
            'ss' => '789',
            'cs' => '0308',
            'rurl' => 'http://return.sk',
            'rem' => 'rem@example.com',
            'desc' => 'Order',
            'rsms' => '0900123456',
            'aredir' => true,
            'lang' => 'sk',
        ]);
        $request->setTimestamp('01022021214520');

        self::assertSame(
            'PT=TatraPay&MID=1111&CURR=978&VS=123456&SS=789&CS=0308&AMT=10.00&DESC=Order&RSMS=0900123456&REM=rem%40example.com&AREDIR=1&LANG=sk&RURL=http%3A%2F%2Freturn.sk&' . $expectedSignature,
            parse_url($request->send()->getRedirectUrl(), PHP_URL_QUERY),
        );
    }

    /**
     * @return array<string, array{string, bool, ?string, string}>
     */
    public static function endpointProvider(): array
    {
        return [
            'production HMAC' => [self::HMAC_KEY, false, null, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/tatrapay'],
            'production AES' => [self::AES_KEY, false, null, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp'],
            'production DES' => [self::DES_KEY, false, null, 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp'],
            'test HMAC' => [self::HMAC_KEY, true, null, 'https://platby.tomaj.sk/payment/tatrapay-hmac'],
            'test AES' => [self::AES_KEY, true, null, 'https://platby.tomaj.sk/payment/tatrapay-aes256'],
            'test DES' => [self::DES_KEY, true, null, 'https://platby.tomaj.sk/payment/tatrapay-des'],
            'test host' => [self::HMAC_KEY, true, 'http://localhost:4444', 'http://localhost:4444/payment/tatrapay-hmac'],
            'empty test host' => [self::AES_KEY, true, '', 'https://platby.tomaj.sk/payment/tatrapay-aes256'],
        ];
    }

    #[DataProvider('endpointProvider')]
    public function testEndpoint(string $secret, bool $testMode, ?string $testHost, string $expected): void
    {
        $this->gateway->setSharedSecret($secret);
        $this->gateway->setTestMode($testMode);
        $this->gateway->setTestHost($testHost);

        self::assertSame($expected, $this->gateway->purchase()->getEndpoint());
    }

    public function testPurchaseRequiresParameters(): void
    {
        $this->gateway->setSharedSecret(self::AES_KEY);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('The rurl parameter is required');
        $this->gateway->purchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '1'])->send();
    }

    public function testPurchaseRequiresCurrency(): void
    {
        $this->gateway->setSharedSecret(self::AES_KEY);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('The currency parameter is required');
        $this->gateway->purchase(['amount' => '10.00', 'vs' => '1', 'rurl' => 'http://return.sk'])->send();
    }

    public function testPurchaseRejectsUnknownKeyLength(): void
    {
        $this->gateway->setSharedSecret('123');

        $this->expectException(UnsupportedKeyException::class);
        $this->expectExceptionMessageIsOrContains('Unknown key length 3');
        $this->gateway->purchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '1', 'rurl' => 'http://return.sk'])->send();
    }

    public function testPurchaseSetsTheTimestampInUtc(): void
    {
        $timestamp = $this->gateway->purchase()->getTimestamp();

        self::assertNotNull($timestamp);
        $time = \DateTimeImmutable::createFromFormat('dmYHis', $timestamp, new \DateTimeZone('UTC'));
        self::assertNotFalse($time);
        self::assertLessThan(5, abs($time->getTimestamp() - time()));
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function callbackProvider(): array
    {
        return [
            'DES' => [self::DES_KEY, ['VS' => '123456', 'RES' => 'OK', 'SIGN' => 'BC80D478A4F7BEAD']],
            'AES' => [self::AES_KEY, ['VS' => '123456', 'RES' => 'OK', 'SIGN' => 'C0E5FDD5CCB9D8B680A32AD22E748878']],
            'HMAC' => [self::HMAC_KEY, ['VS' => '123456', 'RES' => 'OK', 'TID' => '1', 'TIMESTAMP' => '01022021214520', 'HMAC' => '5091e7df8458d90dfad390248373d2cb30289d9ebafdf3b6197bf01736e46bf9']],
        ];
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('callbackProvider')]
    public function testCompletePurchase(string $secret, array $query): void
    {
        if ($secret === self::DES_KEY) {
            self::requireDes();
        }
        $this->gateway->setSharedSecret($secret);
        $this->setQuery($query);

        $response = $this->gateway->completePurchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '123456'])->send();

        self::assertTrue($response->isSuccessful());
        self::assertSame('OK', $response->getRes());
        self::assertSame('123456', $response->getVs());
        self::assertSame(['RES' => 'OK', 'VS' => '123456'], $response->getData());
    }

    /**
     * A genuine callback for another order must not confirm this one (#33).
     *
     * @param array<string, string> $query
     */
    #[DataProvider('callbackProvider')]
    public function testCompletePurchaseRejectsAnotherVariableSymbol(string $secret, array $query): void
    {
        $this->gateway->setSharedSecret($secret);
        $this->setQuery($query);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('Variable symbol mismatch');
        $this->gateway->completePurchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '222'])->send();
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('callbackProvider')]
    public function testCompletePurchaseComparesTheVariableSymbolStrictly(string $secret, array $query): void
    {
        $this->gateway->setSharedSecret($secret);
        $this->setQuery($query);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('Variable symbol mismatch');
        $this->gateway->completePurchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '0123456'])->send();
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('callbackProvider')]
    public function testCompletePurchaseRequiresTheVariableSymbol(string $secret, array $query): void
    {
        $this->gateway->setSharedSecret($secret);
        $this->setQuery($query);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('The vs parameter is required');
        $this->gateway->completePurchase(['amount' => '10.00', 'currency' => 'EUR'])->send();
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('callbackProvider')]
    public function testCompletePurchaseRejectsATamperedResult(string $secret, array $query): void
    {
        if ($secret === self::DES_KEY) {
            self::requireDes();
        }
        $this->gateway->setSharedSecret($secret);
        $this->setQuery(['RES' => 'FAIL'] + $query);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('incorect signature');
        $this->gateway->completePurchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '123456'])->send();
    }

    public function testCompletePurchaseWithMissingParametersIsRejectedWithoutWarnings(): void
    {
        $this->gateway->setSharedSecret(self::HMAC_KEY);
        $this->setQuery(['VS' => '123456']);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('incorect signature');
        $this->gateway->completePurchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '123456'])->send();
    }

    public function testCompletePurchaseWithoutAnyParameterIsRejected(): void
    {
        $this->gateway->setSharedSecret(self::AES_KEY);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('Variable symbol mismatch');
        $this->gateway->completePurchase(['vs' => '123456'])->send();
    }

    public function testCompletePurchaseIgnoresNonStringParameters(): void
    {
        $this->gateway->setSharedSecret(self::AES_KEY);
        $this->setQuery(['VS' => ['123456'], 'RES' => 'OK', 'SIGN' => 'C0E5FDD5CCB9D8B680A32AD22E748878']);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIsOrContains('Variable symbol mismatch');
        $this->gateway->completePurchase(['vs' => '123456'])->send();
    }

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function callbackWithSymbolsProvider(): array
    {
        return [
            'DES' => [self::DES_KEY, ['VS' => '123456', 'RES' => 'OK', 'SIGN' => 'AB7787C5EE7372D6']],
            'AES' => [self::AES_KEY, ['VS' => '123456', 'RES' => 'OK', 'SIGN' => '86F71F8CEA166856F5C65841ECAAA140']],
            'HMAC' => [self::HMAC_KEY, ['VS' => '123456', 'RES' => 'OK', 'TID' => '1', 'TIMESTAMP' => '01022021214520', 'HMAC' => 'c00db7a1d7dba3e4c85361e61747b38b1ff207fcfd52db26f965cdfb704e58bc']],
        ];
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('callbackWithSymbolsProvider')]
    public function testCompletePurchaseSignsTheSpecificSymbol(string $secret, array $query): void
    {
        if ($secret === self::DES_KEY) {
            self::requireDes();
        }
        $this->gateway->setSharedSecret($secret);
        $this->setQuery($query);

        $response = $this->gateway->completePurchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '123456', 'ss' => '789', 'cs' => '0308'])->send();

        self::assertTrue($response->isSuccessful());
    }

    public function testFailedPayment(): void
    {
        $this->gateway->setSharedSecret(self::AES_KEY);
        $this->setQuery(['VS' => '123456', 'RES' => 'FAIL', 'SIGN' => 'C90D94E4E5C2455E0095EAA4DA820988']);

        $response = $this->gateway->completePurchase(['vs' => '123456'])->send();

        self::assertFalse($response->isSuccessful());
        self::assertFalse($response->isPending());
        self::assertSame('FAIL', $response->getRes());
    }

    public function testTimeoutIsPending(): void
    {
        $response = new CompletePurchaseResponse($this->gateway->completePurchase(), ['RES' => 'TOUT', 'VS' => '1']);

        self::assertFalse($response->isSuccessful());
        self::assertTrue($response->isPending());
    }

    public function testResponseGettersOnEmptyData(): void
    {
        $response = new CompletePurchaseResponse($this->gateway->completePurchase(), ['RES' => ['OK']]);

        self::assertNull($response->getRes());
        self::assertNull($response->getVs());
        self::assertFalse($response->isSuccessful());
    }


    public function testGenerateSignature(): void
    {
        $this->gateway->setSharedSecret(self::AES_KEY);

        self::assertSame('C0E5FDD5CCB9D8B680A32AD22E748878', $this->gateway->purchase()->generateSignature('123456OK'));
    }
}
