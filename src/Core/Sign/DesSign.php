<?php

namespace Omnipay\Core\Sign;

class DesSign implements SignInterface
{
    public function sign($input, $secret)
    {
        $sharedSecret = $secret;
        $bytesHash = sha1($input, true);

        $cipher = 'des-ecb';
        $ivsize = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivsize);
        $encrypted = openssl_encrypt($bytesHash, $cipher, $sharedSecret, OPENSSL_RAW_DATA, $iv);
        return strtoupper(substr(bin2hex($encrypted), 0, 16));
    }
}
