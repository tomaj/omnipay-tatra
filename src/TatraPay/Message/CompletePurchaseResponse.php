<?php

declare(strict_types=1);

namespace Omnipay\TatraPay\Message;

use Omnipay\Core\Message\AbstractCallbackResponse;

/**
 * The verified result of a TatraPay payment: RES is OK (paid), FAIL or TOUT (timeout).
 */
final class CompletePurchaseResponse extends AbstractCallbackResponse {}
