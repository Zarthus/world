<?php

declare(strict_types=1);

namespace Zarthus\World\Compiler;

enum CompileType
{
    case Plain;
    case Css;
    case Json;
    case Twig;
}
