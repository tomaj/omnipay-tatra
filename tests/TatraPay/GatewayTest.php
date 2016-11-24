<?php

namespace Omnipay\TatraPay;

use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
	/**
     * @var Gateway
     */
    protected $gateway;

    public function setUp()
    {
        parent::setUp();
        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());
        $this->gateway->setMid(1111);
    }

    public function testPurchaseSign()
    {
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
}
