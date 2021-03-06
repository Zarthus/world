<?php

declare(strict_types=1);

namespace Zarthus\World\App\Http\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
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
use Zarthus\World\File\DirectoryMappingInterface;

final class MainController
{
    use LogAwareTrait;

    public function __construct(
        private readonly Environment $environment,
        private readonly CompilerInterface $compiler,
        private readonly DirectoryMappingInterface $directoryMapping,
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
            $tryBackupFile = $options->getOutDirectory() . '/' . $template;
            if (!str_contains($tryBackupFile, '..') && file_exists($tryBackupFile) && !is_dir($tryBackupFile)) {
                $this->getLogger()->error("Found a match on public folder, indicative of a bug in compiler pattern matching: " . $tryBackupFile);
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
            $compiled = $this->compiler->renderTemplate($compilerOptions, "errors/$code->value");
        } catch (TemplateNotFoundException $e) {
            $this->getLogger()->error("Non-existent error template for HTTP $code->value, serving 500", ['exception' => $e]);
            $compiled = $this->compiler->renderTemplate($compilerOptions, "errors/500");
        }

        return $this->respond($code, (string) $compiled, $compiled->getMimeType());
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

    private function renderDebugErrorPage(HttpStatusCode $code, HttpException $exception): Response
    {
        $this->getLogger()->debug($exception::class . "($code->value): {$exception->getMessage()}");
        $htmlErrorRenderer = new HtmlErrorRenderer(true);
        $e = $htmlErrorRenderer->render($exception->getPrevious() ?? $exception);
        return new Response($code->value, $e->getHeaders(), $e->getAsString());
    }

    /** @return array{options: CompilerOptions, template: string} */
    private function createOptions(Request $request): array
    {
        $inDirectory = $outDirectory = $this->directoryMapping->resolveDirectory($request->getUri()->getPath());
        $template = $this->directoryMapping->resolveFilePath($request->getUri()->getPath());

        if (empty($template)) {
            $template = '/';
        }

        return [
            'options' => new CompilerOptions(
                Path::www(true) . '/' . $inDirectory,
                Path::www(false) . '/' . ('html' === $outDirectory ? '' : $outDirectory),
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
