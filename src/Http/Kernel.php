<?php

declare(strict_types=1);

namespace Zarthus\World\App\Http;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Options;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Loop;
use Amp\Promise;
use Amp\Socket\BindContext;
use Amp\Socket\Certificate;
use Amp\Socket\Server;
use Amp\Socket\ServerTlsContext;
use League\Uri\Uri;
use Monolog\Handler\RotatingFileHandler;
use Psr\Log\LogLevel;
use Zarthus\Http\Status\HttpStatusCode;
use Zarthus\World\App\App;
use Zarthus\World\App\Cli\Command\WebserverCommand;
use Zarthus\World\App\Exception\HttpException;
use Zarthus\World\App\Http\Controller\MainController;
use Zarthus\World\App\Path;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Environment\EnvVar;
use Zarthus\World\Exception\FileNotFoundException;
use Zarthus\World\Exception\TemplateIllegalException;
use Zarthus\World\Exception\TemplateNotFoundException;
use function Amp\call;

/**
 * Primarily for development purposes, start a webserver with PHP support and use this entrypoint
 * to develop templates live. See {@see WebserverCommand}
 *
 * If you need extensive debugging, run `php -d zend.assertions=1 bin/cli webserver` instead.
 */
final class Kernel
{
    private \Monolog\Logger $logger;
    private \Monolog\Logger $httpLogger;

    public function __construct(
        private readonly MainController $controller,
        private readonly Environment $environment,
    ) {
        $this->logger = clone App::getLogger('Kernel');
        $this->logger->pushHandler(
            new RotatingFileHandler(Path::tmp() . '/log/http.log', 2, $this->environment->get(EnvVar::LogLevel)),
        );
        $this->httpLogger = new \Monolog\Logger('HTTP', [
            new RotatingFileHandler(Path::tmp() . '/log/requests.log', 1, $this->environment->get(EnvVar::LogLevel)),
        ]);
        $this->httpLogger->useMicrosecondTimestamps(false);
    }

    public function start(): void
    {
        ini_set('max_execution_time', '0');
        $server = $this->createHttpServer();

        if (!defined('SIGHUP') || !defined('SIGINT')) {
            $this->logger->warning('You are on a system without decent signal handling, functionality may not behave as intended.');
        } else {
            Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
                $this->logBoth(LogLevel::INFO, 'Shutting down webserver..');
                Loop::cancel($watcherId);
                yield $server->stop();
                Loop::stop();
            });
        }

        Loop::run(function () use ($server) {
            $this->logBoth(LogLevel::INFO, 'Starting up webserver..');
            yield $server->start();
        });
    }

    /**
     * @return Promise<Response>
     */
    public function handleRequest(Request $request): Promise
    {
        return call(function () use ($request) {
            try {
                $response = yield call(fn () => $this->controller->handle($request));
            } catch (HttpException $e) {
                $response = yield call(fn () => $this->controller->error($e->getCode(), $e));
            } catch (TemplateNotFoundException | TemplateIllegalException $e) {
                $code = HttpStatusCode::NotFound;
                $response = yield call(fn () => $this->controller->error($code, new HttpException('Template not found', $code, $e)));
            } catch (\Throwable $t) {
                $code = HttpStatusCode::InternalServerError;
                $response = yield call(fn () => $this->controller->error($code, new HttpException('Internal Server Error', $code, $t)));
            }

            return $response;
        });
    }

    private function getContext(bool $tls): ?BindContext
    {
        if ($tls) {
            $path = $this->environment->getNullableString(EnvVar::HttpCertificatePath);
            if (null === $path) {
                throw new \InvalidArgumentException("Environment {$this->environment} is not configured for https listeners, you need to define HttpCertificatePath");
            }
            $path = str_replace('{root}', Path::root(), $path);
            if (!file_exists($path)) {
                throw new FileNotFoundException($path);
            }
            $cert = new Certificate($path);

            return (new BindContext())
                ->withTlsContext(
                    (new ServerTlsContext())
                        ->withDefaultCertificate($cert),
                );
        }

        return null;
    }

    private function createOptions(): Options
    {
        $options = new Options();
        if ($this->environment->getBool(EnvVar::Development)) {
            $options = $options->withDebugMode();
        }

        if ($this->environment->get(EnvVar::Compress)) {
            $options = $options->withCompression();
        }

        return $options;
    }

    private function createHttpServer(): HttpServer
    {
        $listeners = array_map(
            static fn (string $listener) => Uri::createFromString($listener),
            $this->environment->getStringArray(EnvVar::HttpListeners),
        );

        if ([] === $listeners) {
            throw new \InvalidArgumentException("No listeners configured for {$this->environment}, cannot start HTTP server.");
        }

        $sockets = array_map(
            fn (Uri $listener) => Server::listen(
                "{$listener->getHost()}:{$listener->getPort()}",
                $this->getContext('https' === $listener->getScheme())
            ),
            $listeners,
        );

        return new HttpServer(
            $sockets,
            new CallableRequestHandler(function (Request $request) {
                $this->httpLogger->info($request->getMethod() . ' request from [' . $request->getClient()->getRemoteAddress()->toString() . ']: ' . $request->getUri()->getPath());

                /** @var Response $response */
                $response = yield $this->handleRequest($request);
                $this->httpLogger->info($response->getStatus() . ' response to  [' . $request->getClient()->getRemoteAddress()->toString() . ']: ' . $request->getUri()->getPath());

                return $response;
            }),
            $this->logger,
            $this->createOptions(),
        );
    }

    /**
     * @psalm-param $level LogLevel::*
     * @psalm-suppress ArgumentTypeCoercion
     */
    private function logBoth(string $level, string $message): void
    {
        $this->logger->log($level, $message);
    }
}
