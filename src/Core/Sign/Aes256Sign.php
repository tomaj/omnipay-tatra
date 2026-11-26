<?php

declare(strict_types=1);

namespace Omnipay\Core\Sign;

use Omnipay\Core\Exception\SignatureException;

/**
 * The "AES256" signature for the 64-character hexadecimal key: AES-ECB(first 16 bytes of SHA-1(input)),
 * uppercase hexadecimal (32 characters).
 *
 * Despite the name, the block is encrypted with AES-128 and the first 16 bytes of the 32-byte key; the second half of the
 * key has no effect. This has been the behaviour since 0.2.1 (the switch from mcrypt to OpenSSL); before that, mcrypt
 * chose AES-256 from the 32-byte key. Tatra banka's specification of the AES mode is not public, so the output is kept
 * unchanged until it is confirmed against the bank. See https://github.com/tomaj/omnipay-tatra/issues/34.
 */
final class Aes256Sign implements SignInterface
{
    private const string CIPHER = 'aes-128-ecb';

    #[\Override]
    public function sign(string $input, #[\SensitiveParameter] string $secret): string
    {
        $hash = substr(sha1($input, true), 0, 16);

        // ECB mode uses no initialization vector; OpenSSL uses the first 16 bytes of the key for AES-128
        $encrypted = openssl_encrypt($hash, self::CIPHER, pack('H*', $secret), OPENSSL_RAW_DATA);
        if ($encrypted === false) {
            throw new SignatureException('Cannot sign with cipher ' . self::CIPHER . ', is it supported by the OpenSSL build?');
        }

        return strtoupper(substr(bin2hex($encrypted), 0, 32));
    }
}
