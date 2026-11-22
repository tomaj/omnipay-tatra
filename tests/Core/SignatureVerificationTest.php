<?php

namespace Omnipay\Core;

use GuzzleHttp\Psr7\Response;
use Omnipay\CardPay\Gateway as CardPayGateway;
use Omnipay\ComfortPay\Gateway as ComfortPayGateway;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Core\Sign\Signature;
use Omnipay\TatraPay\Gateway as TatraPayGateway;
use Omnipay\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verification of the signatures the bank sends back. The expected values were computed
 * independently with the openssl CLI, not with the library's own signers.
 */
class SignatureVerificationTest extends TestCase
{
    private const DES_KEY = '11111111';
    private const AES_KEY = '1111111111111111111111111111111111111111111111111111111111111111';
    private const HMAC_KEY = '11111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111';

    private const CALLBACK = [
        'VS' => '123456',
        'RES' => 'OK',
        'AC' => '123',
        'TRES' => 'OK',
        'CID' => '55',
        'CC' => '4405****1111',
        'RC' => '00',
        'TID' => '1',
        'TIMESTAMP' => '01022021214520',
        'TXN' => 'PA',
    ];

    protected function tearDown(): void
    {
        $_GET = [];
        parent::tearDown();
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function validSignatureProvider(): array
    {
        return [
            'TatraPay AES' => ['TatraPay', self::AES_KEY, 'SIGN', 'C0E5FDD5CCB9D8B680A32AD22E748878'],
            'TatraPay HMAC' => ['TatraPay', self::HMAC_KEY, 'HMAC', '5091e7df8458d90dfad390248373d2cb30289d9ebafdf3b6197bf01736e46bf9'],
            'CardPay AES' => ['CardPay', self::AES_KEY, 'SIGN', 'DD1C9BA10232F3BBF350BEF5C03049DF'],
            'CardPay DES' => ['CardPay', self::DES_KEY, 'SIGN', '6ACA8F9B4AFB9BE5'],
            'CardPay HMAC' => ['CardPay', self::HMAC_KEY, 'HMAC', 'f10bf5820583eaa6730d3fb8e1b680243e31d5e0cc252adeb6858bdd3fc74ebc'],
            'CardPay authorize HMAC' => ['CardPayAuthorize', self::HMAC_KEY, 'HMAC', '0a9426d46f84fef6649c1a698ee57f35fe85013b973678be8fd019858f001fdd'],
            'ComfortPay AES' => ['ComfortPay', self::AES_KEY, 'SIGN', '8A3EF58174EA8F1CE2BE4F57F22B90A2'],
            'ComfortPay HMAC' => ['ComfortPay', self::HMAC_KEY, 'HMAC', '2efb0d440557f8053496b9d26ccc7f6048cc988485b1e74c8253f73fd16acb26'],
        ];
    }

    #[DataProvider('validSignatureProvider')]
    public function testValidSignatureIsAccepted(string $gateway, string $secret, string $field, string $signature): void
    {
        $this->skipIfDesUnavailable($secret);
        $_GET = self::CALLBACK + [$field => $signature];

        $response = $this->completeRequest($gateway, $secret)->send();

        self::assertTrue($response->isSuccessful());
        self::assertSame('123456', $response->getData()['VS']);
    }

    /**
     * @return array<string, array{string, string, string, string, array<string, mixed>}>
     */
    public static function invalidSignatureProvider(): array
    {
        $cases = [];
        foreach (self::validSignatureProvider() as $name => [$gateway, $secret, $field, $signature]) {
            $cases["{$name}, tampered RES"] = [$gateway, $secret, $field, $signature, ['RES' => 'FAIL', 'TRES' => 'FAIL']];
            $cases["{$name}, wrong signature"] = [$gateway, $secret, $field, $signature, [$field => str_repeat('0', strlen($signature))]];
            $cases["{$name}, wrong case"] = [$gateway, $secret, $field, $signature, [$field => ctype_upper(preg_replace('/\d/', '', $signature)) ? strtolower($signature) : strtoupper($signature)]];
            $cases["{$name}, truncated"] = [$gateway, $secret, $field, $signature, [$field => substr($signature, 0, -1)]];
            $cases["{$name}, empty"] = [$gateway, $secret, $field, $signature, [$field => '']];
            $cases["{$name}, missing"] = [$gateway, $secret, $field, $signature, [$field => null]];
            $cases["{$name}, array"] = [$gateway, $secret, $field, $signature, [$field => [$signature]]];
        }

        return $cases;
    }

    /**
     * @param array<string, mixed> $override
     */
    #[DataProvider('invalidSignatureProvider')]
    public function testInvalidSignatureIsRejected(string $gateway, string $secret, string $field, string $signature, array $override): void
    {
        $this->skipIfDesUnavailable($secret);
        $_GET = array_filter($override + self::CALLBACK + [$field => $signature], fn($value) => $value !== null);

        $this->expectException(InvalidRequestException::class);
        $this->completeRequest($gateway, $secret)->send();
    }

    public function testNumericLookingSignatureIsNotComparedLoosely(): void
    {
        $this->skipIfDesUnavailable(self::DES_KEY);
        // DES signature of "343364OK" is 0E74203727752110, a "magic hash" equal to "0" under ==
        $_GET = ['VS' => '343364', 'RES' => 'OK', 'SIGN' => '0E74203727752110'];
        $response = $this->completeRequest('TatraPay', self::DES_KEY, '343364')->send();
        self::assertTrue($response->isSuccessful());

        $_GET['SIGN'] = '0';
        $this->expectException(InvalidRequestException::class);
        $this->completeRequest('TatraPay', self::DES_KEY, '343364')->send();
    }

    public function testCancelAuthorizeAcceptsValidResponseSignature(): void
    {
        $response = $this->cancelAuthorize('HMAC=d8a7a11ccd937986bfa71eefde00e36f01e811a533414fd34fe7133f1897d696');

        self::assertTrue($response->isSuccessful());
    }

    public function testCancelAuthorizeRejectsInvalidResponseSignature(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->cancelAuthorize('HMAC=d8a7a11ccd937986bfa71eefde00e36f01e811a533414fd34fe7133f1897d697');
    }

    public function testSignatureEquals(): void
    {
        self::assertTrue(Signature::equals('ABC123', 'ABC123'));
        self::assertFalse(Signature::equals('ABC123', 'abc123'));
        self::assertFalse(Signature::equals('0E74203727752110', '0'));
        self::assertFalse(Signature::equals('ABC123', ''));
        self::assertFalse(Signature::equals('ABC123', null));
        self::assertFalse(Signature::equals('ABC123', ['ABC123']));
        self::assertFalse(Signature::equals('123', 123));
    }

    private function completeRequest(string $gateway, string $secret, string $vs = '123456'): AbstractRequest
    {
        $class = match ($gateway) {
            'TatraPay' => TatraPayGateway::class,
            'ComfortPay' => ComfortPayGateway::class,
            default => CardPayGateway::class,
        };
        $instance = new $class($this->getHttpClient(), $this->getHttpRequest());
        $instance->setMid(1111);
        $instance->setSharedSecret($secret);
        $parameters = ['amount' => '10.00', 'currency' => 'EUR', 'vs' => $vs];

        return $gateway === 'CardPayAuthorize'
            ? $instance->completeAuthorize($parameters)
            : $instance->completePurchase($parameters);
    }

    private function cancelAuthorize(string $authorizationHeader): \Omnipay\Common\Message\ResponseInterface
    {
        $this->getMockClient()->addResponse(new Response(
            200,
            ['Authorization' => $authorizationHeader],
            '<?xml version="1.0" encoding="UTF-8"?><response><result><res>OK</res></result></response>',
        ));

        $gateway = new CardPayGateway($this->getHttpClient(), $this->getHttpRequest());
        $gateway->setMid(1111);
        $gateway->setSharedSecret(self::HMAC_KEY);

        return $gateway->cancelAuthorize([
            'amount' => '10.00',
            'vs' => '123456',
            'tid' => '1',
            'txn' => 'CPA',
        ])->send();
    }

    private function skipIfDesUnavailable(string $secret): void
    {
        if (strlen($secret) === 8 && !in_array('des-ecb', openssl_get_cipher_methods(), true)) {
            self::markTestSkipped('DES is not available in this OpenSSL build');
        }
    }
}
