<?php

declare(strict_types=1);

namespace Zarthus\World\App\Cli;

use Symfony\Component\String\UnicodeString;

trait ResolvableNameTrait
{
    public function getName(): string
    {
        $className = static::class;
        $classSplit = explode('\\', $className);
        $classEnd = $classSplit[array_key_last($classSplit)];

        return (new UnicodeString($classEnd))->replace('Command', '')->snake()->replace('_', ':')->toString();
    }
}
