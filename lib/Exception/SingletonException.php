<?php

declare(strict_types=1);

namespace Zarthus\World\Exception;

final class SingletonException extends \RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('This class cannot be instantiated, as it is a singleton.', 0, $previous);
    }
}
