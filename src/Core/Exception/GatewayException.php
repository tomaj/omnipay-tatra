<?php

declare(strict_types=1);

namespace Omnipay\Core\Exception;

/**
 * The bank's server answered a server-to-server request with an error (for example a non-200 HTTP status).
 */
final class GatewayException extends \RuntimeException implements TatraException {}
