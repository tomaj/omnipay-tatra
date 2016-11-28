<?php

namespace Omnipay\Core\Sign;

class Aes256Sign implements SignInterface
{
	public function __construct()
	{
		if (!extension_loaded('mcrypt')) {
			throw new \Exception('Yout have enable mcrypt extension for this sign');
		}
	}

	public function sign($input, $secret)
	{
		$sharedSecret = pack('H*', $secret);

		// $base = $this->GetSignatureBase();
		$bytesHash = sha1($input, TRUE);
		
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
}