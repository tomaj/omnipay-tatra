<?php

declare(strict_types=1);

namespace Omnipay\Tests\Tatra\Support;

/**
 * A SoapClient whose last response is not valid XML.
 */
final class BrokenXmlSoapClient extends \SoapClient
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
    public function __soapCall(string $name, array $args, ?array $options = null, $inputHeaders = null, &$outputHeaders = null): mixed
    {
        return null;
    }

    #[\Override]
    public function __getLastResponse(): string
    {
        return '<broken';
    }
}
