<?php

declare(strict_types=1);

namespace Zarthus\World\App\Http\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Monolog\Logger;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Zarthus\Http\Status\HttpStatusCode;
use Zarthus\World\App\App;
use Zarthus\World\App\Exception\HttpException;
use Zarthus\World\App\LogAwareTrait;
use Zarthus\World\App\Path;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Environment\EnvVar;
use Zarthus\World\Exception\CompilerException;
use Zarthus\World\Exception\FileNotFoundException;
use Zarthus\World\Exception\TemplateNotFoundException;

final class MainController
{
    use LogAwareTrait;

    public function __construct(
        private readonly Environment $environment,
        private readonly CompilerInterface $compiler,
    ) {
    }

    /**
     * @throws HttpException
     */
    public function handle(Request $request): Response
    {
        if ('GET' !== $request->getMethod()) {
            throw new HttpException("Unsupported method {$request->getMethod()} for {$request->getUri()->getPath()}", HttpStatusCode::MethodNotAllowed);
        }
        if (str_ends_with($request->getUri()->getPath(), '.php') && !str_contains($request->getUri()->getPath(), '..')) {
            return $this->handlePhp($request);
        }

        ['options' => $options, 'template' => $template] = $this->createOptions($request);

        try {
            $compiled = $this->compiler->renderTemplate($options, $template);
        } catch (TemplateNotFoundException $e) {
            $fallback = $this->fallback($request);
            if (null !== $fallback) {
                return $fallback;
            }

            throw new HttpException($e->getMessage(), HttpStatusCode::NotFound, $e);
        } catch (CompilerException | \Throwable $e) {
            throw new HttpException($e->getMessage(), HttpStatusCode::InternalServerError, $e);
        }

        return $this->respond(HttpStatusCode::Ok, (string) $compiled, $compiled->getMimeType());
    }

    /** @psalm-param int|HttpStatusCode $code */
    public function error(int|HttpStatusCode $code, HttpException $exception): Response
    {
        if (is_int($code)) {
            $code = HttpStatusCode::from($code);
        }

        if ($this->environment->getBool(EnvVar::Development)) {
            return $this->renderDebugErrorPage($code, $exception);
        }

        $compilerOptions = new CompilerOptions(Path::www(true) . '/html', Path::www(false) . '/', true);
        try {
            $compiled = $this->compiler->renderTemplate($compilerOptions, "errors/$code");
        } catch (TemplateNotFoundException $e) {
            $this->getLogger()->error("Non-existent error template for HTTP $code, serving 500", ['exception' => $e]);
            $compiled = $this->compiler->renderTemplate($compilerOptions, "errors/500");
        }

        return $this->respond($code, (string) $compiled, $compiled->getMimeType());
    }

    private function fallback(Request $request): ?Response
    {
        ['options' => $options, 'template' => $template] = $this->createOptions($request);

        $tryBackupFile = $options->getOutDirectory() . '/' . $template;
        if (!str_contains($tryBackupFile, '..') && file_exists($tryBackupFile)) {
            $this->getLogger()->warning("Fallback handler recovered 404 to (possibly cached) " . $template);
            return $this->respond(HttpStatusCode::Ok, file_get_contents($tryBackupFile), mime_content_type($tryBackupFile));
        }
        return null;
    }

    /**
     * @return array<string, string>
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    private function headers(string $mimeType, ?int $contentLength): array
    {
        $headers = [
            'Server' => App::name(),
            'Date' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC850),
            'Content-Type' => "$mimeType; charset=UTF-8",
            'Cache-Control' => 'no-store',
            'Connection' => 'close',
            'Allow' => 'GET',
        ];
        if (null !== $contentLength) {
            $headers['Content-Length'] = $contentLength;
        }

        return $headers;
    }

    private function renderDebugErrorPage(int $code, HttpException $exception): Response
    {
        $this->getLogger()->debug($exception::class . "($code): {$exception->getMessage()}");
        $htmlErrorRenderer = new HtmlErrorRenderer(true);
        $e = $htmlErrorRenderer->render($exception->getPrevious() ?? $exception);
        return new Response($code, $e->getHeaders(), $e->getAsString());
    }

    /** @return array{options: CompilerOptions, template: string} */
    private function createOptions(Request $request): array
    {
        $firstPathElement = strtok($request->getUri()->getPath(), '/');
        $inDirectory = false === $firstPathElement ? 'html' : $firstPathElement;
        if (!is_dir(Path::www(true) . "/$inDirectory")) {
            $inDirectory = 'html';
            $template = ltrim($request->getUri()->getPath(), '/');
        } else {
            $template = ltrim(str_replace($inDirectory, '', $request->getUri()->getPath()), '/');
        }
        $outDirectory = Path::www(false) . '/' . (false === $firstPathElement ? '' : $firstPathElement);
        $template = empty($template) ? '/' : $template;

        return [
            'options' => new CompilerOptions(
                Path::www(true) . '/' . $inDirectory,
                $outDirectory,
                true,
            ),
            'template' => $template,
        ];
    }

    /**
     * In rare cases we may want to bypass the compiler
     */
    private function handlePhp(Request $request): Response
    {
        $this->getLogger()->debug('Attempting to load a PHP script.');
        $path = Path::www(false) . $request->getUri()->getPath();
        if (!file_exists($path)) {
            throw new FileNotFoundException($path);
        }
        $this->getLogger()->notice('Loading ' . $request->getUri()->getPath());
        ob_start();
        /** @var mixed $output */
        $output = require $path;
        $result = ob_get_clean();
        return $this->respond(HttpStatusCode::Ok, !is_string($output) ? $result : $output, 'text/plain');
    }

    private function respond(HttpStatusCode $code, string $response, string $mimeType): Response
    {
        return new Response(
            $code->value,
            $this->headers($mimeType, mb_strlen($response)),
            $response,
        );
    }
}
