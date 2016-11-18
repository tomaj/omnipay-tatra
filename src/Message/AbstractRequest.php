<?php

namespace Omnipay\Tatra\Message;

abstract class AbstractRequest extends \Omnipay\Common\Message\AbstractRequest
{
    public $testEndpoint = 'http://localhost:3333/payment/tatrapay-aes256';
    public $liveEndpoint = 'http://localhost:3333/payment/tatrapay-aes256';


    public function getMid()
    {
        return $this->getParameter('mid');
    }
    public function setMid($value)
    {
        return $this->setParameter('mid', $value);
    }

    public function getSharedSecret()
    {
        return $this->getParameter('sharedSecret');
    }
    public function setSharedSecret($value)
    {
        return $this->setParameter('sharedSecret', $value);
    }

    public function getAmt()
    {
        return $this->getParameter('amt');
    }
    public function setAmt($value)
    {
        return $this->setParameter('amt', $value);
    }

    public function getCurr()
    {
        return $this->getParameter('curr');
    }
    public function setCurr($value)
    {
        return $this->setParameter('curr', $value);
    }

    public function getVs()
    {
        return $this->getParameter('vs');
    }
    public function setVs($value)
    {
        return $this->setParameter('vs', $value);
    }

    public function getCs()
    {
        return $this->getParameter('cs');
    }
    public function setCs($value)
    {
        return $this->setParameter('cs', $value);
    }

    public function getDesc()
    {
        return $this->getParameter('desc');
    }
    public function setDesc($value)
    {
        return $this->setParameter('desc', $value);
    }

    public function getRsms()
    {
        return $this->getParameter('rsms');
    }
    public function setRsms($value)
    {
        return $this->setParameter('rsms', $value);
    }

    public function getRem()
    {
        return $this->getParameter('rem');
    }
    public function setRem($value)
    {
        return $this->setParameter('rem', $value);
    }

    public function getAredir()
    {
        return $this->getParameter('aredis');
    }
    public function setAredir($value)
    {
        return $this->setParameter('aredir', $value);
    }

    public function getLang()
    {
        return $this->getParameter('lang');
    }
    public function setLang($value)
    {
        return $this->setParameter('lang', $value);
    }

    public function getRurl()
    {
        return $this->getParameter('rurl');
    }
    public function setRurl($value)
    {
        return $this->setParameter('rurl', $value);
    }

    public function getData()
    {
        $this->validate('sharedSecret', 'mid', 'curr', 'vs', 'rurl');
        $data = [];
        $data['MID'] = $this->getMid();
        $data['CURR'] = $this->getCurr();
        $data['VS'] = $this->getVs();
        $data['CS'] = $this->getCs();
        $data['AMT'] = $this->getAmt();
        $data['PT'] = 'TatraPay';
        $data['DESC'] = $this->getDesc();
        $data['RSMS'] = $this->getRsms();
        $data['REM'] = $this->getRem();
        $data['AREDIR'] = $this->getAredir();
        $data['LANG'] = $this->getLang();
        $data['RURL'] = $this->getRurl();
        return $data;
    }

    public function generateSignature($data)
    {
        $sharedSecret = $this->getSharedSecret();

        if (strlen($sharedSecret) == 64) {
            $sharedSecret = pack('H*', $sharedSecret);
        }
        
        $base = "{$this->getMid()}{$this->getAmt()}{$this->getCurr()}{$this->getVs()}{$this->getCs()}{$this->getRurl()}";
        $bytesHash = sha1($base, TRUE);
        
        $bytesHash = substr($bytesHash, 0, 16);
        
        $aes = mcrypt_module_open (MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($aes), MCRYPT_RAND);
        mcrypt_generic_init($aes, $sharedSecret, $iv);
        $bytesSign = mcrypt_generic($aes, $bytesHash);
        mcrypt_generic_deinit($aes);
        mcrypt_module_close($aes);
        
        $sign = strtoupper(bin2hex($bytesSign));
        return $sign;
    }

    public function sendData($data)
    {
        $data['SIGN'] = $this->generateSignature($data);
        return $this->response = new PurchaseResponse($this, $data);
    }

    public function getEndpoint()
    {
        return $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;
    }
}