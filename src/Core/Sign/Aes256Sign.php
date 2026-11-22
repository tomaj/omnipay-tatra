<?php

namespace Omnipay\Core\Sign;

class Aes256Sign implements SignInterface
{
    public function sign($input, $secret)
    {
        $sharedSecret = pack('H*', $secret);

        $bytesHash = sha1($input, true);
        $bytesHash = substr($bytesHash, 0, 16);

        $cipher = 'aes-128-ecb';
        // ECB mode uses no initialization vector
        $encrypted = openssl_encrypt($bytesHash, $cipher, $sharedSecret, OPENSSL_RAW_DATA);
        if ($encrypted === false) {
            throw new \RuntimeException("Cannot sign with cipher {$cipher}, is it supported by the OpenSSL build?");
        }

        $sign = strtoupper(substr(bin2hex($encrypted), 0, 32));

        return $sign;
    }
}
