<?php

namespace Omnipay\TatraPay\Sign;

class DesSign implements SignInterface
{
	public function __construct()
	{
		if (!extension_loaded('mcrypt')) {
			throw new \Exception('Yout have enable mcrypt extension for this sign');
		}
	}

	public function sign($input, $secret)
	{
		$sharedSecret = $secret;

		$bytesHash = sha1($input, true);

		$des = mcrypt_module_open(MCRYPT_DES, "", MCRYPT_MODE_ECB, "");
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($des), MCRYPT_RAND);
        mcrypt_generic_init($des, $sharedSecret, $iv);
        $bytesSign = mcrypt_generic($des, substr($bytesHash, 0, 8));
        mcrypt_generic_deinit($des);
        mcrypt_module_close($des);
        $sign = strtoupper(bin2hex($bytesSign));

        return $sign;
	}
}