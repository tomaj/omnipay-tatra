<?php

namespace Omnipay\ComfortPay;

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
            'ipc' => 'a',
            'name' => 'test',
        ));
        
        $this->assertInstanceOf('Omnipay\ComfortPay\Message\PurchaseRequest', $request);
        $this->assertSame('10.00', $request->getAmount());

        $response = $request->send();

        $this->assertTrue($response->isRedirect());

        $this->assertEquals(
            'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp?PT=CardPay&MID=1111&CURR=978&VS=123456&AMT=10.00&AREDIR=1&RURL=http%3A%2F%2Freturn.sk&IPC=a&NAME=test&TPAY=Y&TEM=1&SIGN=CA6D5581146815F7BCDD3072D39B481A',
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
            'ipc' => 'a',
            'name' => 'test',
            'terminalId' => 1,
        ));

        $request->setTimestamp('01022021214520');

        
        $this->assertInstanceOf('Omnipay\ComfortPay\Message\PurchaseRequest', $request);
        $this->assertSame('10.00', $request->getAmount());

        $response = $request->send();

        $this->assertTrue($response->isRedirect());

        $this->assertEquals(
            'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay?PT=CardPay&MID=1111&CURR=978&VS=123456&AMT=10.00&AREDIR=1&RURL=http%3A%2F%2Freturn.sk&IPC=a&NAME=test&TPAY=Y&TEM=1&TIMESTAMP=01022021214520&HMAC=ffa9b92506ae87bff4bdbd7ef95d4877a37af79a1dc839585add56068083dc03',
            $response->getRedirectUrl()
        );
    }
}
