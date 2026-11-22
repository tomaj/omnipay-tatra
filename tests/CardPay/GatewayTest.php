<?php

namespace Omnipay\CardPay;

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
            'ipc' => 'a',
            'name' => 'test',
        ]);

        self::assertInstanceOf('Omnipay\CardPay\Message\PurchaseRequest', $request);
        self::assertSame('10.00', $request->getAmount());

        $response = $request->send();
        self::assertInstanceOf(RedirectResponseInterface::class, $response);

        self::assertTrue($response->isRedirect());

        self::assertEquals(
            'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp?PT=CardPay&MID=1111&CURR=978&VS=123456&AMT=10.00&RURL=http%3A%2F%2Freturn.sk&IPC=a&NAME=test&SIGN=A6E11F14B1782C67',
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
            'ipc' => 'a',
            'name' => 'test',
        ]);

        self::assertInstanceOf('Omnipay\CardPay\Message\PurchaseRequest', $request);
        self::assertSame('10.00', $request->getAmount());

        $response = $request->send();
        self::assertInstanceOf(RedirectResponseInterface::class, $response);

        self::assertTrue($response->isRedirect());

        self::assertEquals(
            'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/e-commerce.jsp?PT=CardPay&MID=1111&CURR=978&VS=123456&AMT=10.00&RURL=http%3A%2F%2Freturn.sk&IPC=a&NAME=test&SIGN=53819B07462B0E9879342BE729EC5CC1',
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
            'ipc' => 'a',
            'name' => 'test',
        ]);

        $request->setTimestamp('01022021214520');


        self::assertInstanceOf('Omnipay\CardPay\Message\PurchaseRequest', $request);
        self::assertSame('10.00', $request->getAmount());

        $response = $request->send();
        self::assertInstanceOf(RedirectResponseInterface::class, $response);

        self::assertTrue($response->isRedirect());

        self::assertEquals(
            'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay?PT=CardPay&MID=1111&CURR=978&VS=123456&AMT=10.00&RURL=http%3A%2F%2Freturn.sk&IPC=a&NAME=test&TIMESTAMP=01022021214520&HMAC=cc562c09b3d75d8028c09ad541943d92246e5d78ef7ebc9de409229b6c133f8c',
            $response->getRedirectUrl(),
        );
    }

    public function testAuthorizeSignWithHmac()
    {
        $this->testedGateway->setSharedSecret('11111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111');

        $request = $this->testedGateway->authorize([
            'amount' => '1.01',
            'vs' => '0257430862',
            'currency' => 'EUR',
            'rurl' => 'http://return.sk',
            'name' => 'test',
            'tpay' => 'Y',
            'rem' => 'test@example.com',
            'ipc' => '127.0.0.1',
        ]);

        $request->setTimestamp('01022021214520');

        $response = $request->send();
        self::assertInstanceOf(RedirectResponseInterface::class, $response);

        self::assertTrue($response->isRedirect());
        self::assertEquals(
            'https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay?PT=CardPay&MID=1111&AMT=1.01&CURR=978&VS=0257430862&RURL=http%3A%2F%2Freturn.sk&IPC=127.0.0.1&NAME=test&TIMESTAMP=01022021214520&TXN=PA&REM=test%40example.com&TPAY=Y&HMAC=a6ae350560717be53682d381ae3abc714f5d7f65bf64a98a94e9110b8e25fb1a',
            $response->getRedirectUrl(),
        );
    }
}
