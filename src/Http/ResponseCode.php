<?php

declare(strict_types=1);

namespace Zarthus\World\App\Http;

enum ResponseCode: int
{
    case Ok = 200;
    case BadRequest = 400;
    case NotFound = 404;
}
