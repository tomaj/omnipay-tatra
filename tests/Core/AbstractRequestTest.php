<?php

declare(strict_types=1);

namespace Omnipay\Tests\Tatra\Core;

use Omnipay\Core\Message\AbstractRequest;
use Omnipay\TatraPay\Gateway;
use Omnipay\TatraPay\Message\PurchaseResponse;
use Omnipay\Tests\Tatra\Support\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class AbstractRequestTest extends TestCase
{
    private AbstractRequest $request;

    #[\Override]
    protected function setUp(): void
    {
        $this->request = new Gateway($this->getHttpClient(), $this->getHttpRequest())->purchase();
    }

    public function testParameters(): void
    {
        $this->request->initialize([
            'mid' => 1,
            'sharedSecret' => 'secret',
            'testHost' => 'http://localhost',
            'vs' => 2,
            'cs' => '0308',
            'ss' => '3',
            'desc' => 'Order',
            'rsms' => '0900123456',
            'lang' => 'sk',
            'rem' => 'a@b.sk',
            'rurl' => 'http://return.sk',
        ]);

        self::assertSame('1', $this->request->getMid());
        self::assertSame('secret', $this->request->getSharedSecret());
        self::assertSame('http://localhost', $this->request->getTestHost());
        self::assertSame('2', $this->request->getVs());
        self::assertSame('0308', $this->request->getCs());
        self::assertSame('3', $this->request->getSs());
        self::assertSame('Order', $this->request->getDesc());
        self::assertSame('0900123456', $this->request->getRsms());
        self::assertSame('sk', $this->request->getLang());
        self::assertSame('a@b.sk', $this->request->getRem());
        self::assertSame('http://return.sk', $this->request->getRurl());
        self::assertNull($this->request->getAredir());
    }

    /**
     * @return array<string, array{bool|int|string, int}>
     */
    public static function aredirProvider(): array
    {
        return [
            'bool true' => [true, 1],
            'bool false' => [false, 0],
            'int 1' => [1, 1],
            'int 0' => [0, 0],
            'string' => ['yes', 1],
            'empty string' => ['', 0],
        ];
    }

    #[DataProvider('aredirProvider')]
    public function testAredir(bool|int|string $value, int $expected): void
    {
        self::assertSame($expected, $this->request->setAredir($value)->getAredir());
    }

    public function testResponseRejectsAnotherRequest(): void
    {
        $response = new PurchaseResponse(new Gateway($this->getHttpClient(), $this->getHttpRequest())->completePurchase(), []);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageIsOrContains('Response was created for an unexpected request Omnipay\TatraPay\Message\CompletePurchaseRequest');
        $response->getRedirectUrl();
    }

    public function testRedirectDataMustBeAnArray(): void
    {
        $response = new PurchaseResponse($this->request, 'invalid');

        self::assertSame([], $response->getRedirectData());
    }

    public function testSendDataRequiresAnArray(): void
    {
        $this->request->initialize(['sharedSecret' => self::AES_KEY, 'currency' => 'EUR']);

        $this->expectException(\InvalidArgumentException::class);
        $this->request->sendData('invalid');
    }
}
