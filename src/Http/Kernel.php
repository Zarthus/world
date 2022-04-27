<?php

declare(strict_types=1);

namespace Zarthus\World\App\Http;

use Amp\Http\Server\HttpServer;
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

        Loop::run(function () {
            $listeners = array_map(
                static fn (string $listener) => Uri::createFromString($listener),
                $this->environment->getStringArray(EnvVar::HttpListeners),
            );

            if ([] === $listeners) {
                throw new \InvalidArgumentException("No listeners configured for {$this->environment}, cannot start HTTP server.");
            }

            $sockets = array_map(
                function (Uri $listener) {
                    if ('https' === $listener->getScheme()) {
                        $context = $this->getContext(true);
                    } else {
                        $context = $this->getContext(false);
                    }

                    return Server::listen("{$listener->getHost()}:{$listener->getPort()}", $context);
                },
                $listeners,
            );

            $server = new HttpServer(
                $sockets,
                new CallableRequestHandler(function (Request $request) {
                    $this->httpLogger->info($request->getMethod() . ' request from [' . $request->getClient()->getRemoteAddress()->toString() . ']: ' . $request->getUri()->getPath());

                    /** @var Response $response */
                    $response = yield $this->handleRequest($request);
                    $this->httpLogger->info($response->getStatus() . ' response to   [' . $request->getClient()->getRemoteAddress()->toString() . ']: ' . $request->getUri()->getPath());

                    return $response;
                }),
                $this->logger,
            );

            $this->httpLogger->info('Starting up..');
            yield $server->start();

            if (!defined('SIGINT')) {
                $this->logger->warning('You are on a system without decent signal handling, functionality may not behave as intended.');
                return;
            }

            Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
                $this->logger->info('Shutting down.');
                $this->httpLogger->info('Shutting down.');
                Loop::cancel($watcherId);
                yield $server->stop();
            });
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
                $code = HttpStatusCode::NotFound->value;
                $response = yield call(fn () => $this->controller->error($code, new HttpException('Template not found', $code, $e)));
            } catch (\Throwable $t) {
                $code = HttpStatusCode::InternalServerError->value;
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
}
