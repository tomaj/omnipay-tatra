<?php

namespace Omnipay\TatraPay;

use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    /**
     * @var Gateway
     */
    protected $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->setMid(1111);
    }

    public function testPurchaseSignWithDes()
    {
        if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
            $this->markTestSkipped('Skipped for php 7.4');
        }

        $this->gateway->setSharedSecret('11111111');

        $request = $this->gateway->purchase(array(
            'amount' => '10.00',
            'currency' => 'EUR',
            'vs' => 123456,
            'rurl' => 'http://return.sk',
        ));
        
        $this->assertInstanceOf('Omnipay\TatraPay\Message\PurchaseRequest', $request);
        $this->assertSame('10.00', $request->getAmount());

        $response = $request->send();

        $this->assertTrue($response->isRedirect());

        $this->assertEquals(
            'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp?PT=TatraPay&MID=1111&CURR=978&VS=123456&AMT=10.00&RURL=http%3A%2F%2Freturn.sk&SIGN=C758BF5F3A60FC3B',
            $response->getRedirectUrl()
        );
    }

    public function testPurchaseSignWithAes()
    {
        if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
            $this->markTestSkipped('Skipped for php 7.4');
        }

        $this->gateway->setSharedSecret('1111111111111111111111111111111111111111111111111111111111111111');

        $request = $this->gateway->purchase(array(
            'amount' => '10.00',
            'currency' => 'EUR',
            'vs' => 123456,
            'rurl' => 'http://return.sk',
        ));
        
        $this->assertInstanceOf('Omnipay\TatraPay\Message\PurchaseRequest', $request);
        $this->assertSame('10.00', $request->getAmount());

        $response = $request->send();

        $this->assertTrue($response->isRedirect());

        $this->assertEquals(
            'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp?PT=TatraPay&MID=1111&CURR=978&VS=123456&AMT=10.00&RURL=http%3A%2F%2Freturn.sk&SIGN=A50341DBBE65CB0A0E67F487C463DAF9',
            $response->getRedirectUrl()
        );
    }

    public function testPurchaseSignWithHmac()
    {
        $this->gateway->setSharedSecret('11111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111');

        $request = $this->gateway->purchase(array(
            'amount' => '10.00',
            'currency' => 'EUR',
            'vs' => 123456,
            'rurl' => 'http://return.sk',
        ));

        $request->setTimestamp('01022021214520');

        
        $this->assertInstanceOf('Omnipay\TatraPay\Message\PurchaseRequest', $request);
        $this->assertSame('10.00', $request->getAmount());

        $response = $request->send();

        $this->assertTrue($response->isRedirect());

        $this->assertEquals(
            'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/tatrapay?PT=TatraPay&MID=1111&CURR=978&VS=123456&AMT=10.00&RURL=http%3A%2F%2Freturn.sk&TIMESTAMP=01022021214520&HMAC=949f80a0b9a5626bc4a742de1835d7f307e8690a0eeb43473c5f79405023f43a',
            $response->getRedirectUrl()
        );
    }
}
