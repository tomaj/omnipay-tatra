<?php

declare(strict_types=1);

namespace Omnipay\Tests\Tatra\Support;

use Http\Mock\Client as MockClient;
use Omnipay\Common\Http\Client;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\Common\Http\PsrClient;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Symfony\Component\HttpFoundation\Request;

abstract class TestCase extends PHPUnitTestCase
{
    public const string DES_KEY = '11111111';
    public const string AES_KEY = '1111111111111111111111111111111111111111111111111111111111111111';
    public const string HMAC_KEY = '11111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111';

    private ?MockClient $mockClient = null;

    private ?ClientInterface $httpClient = null;

    private ?Request $httpRequest = null;

    protected function getMockClient(): MockClient
    {
        return $this->mockClient ??= new MockClient();
    }

    protected function getHttpClient(): ClientInterface
    {
        if (class_exists(PsrClient::class)) {
            return $this->httpClient ??= new PsrClient($this->getMockClient());
        }

        // omnipay/common < 3.5 (the --prefer-lowest build) has no PsrClient yet
        return $this->httpClient ??= new Client($this->getMockClient()); // @phpstan-ignore new.deprecatedClass, method.deprecatedClass
    }

    protected function getHttpRequest(): Request
    {
        return $this->httpRequest ??= new Request();
    }

    /**
     * The query string the bank sends back with the customer.
     *
     * @param array<string, mixed> $query
     */
    protected function setQuery(array $query): void
    {
        $this->getHttpRequest()->query->replace($query);
    }

    protected static function requireDes(): void
    {
        if (!in_array('des-ecb', openssl_get_cipher_methods(), true)) {
            self::markTestSkipped('DES is not available in this OpenSSL build');
        }
    }
}
