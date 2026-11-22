<?php

declare(strict_types=1);

namespace Omnipay\Core\Exception;

/**
 * A signature could not be computed, for example because the OpenSSL build lacks the cipher (DES needs the legacy provider).
 */
final class SignatureException extends \RuntimeException implements TatraException {}
