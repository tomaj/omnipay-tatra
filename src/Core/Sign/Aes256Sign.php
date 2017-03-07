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
        $ivsize = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivsize);
        $encrypted = openssl_encrypt($bytesHash, $cipher, $sharedSecret, OPENSSL_RAW_DATA, $iv);

        $sign = strtoupper(substr(bin2hex($encrypted), 0, 32));

        return $sign;
    }
}
