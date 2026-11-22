<?php

namespace Omnipay\Core\Sign;

class DesSign implements SignInterface
{
    public function sign($input, $secret)
    {
        $sharedSecret = $secret;
        $bytesHash = sha1($input, true);

        $cipher = 'des-ecb';
        // ECB mode uses no initialization vector
        $encrypted = openssl_encrypt($bytesHash, $cipher, $sharedSecret, OPENSSL_RAW_DATA);
        if ($encrypted === false) {
            throw new \RuntimeException("Cannot sign with cipher {$cipher}, is it supported by the OpenSSL build?");
        }
        return strtoupper(substr(bin2hex($encrypted), 0, 16));
    }
}
