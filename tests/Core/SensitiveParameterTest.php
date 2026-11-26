<?php

declare(strict_types=1);

namespace Omnipay\Tests\Tatra\Core;

use Omnipay\CardPay\Gateway as CardPayGateway;
use Omnipay\ComfortPay\Gateway as ComfortPayGateway;
use Omnipay\ComfortPay\Message\ChargeRequest;
use Omnipay\Core\Exception\SignatureException;
use Omnipay\Core\Exception\UnsupportedKeyException;
use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Core\Sign\Aes256Sign;
use Omnipay\Core\Sign\DesSign;
use Omnipay\Core\Sign\HexKey;
use Omnipay\Core\Sign\HmacSign;
use Omnipay\Core\Sign\KeyType;
use Omnipay\Core\Sign\SignInterface;
use Omnipay\TatraPay\Gateway as TatraPayGateway;
use Omnipay\Tests\Tatra\Support\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Secrets are redacted from exception traces with #[\SensitiveParameter] (#49).
 */
final class SensitiveParameterTest extends TestCase
{
    /**
     * @return array<string, array{class-string, string, string}>
     */
    public static function sensitiveParameterProvider(): array
    {
        return [
            'SignInterface::sign' => [SignInterface::class, 'sign', 'secret'],
            'HmacSign::sign' => [HmacSign::class, 'sign', 'secret'],
            'Aes256Sign::sign' => [Aes256Sign::class, 'sign', 'secret'],
            'DesSign::sign' => [DesSign::class, 'sign', 'secret'],
            'KeyType::fromSecret' => [KeyType::class, 'fromSecret', 'secret'],
            'HexKey::decode' => [HexKey::class, 'decode', 'secret'],
            'AbstractRequest::setSharedSecret' => [AbstractRequest::class, 'setSharedSecret', 'value'],
            'TatraPay Gateway::setSharedSecret' => [TatraPayGateway::class, 'setSharedSecret', 'value'],
            'CardPay Gateway::setSharedSecret' => [CardPayGateway::class, 'setSharedSecret', 'value'],
            'ComfortPay Gateway::setSharedSecret' => [ComfortPayGateway::class, 'setSharedSecret', 'value'],
            'ComfortPay Gateway::setCertPass' => [ComfortPayGateway::class, 'setCertPass', 'value'],
            'ComfortPay Gateway::setProxyPassword' => [ComfortPayGateway::class, 'setProxyPassword', 'value'],
            'ComfortPay request::setCertPass' => [ChargeRequest::class, 'setCertPass', 'value'],
            'ComfortPay request::setProxyPassword' => [ChargeRequest::class, 'setProxyPassword', 'value'],
        ];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('sensitiveParameterProvider')]
    public function testParameterIsSensitive(string $class, string $method, string $parameter): void
    {
        $parameters = new \ReflectionMethod($class, $method)->getParameters();
        $found = array_values(array_filter($parameters, static fn(\ReflectionParameter $p): bool => $p->getName() === $parameter));

        self::assertCount(1, $found);
        self::assertCount(1, $found[0]->getAttributes(\SensitiveParameter::class));
    }

    public function testUnsupportedKeyTraceDoesNotContainTheSecret(): void
    {
        $secret = 'Wr0ngLengthSecret';
        $gateway = new TatraPayGateway($this->getHttpClient(), $this->getHttpRequest());
        $gateway->initialize(['mid' => '1111', 'sharedSecret' => $secret]);

        // php.ini-production (and setup-php in CI) drops all arguments from traces; record them like a development setup
        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');
        try {
            $gateway->purchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '123456', 'rurl' => 'https://shop.example/return'])->getData();
            self::fail('UnsupportedKeyException expected');
        } catch (UnsupportedKeyException $exception) {
            $trace = $exception->getTraceAsString();
            self::assertStringContainsString('KeyType::fromSecret(Object(SensitiveParameterValue))', $trace);
            self::assertStringNotContainsString($secret, $trace);
            self::assertStringNotContainsString($secret, print_r($exception->getTrace(), true));
        } finally {
            ini_set('zend.exception_ignore_args', (string) $ignoreArgs);
        }
    }

    /**
     * @return array<string, array{class-string<SignInterface>, string}>
     */
    public static function nonHexadecimalKeyProvider(): array
    {
        return [
            'HMAC' => [HmacSign::class, 'Z' . str_repeat('a', 127)],
            'AES' => [Aes256Sign::class, 'Z' . str_repeat('a', 63)],
        ];
    }

    /**
     * pack() warns about a non-hexadecimal key; an error handler that turns the warning into an exception would show the
     * key as pack()'s argument in the trace, so the key is rejected before pack() is called.
     *
     * @param class-string<SignInterface> $signer
     */
    #[DataProvider('nonHexadecimalKeyProvider')]
    public function testNonHexadecimalKeyIsRejectedWithoutAWarning(string $signer, string $secret): void
    {
        $ignoreArgs = ini_set('zend.exception_ignore_args', '0');
        set_error_handler(static fn(int $severity, string $message): never => throw new \ErrorException($message, 0, $severity));
        try {
            new $signer()->sign('abc', $secret);
            self::fail('SignatureException expected');
        } catch (SignatureException $exception) {
            self::assertSame('The shared secret must be hexadecimal', $exception->getMessage());
            self::assertStringContainsString('HexKey::decode(Object(SensitiveParameterValue))', $exception->getTraceAsString());
            // the frames of the library (this test's own frame has the secret as its argument)
            $frames = array_filter($exception->getTrace(), static fn(array $frame): bool => str_starts_with($frame['file'] ?? '', dirname(__DIR__, 2) . '/src/'));
            self::assertNotSame([], $frames);
            self::assertNotContains($secret, array_merge(...array_map(static fn(array $frame): array => $frame['args'] ?? [], array_values($frames))));
        } finally {
            restore_error_handler();
            ini_set('zend.exception_ignore_args', (string) $ignoreArgs);
        }
    }

    /**
     * DES fails with a SignatureException thrown inside DesSign::sign() when OpenSSL has no legacy provider. The child
     * process gets an OpenSSL configuration with only the default provider, so this runs the same everywhere.
     */
    public function testSignatureExceptionTraceDoesNotContainTheSecret(): void
    {
        $secret = 'S3cr3t!x';
        $config = tempnam(sys_get_temp_dir(), 'openssl');
        self::assertIsString($config);
        file_put_contents($config, "openssl_conf = openssl_init\n[openssl_init]\nproviders = provider_sect\n[provider_sect]\ndefault = default_sect\n[default_sect]\nactivate = 1\n");

        $code = sprintf(
            <<<'PHP'
                require %s;
                $gateway = new Omnipay\TatraPay\Gateway();
                $gateway->initialize(['mid' => '1111', 'sharedSecret' => %s]);
                try {
                    $gateway->purchase(['amount' => '10.00', 'currency' => 'EUR', 'vs' => '123456', 'rurl' => 'https://shop.example/return'])->send();
                } catch (Omnipay\Core\Exception\SignatureException $e) {
                    echo $e->getTraceAsString(), "\n", print_r($e->getTrace(), true);
                }
                PHP,
            var_export(dirname(__DIR__, 2) . '/vendor/autoload.php', true),
            var_export($secret, true),
        );

        try {
            $process = proc_open(
                [PHP_BINARY, '-d', 'zend.exception_ignore_args=0', '-r', $code],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                null,
                ['OPENSSL_CONF' => $config] + getenv(),
            );
            self::assertIsResource($process);
            $stdout = (string) stream_get_contents($pipes[1]);
            $stderr = (string) stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        } finally {
            unlink($config);
        }

        self::assertStringContainsString('DesSign->sign(', $stdout, $stdout . $stderr);
        self::assertStringContainsString('Object(SensitiveParameterValue)', $stdout);
        self::assertStringNotContainsString($secret, $stdout);
    }
}
