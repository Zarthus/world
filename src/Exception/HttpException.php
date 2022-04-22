<?php

declare(strict_types=1);

namespace Zarthus\World\App\Exception;

/**
 * HTTP Exceptions have the code be identical to the http error we should serve.
 */
final class HttpException extends \Exception
{
    public function __construct(string $message, int $code, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
