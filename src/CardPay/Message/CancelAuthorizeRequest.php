<?php

namespace Omnipay\CardPay\Message;

use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Core\Sign\HmacSign;
use Tracy\Debugger;

class CancelAuthorizeRequest extends AbstractRequest
{
    const TXN_CLOSE_PREAUTHORIZATION = 'CPA';
    const TXN_STORNO_PREAUTHORIZATION = 'SPA';
    const TXN_CHARGEBACK = 'CB';

    public function initialize(array $parameters = [])
    {
        parent::initialize($parameters);
        $this->setTimestamp(gmdate('dmYHis'));

        return $this;
    }

    public function setTid($value)
    {
        return $this->setParameter('tid', $value);
    }

    public function getTid()
    {
        return $this->getParameter('tid');
    }

    public function setTxn($value)
    {
        if (!in_array($value, [static::TXN_STORNO_PREAUTHORIZATION, static::TXN_CLOSE_PREAUTHORIZATION, static::TXN_CHARGEBACK])) {
            throw new \UnexpectedValueException("Unsopported value for TXN parameter: {$value}");
        }

        return $this->setParameter('txn', $value);
    }

    public function getTxn()
    {
        return $this->getParameter('txn');
    }

    public function getData()
    {
        $data = [];

        $data['MID'] = $this->getMid();
        $data['AMT'] = $this->getAmount();
        $data['TID'] = $this->getTid();
        $data['VS'] = $this->getVs();
        $data['TXN'] = $this->getTxn();
        $data['REM'] = $this->getRem();
        $data['TIMESTAMP'] = $this->getTimestamp();

        return $data;
    }

    private function generateSignature($data)
    {
        $sharedSecret = $this->getParameter('sharedSecret');

        $sign = new HmacSign();
        return $sign->sign($data, $sharedSecret);
    }

    public function sendData($data)
    {
        $input = "{$this->getMid()}{$this->getAmount()}{$this->getTid()}{$this->getVs()}{$this->getTxn()}{$this->getRem()}{$this->getTimestamp()}";
        $data['HMAC'] = $this->generateSignature($input);

        $params = http_build_query($data);

        $client = $this->getHttpClient();
        $response = $client->request(
            'GET',
            "{$this->getEndpoint()}?{$params}"
        );

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Failed to request gateway: {$response->getStatusCode()} {$response->getReasonPhrase()}");
        }

        $xml = $response->getBody()->getContents();

        $authorizationHeaders = $response->getHeader('Authorization');
        if (is_array($authorizationHeaders) && !empty($authorizationHeaders)) {
            $parsedHeaders = [];
            foreach (explode(",", $authorizationHeaders[0]) as $part) {
                $item = explode("=", $part);
                $parsedHeaders[$item[0]] = $item[1];
            }

            if (isset($parsedHeaders['HMAC']) && $parsedHeaders['HMAC'] !== $this->generateSignature($xml)) {
                throw new InvalidResponseException("Incorrect signature");
            }
        }

        $simpleXmlElement = new \SimpleXMLElement($xml);

        return $this->response = new CancelAuthorizeResponse($this, (array)$simpleXmlElement->result);
    }

    public function getEndpoint()
    {
        if ($this->getTestMode()) {
            return 'https://platby.tomaj.sk/payment/cardpay-cancel-purchase';
        }

        return 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/txn_process.jsp';
    }
}
