<?php

declare(strict_types=1);

namespace Zarthus\World\Environment;

enum EnvVar
{
    /** Canonical name of the environment, e.g. Production */
    case Name;
    /** A boolean whether dev / debug mode is enabled. */
    case Development;
    /** The minimum loglevel, as Psr\Log\LogLevel */
    case LogLevel;

    /**
     * list<string> - may be empty if no webserver is needed.
     * examples: https://127.0.0.1:4443/, https://domain.dev:4443/
     */
    case HttpListeners;
    /**
     * In the event HTTPS is enabled, a path should be returned here.
     * See `bin/gen-ca.sh` for guidance.
     */
    case HttpCertificatePath;
    /** Public directory of the webserver, in some cases it may not be `/` */
    case HttpBaseDir;

    /**
     * Enables SASS support, you must have a valid `sass` binary on your system (locatable by PATH env)
     */
    case Sass;

    /**
     * Compress as much as possible
     */
    case Compress;
}
