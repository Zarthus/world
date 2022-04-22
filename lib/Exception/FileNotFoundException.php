<?php

declare(strict_types=1);

namespace Zarthus\World\Exception;

final class FileNotFoundException extends \Exception
{
    public function __construct(string $path)
    {
        parent::__construct("File at '$path' does not exist, or is not readable.");
    }
}
