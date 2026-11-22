<?php

declare(strict_types=1);

namespace Omnipay\Tests\Tatra\Core;

use Omnipay\Core\Exception\SignatureException;
use Omnipay\Core\Exception\UnsupportedKeyException;
use Omnipay\Core\Sign\Aes256Sign;
use Omnipay\Core\Sign\DesSign;
use Omnipay\Core\Sign\HmacSign;
use Omnipay\Core\Sign\KeyType;
use Omnipay\Tests\Tatra\Support\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Expected values computed with the openssl CLI:
 *   HMAC: printf '%s' DATA | openssl dgst -sha256 -mac HMAC -macopt hexkey:KEY
 *   AES:  printf '%s' DATA | openssl dgst -sha1 -binary | head -c 16 | openssl enc -aes-128-ecb -K <first 32 hex of KEY> | xxd -p (first 32 hex, uppercase)
 *   DES:  printf '%s' DATA | openssl dgst -sha1 -binary | openssl enc -des-ecb -provider legacy -provider default -K <hex of KEY> | xxd -p (first 16 hex, uppercase)
 */
final class SignTest extends TestCase
{
    private const string AES_KEY_HALVES_DIFFER = '00112233445566778899aabbccddeeffffffffffffffffffffffffffffffffff';

    public function testHmac(): void
    {
        self::assertSame('5091e7df8458d90dfad390248373d2cb30289d9ebafdf3b6197bf01736e46bf9', new HmacSign()->sign('10.00978123456OK101022021214520', self::HMAC_KEY));
        self::assertSame('20d8f26fafab8c2b7b1610bb070535e77ce9e3321cdbd688ad1ff8393d6beac0', new HmacSign()->sign('abc', '0123456789abcdef' . str_repeat('a', 112)));
    }

    public function testAes(): void
    {
        self::assertSame('C0E5FDD5CCB9D8B680A32AD22E748878', new Aes256Sign()->sign('123456OK', self::AES_KEY));
        self::assertSame('C4DDF13C2D9B47447D11541BAE0434D7', new Aes256Sign()->sign('123456OK', self::AES_KEY_HALVES_DIFFER));
    }

    /**
     * Documents #34: the AES signature uses AES-128 with the first half of the key, so the second half has no effect.
     * With AES-256 (openssl enc -aes-256-ecb) the two keys would give B63545908F3922358770F7BF5D5A4F2B and
     * 82D6666A4EEE491FB184390B75C047E9.
     */
    public function testAesUsesOnlyTheFirstHalfOfTheKey(): void
    {
        $otherSecondHalf = substr(self::AES_KEY_HALVES_DIFFER, 0, 32) . str_repeat('0', 32);

        self::assertSame(new Aes256Sign()->sign('123456OK', self::AES_KEY_HALVES_DIFFER), new Aes256Sign()->sign('123456OK', $otherSecondHalf));
    }

    public function testDes(): void
    {
        self::requireDes();

        self::assertSame('BC80D478A4F7BEAD', new DesSign()->sign('123456OK', self::DES_KEY));
        self::assertSame('0E74203727752110', new DesSign()->sign('343364OK', self::DES_KEY));
    }

    #[Group('without-legacy-provider')]
    public function testDesWithoutTheLegacyProvider(): void
    {
        if (in_array('des-ecb', openssl_get_cipher_methods(), true)) {
            self::markTestSkipped('DES is available in this OpenSSL build');
        }

        $this->expectException(SignatureException::class);
        $this->expectExceptionMessageIsOrContains('Cannot sign with cipher des-ecb');
        new DesSign()->sign('123456OK', self::DES_KEY);
    }

    /**
     * @return array<string, array{string, KeyType, class-string}>
     */
    public static function keyTypeProvider(): array
    {
        return [
            'DES' => [self::DES_KEY, KeyType::Des, DesSign::class],
            'AES' => [self::AES_KEY, KeyType::Aes, Aes256Sign::class],
            'HMAC' => [self::HMAC_KEY, KeyType::Hmac, HmacSign::class],
        ];
    }

    /**
     * @param class-string $signer
     */
    #[DataProvider('keyTypeProvider')]
    public function testKeyType(string $secret, KeyType $keyType, string $signer): void
    {
        self::assertSame($keyType, KeyType::fromSecret($secret));
        self::assertInstanceOf($signer, $keyType->signer());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsupportedKeyProvider(): array
    {
        return [
            'empty' => [''],
            '7 characters' => ['1234567'],
            '9 characters' => ['123456789'],
            '63 characters' => [str_repeat('1', 63)],
            '129 characters' => [str_repeat('1', 129)],
        ];
    }

    #[DataProvider('unsupportedKeyProvider')]
    public function testUnsupportedKey(string $secret): void
    {
        try {
            KeyType::fromSecret($secret);
            self::fail('UnsupportedKeyException expected');
        } catch (UnsupportedKeyException $exception) {
            self::assertSame(
                'Unknown key length ' . strlen($secret) . ': the shared secret must have 8 (DES), 64 (AES) or 128 (HMAC) characters',
                $exception->getMessage(),
            );
        }
    }
}
