<?php

declare(strict_types=1);

namespace Omnipay\CardPay\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Core\Exception\GatewayException;
use Omnipay\Core\Exception\InvalidParameterException;
use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Core\Sign\HmacSign;
use Omnipay\Core\Sign\Signature;

/**
 * Server-to-server request that captures (CPA) or reverses (SPA) a pre-authorization, or refunds a payment (CB).
 * Signed with HMAC; the shared secret must be the 128-character key.
 */
final class CancelAuthorizeRequest extends AbstractRequest
{
    /** @deprecated use CancelAuthorizeTxn::ClosePreauthorization */
    public const string TXN_CLOSE_PREAUTHORIZATION = 'CPA';
    /** @deprecated use CancelAuthorizeTxn::StornoPreauthorization */
    public const string TXN_STORNO_PREAUTHORIZATION = 'SPA';
    /** @deprecated use CancelAuthorizeTxn::Chargeback */
    public const string TXN_CHARGEBACK = 'CB';

    /**
     * @param array<mixed> $parameters
     */
    #[\Override]
    public function initialize(array $parameters = []): static
    {
        parent::initialize($parameters);
        $this->setTimestamp(gmdate('dmYHis'));

        return $this;
    }

    public function setTid(?string $value): static
    {
        return $this->setParameter('tid', $value);
    }

    public function getTid(): ?string
    {
        return $this->getStringParameter('tid');
    }

    /**
     * @throws InvalidParameterException for a value other than CPA, SPA or CB
     */
    public function setTxn(CancelAuthorizeTxn|string $value): static
    {
        if (is_string($value)) {
            $value = CancelAuthorizeTxn::tryFrom($value) ?? throw new InvalidParameterException("Unsopported value for TXN parameter: {$value}");
        }

        return $this->setParameter('txn', $value->value);
    }

    public function getTxn(): ?string
    {
        return $this->getStringParameter('txn');
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function getData(): array
    {
        return ['MID' => $this->getMid(), 'AMT' => $this->getAmount(), 'TID' => $this->getTid(), 'VS' => $this->getVs(), 'TXN' => $this->getTxn(), 'REM' => $this->getRem(), 'TIMESTAMP' => $this->getTimestamp()];
    }

    #[\Override]
    public function send(): CancelAuthorizeResponse
    {
        return $this->sendData($this->getData());
    }

    #[\Override]
    public function sendData(mixed $data): CancelAuthorizeResponse
    {
        $data = self::arrayData($data);
        $input = "{$this->getMid()}{$this->getAmount()}{$this->getTid()}{$this->getVs()}{$this->getTxn()}{$this->getRem()}{$this->getTimestamp()}";
        $data['HMAC'] = $this->generateSignature($input);

        $response = $this->httpClient->request('GET', "{$this->getEndpoint()}?" . http_build_query($data));

        if ($response->getStatusCode() !== 200) {
            throw new GatewayException("Failed to request gateway: {$response->getStatusCode()} {$response->getReasonPhrase()}");
        }

        $xml = (string) $response->getBody();

        $authorizationHeaders = $response->getHeader('Authorization');
        if ($authorizationHeaders !== []) {
            $parsedHeaders = [];
            foreach (explode(',', $authorizationHeaders[0]) as $part) {
                $item = explode('=', $part, 2);
                $parsedHeaders[trim($item[0])] = $item[1] ?? '';
            }

            if (isset($parsedHeaders['HMAC']) && !Signature::equals($this->generateSignature($xml), $parsedHeaders['HMAC'])) {
                throw new InvalidResponseException('Incorrect signature');
            }
        }

        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($document === false) {
            throw new InvalidResponseException('The response is not valid XML');
        }

        $result = [];
        foreach ($document->result->children() ?? [] as $name => $value) {
            $result[$name] = (string) $value;
        }

        return $this->response = new CancelAuthorizeResponse($this, $result);
    }

    public function getEndpoint(): string
    {
        if ($this->getTestMode()) {
            return $this->getTestEndpointHost() . '/payment/cardpay-cancel-purchase';
        }

        return 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/txn_process.jsp';
    }

    private function generateSignature(string $data): string
    {
        return new HmacSign()->sign($data, $this->getSharedSecret() ?? '');
    }
}
