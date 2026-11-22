<?php

declare(strict_types=1);

namespace Omnipay\Tests\Tatra\Support;

/**
 * A SoapClient that fails every call: tests that must not reach the web service (test mode, validation) use it so a
 * mistake never connects to Tatra banka.
 */
final class UnexpectedSoapClient extends \SoapClient
{
    public function __construct()
    {
        parent::__construct(null, ['location' => 'http://localhost', 'uri' => 'urn:test']);
    }

    /**
     * @param array<mixed> $args
     * @param array<mixed>|null $options
     * @param array<mixed> $outputHeaders
     */
    #[\Override]
    public function __soapCall(string $name, array $args, ?array $options = null, $inputHeaders = null, &$outputHeaders = null): never
    {
        throw new \LogicException("Unexpected SOAP call {$name}");
    }
}
