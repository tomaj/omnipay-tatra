<?php

declare(strict_types=1);

namespace Omnipay\Tests\Tatra\Support;

use Omnipay\ComfortPay\Message\AbstractSoapRequest;

/**
 * A SoapClient on the bundled WSDL that answers with canned XML instead of calling Tatra banka, and records the
 * request XML it would have sent.
 */
final class FakeSoapClient extends \SoapClient
{
    public ?string $lastRequestXml = null;

    public ?string $lastLocation = null;

    public function __construct(private readonly string $responseXml)
    {
        parent::__construct(AbstractSoapRequest::WSDL, [
            'trace' => true,
            'exceptions' => true,
            'soap_version' => SOAP_1_2,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ]);
    }

    public static function envelope(string $body): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:t="urn:tatrabanka:ibanking:Teleplatba">'
            . "<env:Body>{$body}</env:Body></env:Envelope>";
    }

    public static function fault(string $code, ?int $errorCode, string $method = 'doCardTransaction'): string
    {
        $detail = $errorCode === null ? '' : '<SOAP-ENV:Detail><types:ExceptionType>'
            . "<method>{$method}</method><file>ImplFile</file><line>1359</line><errorCode>{$errorCode}</errorCode>"
            . '<subsystemId>19</subsystemId><subsystemErrorCode>0</subsystemErrorCode><message></message>'
            . '</types:ExceptionType></SOAP-ENV:Detail>';

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope" xmlns:types="urn:tatrabanka:ibanking:Types">'
            . '<SOAP-ENV:Body><SOAP-ENV:Fault><SOAP-ENV:Code><SOAP-ENV:Value>' . $code . '</SOAP-ENV:Value></SOAP-ENV:Code>'
            . '<SOAP-ENV:Reason><SOAP-ENV:Text xml:lang="en">fault</SOAP-ENV:Text></SOAP-ENV:Reason>'
            . $detail . '</SOAP-ENV:Fault></SOAP-ENV:Body></SOAP-ENV:Envelope>';
    }

    #[\Override]
    public function __doRequest(string $request, string $location, string $action, int $version, bool $oneWay = false, ?string $uriParserClass = null): string
    {
        $this->lastRequestXml = $request;
        $this->lastLocation = $location;

        return $this->responseXml;
    }

    /**
     * The request body (the operation element) without the envelope, for comparisons.
     */
    public function lastRequestBody(): string
    {
        $xml = (string) $this->lastRequestXml;
        $start = strpos($xml, '<env:Body>');
        $end = strpos($xml, '</env:Body>');
        if ($start === false || $end === false) {
            return $xml;
        }

        return substr($xml, $start + 10, $end - $start - 10);
    }
}
