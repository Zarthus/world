<?php
declare(strict_types=1);

namespace Zarthus\World\App\Exception;

final class PhpError extends \Error
{
    public function __construct(
        string $message,
        int $code,
        string $file,
        int $line,
    ) {
        parent::__construct($message . " (in $file:$line)", $code, null);
    }
}
