<?php

declare(strict_types=1);

namespace Zarthus\World\App\Http\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Monolog\Logger;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Zarthus\Http\Status\HttpStatusCode;
use Zarthus\World\App\Exception\HttpException;
use Zarthus\World\App\Path;
use Zarthus\World\Compiler\CompilerInterface;
use Zarthus\World\Compiler\CompilerOptions;
use Zarthus\World\Environment\Environment;
use Zarthus\World\Environment\EnvVar;
use Zarthus\World\Exception\CompilerException;
use Zarthus\World\Exception\TemplateNotFoundException;

final class MainController
{
    private CompilerOptions $compilerOptions;

    public function __construct(
        private readonly Environment $environment,
        private readonly CompilerInterface $compiler,
        private readonly Logger $logger,
    ) {
        $this->compilerOptions = new CompilerOptions(Path::templates(true), Path::templates(false), true);
    }

    /**
     * @throws HttpException
     */
    public function handle(Request $request): Response
    {
        if ('GET' !== $request->getMethod()) {
            throw new HttpException("Unsupported method {$request->getMethod()} for {$request->getUri()->getPath()}", 405);
        }

        try {
            $compiled = $this->compiler->renderTemplate($this->compilerOptions, $request->getUri()->getPath());
        } catch (TemplateNotFoundException $e) {
            throw new HttpException($e->getMessage(), HttpStatusCode::NotFound->value, $e);
        } catch (CompilerException | \Throwable $e) {
            throw new HttpException($e->getMessage(), HttpStatusCode::InternalServerError->value, $e);
        }

        return new Response(HttpStatusCode::Ok->value, $this->headers($compiled->getMimeType()), (string) $compiled);
    }

    /** @psalm-param int|HttpStatusCode $code */
    public function error(int|HttpStatusCode $code, HttpException $exception): Response
    {
        if ($code instanceof HttpStatusCode) {
            $code = $code->value;
        }

        if ($this->environment->getBool(EnvVar::Development)) {
            return $this->renderDebugErrorPage($code, $exception);
        }

        try {
            $compiled = $this->compiler->renderTemplate($this->compilerOptions, "errors/$code");
        } catch (TemplateNotFoundException $e) {
            $this->logger->error("Non-existent error template for HTTP $code, serving 500", ['exception' => $e]);
            $compiled = $this->compiler->renderTemplate($this->compilerOptions, "errors/500");
        }

        return new Response($code, $this->headers($compiled->getMimeType()), (string) $compiled);
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $mimeType): array
    {
        return [
            'Content-Type' => "$mimeType; charset=UTF-8",
        ];
    }

    private function renderDebugErrorPage(int $code, HttpException $exception): Response
    {
        $this->logger->debug($exception::class . "($code): {$exception->getMessage()}");
        $htmlErrorRenderer = new HtmlErrorRenderer(true);
        $e = $htmlErrorRenderer->render($exception);
        return new Response($code, $e->getHeaders(), $e->getAsString());
    }
}
