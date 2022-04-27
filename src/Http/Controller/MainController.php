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
    public function __construct(
        private readonly Environment $environment,
        private readonly CompilerInterface $compiler,
        private readonly Logger $logger,
    ) {
    }

    /**
     * @throws HttpException
     */
    public function handle(Request $request): Response
    {
        if ('GET' !== $request->getMethod()) {
            throw new HttpException("Unsupported method {$request->getMethod()} for {$request->getUri()->getPath()}", 405);
        }

        ['options' => $options, 'template' => $template] = $this->createOptions($request);

        try {
            $compiled = $this->compiler->renderTemplate($options, $template);
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

        $compilerOptions = new CompilerOptions(Path::www(true) . '/html', Path::www(false) . '/', true);
        try {
            $compiled = $this->compiler->renderTemplate($compilerOptions, "errors/$code");
        } catch (TemplateNotFoundException $e) {
            $this->logger->error("Non-existent error template for HTTP $code, serving 500", ['exception' => $e]);
            $compiled = $this->compiler->renderTemplate($compilerOptions, "errors/500");
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

    /** @return array{options: CompilerOptions, template: string} */
    private function createOptions(Request $request): array
    {
        $firstPathElement = strtok($request->getUri()->getPath(), '/');
        $inDirectory = false === $firstPathElement ? '/html' : $firstPathElement;
        if (!is_dir(Path::www(true) . "/$inDirectory")) {
            $inDirectory = '/html';
            $template = $request->getUri()->getPath();
        } else {
            $template = str_replace($inDirectory, '', $request->getUri()->getPath());
        }
        $outDirectory = Path::www(false);
        $template = empty($template) ? $request->getUri()->getPath() : $template;

        return [
            'options' => new CompilerOptions(Path::www(true) . '/' . $inDirectory, Path::www(false) . '/' . $outDirectory, true),
            'template' => $template,
        ];
    }
}
