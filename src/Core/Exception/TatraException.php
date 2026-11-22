<?php

declare(strict_types=1);

namespace Omnipay\Core\Exception;

/**
 * Implemented by every exception this package throws, so a caller can catch all of them at once.
 *
 * Omnipay's own InvalidRequestException (invalid parameters, a wrong signature or variable symbol in a callback)
 * and InvalidResponseException (a wrong signature in a response from the bank) are thrown as they are, because
 * they are part of the Omnipay contract.
 */
interface TatraException extends \Throwable {}
