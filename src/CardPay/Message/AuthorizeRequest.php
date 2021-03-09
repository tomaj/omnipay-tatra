<?php

namespace Omnipay\CardPay\Message;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Core\Sign\HmacSign;

class AuthorizeRequest extends AbstractRequest
{
    const TXN_VALUE = 'PA';

    public function initialize(array $parameters = array())
    {
        parent::initialize($parameters);

        $this->setTimestamp(gmdate('dmYHis'));
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $this->setIpc($_SERVER['REMOTE_ADDR']);
        }
        return $this;
    }

    public function getIpc()
    {
        return $this->getParameter('ipc');
    }

    public function setIpc($value)
    {
        return $this->setParameter('ipc', $value);
    }

    public function getName()
    {
        return $this->getParameter('name');
    }

    public function setName($value)
    {
        return $this->setParameter('name', $value);
    }

    public function setTpay($value)
    {
        return $this->setParameter('tpay', $value);
    }

    public function getTpay()
    {
        return $this->getParameter('tpay');
    }

    public function getTxn()
    {
        return static::TXN_VALUE;
    }

    public function getE2E()
    {
        return $this->getParameter('E2E');
    }

    public function setE2E($value)
    {
        return $this->setParameter('E2E', $value);
    }

    public function getCid()
    {
        return $this->getParameter('CID');
    }

    public function setCid($value)
    {
        return $this->setParameter('CID', $value);
    }

    public function getEcid()
    {
        return $this->getParameter('ECID');
    }

    public function setEcid($value)
    {
        return $this->setParameter('ECID', $value);
    }

    public function getTdsCardholder()
    {
        return $this->getParameter('E2E');
    }

    public function setTdsCardholder($value)
    {
        return $this->setParameter('E2E', $value);
    }

    public function getTdsEmail()
    {
        return $this->getParameter('TDS_EMAIL');
    }

    public function setTdsEmail($value)
    {
        return $this->setParameter('TDS_EMAIL', $value);
    }

    public function getTdsMobilePhone()
    {
        return $this->getParameter('TDS_MOBILE_PHONE');
    }

    public function setTdsMobilePhone($value)
    {
        return $this->setParameter('TDS_MOBILE_PHONE', $value);
    }

    public function getTdsBillCity()
    {
        return $this->getParameter('TDS_BILL_CITY');
    }

    public function setTdsBillCity($value)
    {
        return $this->setParameter('TDS_BILL_CITY', $value);
    }

    public function getTdsBillCountry()
    {
        return $this->getParameter('TDS_BILL_COUNTRY');
    }

    public function setTdsBillCountry($value)
    {
        return $this->setParameter('TDS_BILL_COUNTRY', $value);
    }

    public function getTdsBillAddress1()
    {
        return $this->getParameter('TDS_BILL_ADDRESS1');
    }

    public function setTdsBillAddress1($value)
    {
        return $this->setParameter('TDS_BILL_ADDRESS1', $value);
    }

    public function getTdsBillAddress2()
    {
        return $this->getParameter('TDS_BILL_ADDRESS2');
    }

    public function setTdsBillAddress2($value)
    {
        return $this->setParameter('TDS_BILL_ADDRESS2', $value);
    }

    public function getTdsBillZip()
    {
        return $this->getParameter('TDS_BILL_ZIP');
    }

    public function setTdsBillZip($value)
    {
        return $this->setParameter('TDS_BILL_ZIP', $value);
    }

    public function getTdsShipCity()
    {
        return $this->getParameter('TDS_SHIP_CITY');
    }

    public function setTdsShipCity($value)
    {
        return $this->setParameter('TDS_SHIP_CITY', $value);
    }

    public function getTdsShipCountry()
    {
        return $this->getParameter('TDS_SHIP_COUNTRY');
    }

    public function setTdsShipCountry($value)
    {
        return $this->setParameter('TDS_SHIP_COUNTRY', $value);
    }

    public function getTdsShipAddress1()
    {
        return $this->getParameter('TDS_SHIP_ADDRESS1');
    }

    public function setTdsShipAddress1($value)
    {
        return $this->setParameter('TDS_SHIP_ADDRESS1', $value);
    }

    public function getTdsShipAddress2()
    {
        return $this->getParameter('TDS_SHIP_ADDRESS2');
    }

    public function setTdsShipAddress2($value)
    {
        return $this->setParameter('TDS_SHIP_ADDRESS2', $value);
    }

    public function getTdsShipZip()
    {
        return $this->getParameter('TDS_SHIP_ZIP');
    }

    public function setTdsShipZip($value)
    {
        return $this->setParameter('TDS_SHIP_ZIP', $value);
    }

    public function getTdsAddrMatch()
    {
        return $this->getParameter('TDS_ADDR_MATCH');
    }

    public function setTdsAddrMatch($value)
    {
        return $this->setParameter('TDS_ADDR_MATCH', $value);
    }

    public function getData()
    {
        $this->validate('sharedSecret', 'mid', 'currency', 'vs', 'rurl', 'ipc', 'name', 'timestamp');

        $data = [];
        $data['PT'] = 'CardPay';

        $data['MID'] = $this->getMid();
        $data['AMT'] = $this->getAmount();
        $data['CURR'] = (new ISOCurrencies())->numericCodeFor(new Currency($this->getCurrency()));
        $data['VS'] = $this->getVs();
        $data['RURL'] = $this->getRurl();
        $data['IPC'] = $this->getIpc();
        $data['NAME'] = $this->getName();
        $data['TIMESTAMP'] = $this->getTimestamp();

        $data['E2E'] = $this->getE2E();
        $data['TXN'] = $this->getTxn();
        $data['REM'] = $this->getRem();
        $data['TPAY'] = $this->getTpay();
        $data['CID'] = $this->getCid();
        $data['ECID'] = $this->getEcid();

        $data['TDS_CARDHOLDER'] = $this->getTdsCardholder();
        $data['TDS_EMAIL'] = $this->getTdsEmail();
        $data['TDS_MOBILE_PHONE'] = $this->getTdsMobilePhone();
        $data['TDS_BILL_CITY'] = $this->getTdsBillCity();
        $data['TDS_BILL_COUNTRY'] = $this->getTdsBillCountry();
        $data['TDS_BILL_CITY'] = $this->getTdsBillCity();
        $data['TDS_BILL_ADDRESS1'] = $this->getTdsBillAddress1();
        $data['TDS_BILL_ADDRESS2'] = $this->getTdsBillAddress2();
        $data['TDS_BILL_ZIP'] = $this->getTdsBillZip();
        $data['TDS_BILL_CITY'] = $this->getTdsBillCity();
        $data['TDS_SHIP_COUNTRY'] = $this->getTdsShipCountry();
        $data['TDS_SHIP_ADDRESS1'] = $this->getTdsShipAddress1();
        $data['TDS_SHIP_ADDRESS2'] = $this->getTdsShipAddress2();
        $data['TDS_SHIP_ZIP'] = $this->getTdsShipZip();
        $data['TDS_ADDR_MATCH'] = $this->getTdsAddrMatch();

        $data['AREDIR'] = $this->getAredir();
        $data['LANG'] = $this->getLang();

        return $data;
    }

    public function generateSignature($data)
    {
        $sign = new HmacSign();
        return $sign->sign($data, $this->getParameter('sharedSecret'));
    }

    public function sendData($data)
    {
        $curr = (new ISOCurrencies())->numericCodeFor(new Currency($this->getCurrency()));

        $input = "{$this->getMid()}{$this->getAmount()}{$curr}{$this->getVs()}{$this->getE2E()}{$this->getTxn()}{$this->getRurl()}";
        $input .= "{$this->getIpc()}{$this->getName()}{$this->getRem()}{$this->getTpay()}{$this->getCid()}{$this->getEcid()}";
        $input .="{$this->getTdsCardholder()}{$this->getTdsEmail()}{$this->getTdsMobilePhone()}{$this->getTdsBillCity()}";
        $input .= "{$this->getTdsBillCountry()}{$this->getTdsBillAddress1()}{$this->getTdsBillAddress2()}{$this->getTdsBillZip()}";
        $input .= "{$this->getTdsShipCity()}{$this->getTdsShipCountry()}{$this->getTdsShipAddress1()}{$this->getTdsShipAddress2()}";
        $input .= "{$this->getTdsShipZip()}{$this->getTdsAddrMatch()}{$this->getTimestamp()}";

        $data['HMAC'] = $this->generateSignature($input);

        return $this->response = new AuthorizeResponse($this, $data);
    }

    public function getEndpoint()
    {
        if ($this->getTestMode()) {
            return 'https://platby.tomaj.sk/payment/cardpay-authorize-hmac';
        }

        return 'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay';
    }
}
