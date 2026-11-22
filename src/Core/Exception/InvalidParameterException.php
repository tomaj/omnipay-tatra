<?php

declare(strict_types=1);

namespace Omnipay\Core\Exception;

/**
 * A parameter was given a value the bank does not accept.
 */
final class InvalidParameterException extends \UnexpectedValueException implements TatraException {}
