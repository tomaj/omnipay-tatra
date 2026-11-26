<?php

declare(strict_types=1);

namespace Omnipay\Tests\Tatra;

use Omnipay\CardPay\Message\CancelAuthorizeRequest;
use Omnipay\CardPay\Message\CancelAuthorizeTxn;
use Omnipay\ComfortPay\CardStatus;
use Omnipay\ComfortPay\Gateway as ComfortPayGateway;
use Omnipay\ComfortPay\TransactionType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

/**
 * The 4.x constants keep their values and emit E_USER_DEPRECATED through #[\Deprecated] (#48); 6.0 removes them.
 */
final class DeprecatedConstantsTest extends TestCase
{
    /**
     * @return array<string, array{class-string, string, string, \BackedEnum}>
     */
    public static function constantProvider(): array
    {
        return [
            'TXN_CLOSE_PREAUTHORIZATION' => [CancelAuthorizeRequest::class, 'TXN_CLOSE_PREAUTHORIZATION', 'CPA', CancelAuthorizeTxn::ClosePreauthorization],
            'TXN_STORNO_PREAUTHORIZATION' => [CancelAuthorizeRequest::class, 'TXN_STORNO_PREAUTHORIZATION', 'SPA', CancelAuthorizeTxn::StornoPreauthorization],
            'TXN_CHARGEBACK' => [CancelAuthorizeRequest::class, 'TXN_CHARGEBACK', 'CB', CancelAuthorizeTxn::Chargeback],
            'TRANSACTION_TYPE_PURCHASE' => [ComfortPayGateway::class, 'TRANSACTION_TYPE_PURCHASE', 'PURCHASE', TransactionType::Purchase],
            'TRANSACTION_TYPE_PREAUTHORIZATION' => [ComfortPayGateway::class, 'TRANSACTION_TYPE_PREAUTHORIZATION', 'PREAUTHORIZATION', TransactionType::Preauthorization],
            'TRANSACTION_TYPE_PREAUTH_CONFIRM' => [ComfortPayGateway::class, 'TRANSACTION_TYPE_PREAUTH_CONFIRM', 'PREAUTH-CONFIRM', TransactionType::PreauthConfirm],
            'TRANSACTION_TYPE_PREAUTH_CANCEL' => [ComfortPayGateway::class, 'TRANSACTION_TYPE_PREAUTH_CANCEL', 'PREAUTH-CANCEL', TransactionType::PreauthCancel],
            'TRANSACTION_TYPE_CHARGEBACK' => [ComfortPayGateway::class, 'TRANSACTION_TYPE_CHARGEBACK', 'CHARGEBACK', TransactionType::Chargeback],
            'CARD_STATUS_OK' => [ComfortPayGateway::class, 'CARD_STATUS_OK', 'OK', CardStatus::Ok],
            'CARD_STATUS_FAIL' => [ComfortPayGateway::class, 'CARD_STATUS_FAIL', 'FAIL', CardStatus::Fail],
            'CARD_STATUS_UNKNOWN' => [ComfortPayGateway::class, 'CARD_STATUS_UNKNOWN', 'UNKNOWN', CardStatus::Unknown],
        ];
    }

    /**
     * @param class-string $class
     * @param string $value the 4.x value
     */
    #[DataProvider('constantProvider')]
    #[IgnoreDeprecations]
    public function testConstantIsDeprecatedAndKeepsItsValue(string $class, string $constant, string $value, \BackedEnum $replacement): void
    {
        $this->expectUserDeprecationMessage(sprintf(
            'Constant %s::%s is deprecated since 5.0, use %s::%s',
            $class,
            $constant,
            $replacement::class,
            $replacement->name,
        ));

        self::assertSame($value, $replacement->value);
        self::assertSame($value, constant($class . '::' . $constant));
    }

    public function testEveryDeprecatedConstantIsCovered(): void
    {
        $deprecated = [];
        foreach ([CancelAuthorizeRequest::class, ComfortPayGateway::class] as $class) {
            foreach (new \ReflectionClass($class)->getReflectionConstants() as $constant) {
                if ($constant->isDeprecated()) {
                    $deprecated[] = $constant->getName();
                }
            }
        }
        sort($deprecated);

        $covered = array_keys(self::constantProvider());
        sort($covered);

        self::assertSame($covered, $deprecated);
    }
}
