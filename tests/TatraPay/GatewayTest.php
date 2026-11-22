<?php

namespace Omnipay\TatraPay;

use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    private Gateway $testedGateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = $this->testedGateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->testedGateway->setMid(1111);
    }

    public function testPurchaseSignWithDes()
    {
        if (!in_array('des-ecb', openssl_get_cipher_methods(), true)) {
            self::markTestSkipped('DES is not available in this OpenSSL build');
        }

        $this->testedGateway->setSharedSecret('11111111');

        $request = $this->testedGateway->purchase([
            'amount' => '10.00',
            'currency' => 'EUR',
            'vs' => 123456,
            'rurl' => 'http://return.sk',
        ]);

        self::assertInstanceOf('Omnipay\TatraPay\Message\PurchaseRequest', $request);
        self::assertSame('10.00', $request->getAmount());

        $response = $request->send();
        self::assertInstanceOf(RedirectResponseInterface::class, $response);

        self::assertTrue($response->isRedirect());

        self::assertEquals(
            'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp?PT=TatraPay&MID=1111&CURR=978&VS=123456&AMT=10.00&RURL=http%3A%2F%2Freturn.sk&SIGN=C758BF5F3A60FC3B',
            $response->getRedirectUrl(),
        );
    }

    public function testPurchaseSignWithAes()
    {
        $this->testedGateway->setSharedSecret('1111111111111111111111111111111111111111111111111111111111111111');

        $request = $this->testedGateway->purchase([
            'amount' => '10.00',
            'currency' => 'EUR',
            'vs' => 123456,
            'rurl' => 'http://return.sk',
        ]);

        self::assertInstanceOf('Omnipay\TatraPay\Message\PurchaseRequest', $request);
        self::assertSame('10.00', $request->getAmount());

        $response = $request->send();
        self::assertInstanceOf(RedirectResponseInterface::class, $response);

        self::assertTrue($response->isRedirect());

        self::assertEquals(
            'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp?PT=TatraPay&MID=1111&CURR=978&VS=123456&AMT=10.00&RURL=http%3A%2F%2Freturn.sk&SIGN=A50341DBBE65CB0A0E67F487C463DAF9',
            $response->getRedirectUrl(),
        );
    }

    public function testPurchaseSignWithHmac()
    {
        $this->testedGateway->setSharedSecret('11111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111');

        $request = $this->testedGateway->purchase([
            'amount' => '10.00',
            'currency' => 'EUR',
            'vs' => 123456,
            'rurl' => 'http://return.sk',
        ]);

        $request->setTimestamp('01022021214520');


        self::assertInstanceOf('Omnipay\TatraPay\Message\PurchaseRequest', $request);
        self::assertSame('10.00', $request->getAmount());

        $response = $request->send();
        self::assertInstanceOf(RedirectResponseInterface::class, $response);

        self::assertTrue($response->isRedirect());

        self::assertEquals(
            'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/tatrapay?PT=TatraPay&MID=1111&CURR=978&VS=123456&AMT=10.00&RURL=http%3A%2F%2Freturn.sk&TIMESTAMP=01022021214520&HMAC=949f80a0b9a5626bc4a742de1835d7f307e8690a0eeb43473c5f79405023f43a',
            $response->getRedirectUrl(),
        );
    }
}
