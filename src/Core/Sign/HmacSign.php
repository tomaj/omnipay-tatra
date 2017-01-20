<?php

namespace Omnipay\Core\Sign;

class HmacSign implements SignInterface
{
    public function sign($input, $secret)
    {
        $sharedSecret = pack('H*', $secret);
        return hash_hmac('sha256', $input, $sharedSecret);
    }
}
