<?php

declare(strict_types=1);

namespace Omnipay\ComfortPay\Message;

use Omnipay\ComfortPay\ParametersTrait;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Core\Message\AbstractRequest;

/**
 * A request to the ComfortPay SOAP web service, authenticated with the merchant's client certificate.
 * Extended by the package's SOAP requests.
 */
abstract class AbstractSoapRequest extends AbstractRequest
{
    use ParametersTrait;

    public const string WSDL = __DIR__ . '/../Teleplatba_1_0.wsdl';

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getData(): array
    {
        $this->validate('certPath', 'certPass');

        return [
            'certPath' => $this->getCertPath(),
            'certPass' => $this->getCertPass(),
        ];
    }

    /**
     * The options the SoapClient is created with. The bank's TLS certificate is always verified: by the system CA
     * store, by the `caFile` CA bundle, or by your own `streamContext`.
     *
     * @return array<string, mixed>
     */
    public function getSoapClientOptions(): array
    {
        $options = [
            'trace' => true,
            'exceptions' => true,
            'local_cert' => $this->getCertPath(),
            'passphrase' => $this->getCertPass(),
            'connection_timeout' => 5,
            'keep_alive' => false,
            'soap_version' => SOAP_1_2,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'stream_context' => $this->getStreamContext() ?? $this->createStreamContext(),
        ];

        $proxyHost = $this->getProxyHost();
        if ($proxyHost !== null && $proxyHost !== '') {
            $options['proxy_host'] = $proxyHost;
            $options['proxy_port'] = $this->getProxyPort();
            $proxyLogin = $this->getProxyLogin();
            if ($proxyLogin !== null && $proxyLogin !== '') {
                $options['proxy_login'] = $proxyLogin;
                $options['proxy_password'] = $this->getProxyPassword();
            }
        }

        return $options;
    }

    /**
     * Calls a web service operation.
     *
     * @throws \SoapFault
     */
    protected function call(string $operation, object $request): mixed
    {
        return $this->callClient($this->createSoapClient(), $operation, $request);
    }

    /**
     * Calls a web service operation and returns the <list> elements of its raw XML response.
     *
     * @return array<\SimpleXMLElement>
     * @throws \SoapFault
     * @throws InvalidResponseException when the response is not valid XML
     */
    protected function callForList(string $operation, object $request): array
    {
        $client = $this->createSoapClient();
        $this->callClient($client, $operation, $request);

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string((string) $client->__getLastResponse());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($xml === false) {
            throw new InvalidResponseException('The ComfortPay response is not valid XML');
        }

        $list = $xml->xpath('//list');

        return is_array($list) ? $list : [];
    }

    /**
     * The transactionId, transactionStatus and transactionApproval of a CardTransactionResponse from the web service.
     *
     * @return array<string, mixed>
     */
    protected static function transactionResult(mixed $response): array
    {
        $res = is_object($response) && property_exists($response, 'res') && is_object($response->res) ? $response->res : new \stdClass();

        return [
            'transactionId' => self::stringProperty($res, 'transactionId'),
            'transactionStatus' => self::stringProperty($res, 'transactionStatus'),
            'transactionApproval' => self::stringProperty($res, 'transactionApproval'),
        ];
    }

    private static function stringProperty(object $object, string $name): ?string
    {
        $value = get_object_vars($object)[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    protected function getTransactionIdString(): ?string
    {
        $transactionId = $this->getParameter('transactionId');

        return is_scalar($transactionId) ? (string) $transactionId : null;
    }

    private function createSoapClient(): \SoapClient
    {
        return $this->getSoapClient() ?? new \SoapClient(self::WSDL, $this->getSoapClientOptions());
    }

    /**
     * @throws \SoapFault
     */
    private function callClient(\SoapClient $client, string $operation, object $request): mixed
    {
        return $client->__soapCall($operation, [$request]);
    }

    /**
     * @return resource
     */
    private function createStreamContext()
    {
        $ssl = [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ];

        $caFile = $this->getCaFile();
        if ($caFile !== null && $caFile !== '') {
            $ssl['cafile'] = $caFile;
        }

        return stream_context_create(['ssl' => $ssl]);
    }
}
