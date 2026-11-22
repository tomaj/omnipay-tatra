<?php

declare(strict_types=1);

namespace Omnipay\Core\Exception;

/**
 * The shared secret has a length that selects no signature algorithm, or one the service does not support.
 */
final class UnsupportedKeyException extends \InvalidArgumentException implements TatraException {}
