<?php

declare(strict_types=1);

namespace Zarthus\World\Test\System\App\Http\Controller;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use League\Uri\Http;
use Zarthus\Http\Method\HttpMethod;
use Zarthus\Http\Status\HttpStatusCode;
use Zarthus\World\App\Exception\HttpException;
use Zarthus\World\App\Http\Controller\MainController;
use Zarthus\World\Test\Framework\ContainerAwareTestCase;

final class MainControllerTest extends ContainerAwareTestCase
{
    /** @return list<list<HttpMethod>> */
    public function dataHttpMethods(): array
    {
        return [
            [HttpMethod::Put],
            [HttpMethod::Post],
            [HttpMethod::Patch],
            [HttpMethod::Options],
            [HttpMethod::Delete],
        ];
    }

    /** @return list<list<string>> */
    public function dataEndpoints(): array
    {
        return [
            ['/', 'text/html'],
            ['/favicon.ico', 'image/x-icon'],
            ['/style/dark.css', 'text/css'],
            //['/api/info.json', 'application/json'],

            //['/errors/404.html', 'text/html', HttpStatusCode::NotFound],
            //['/errors/500.html', 'text/html', HttpStatusCode::InternalServerError],
        ];
    }

    /** @dataProvider dataHttpMethods */
    public function testAcceptOnlyHttpGet(HttpMethod $method): void
    {
        $this->expectHttpException(HttpStatusCode::MethodNotAllowed);
        $this->getController()->handle($this->createRequest('https://world/foo', $method));
    }

    /** @dataProvider dataEndpoints */
    public function testHttpEndpoints(string $endpoint, string $expectedContentType, HttpStatusCode $expectedStatusCode = HttpStatusCode::Ok): void
    {
        $response = $this->getController()->handle($this->createRequest('https://world' . $endpoint));

        $this->assertHttpStatusCode($expectedStatusCode, $response);
        $this->assertHeader('Content-Type', "$expectedContentType; charset=UTF-8", $response);
    }

    private function expectHttpException(HttpStatusCode $code): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode($code->value);
    }

    private function assertHttpStatusCode(HttpStatusCode $expectedCode, Response $response): void
    {
        $this->assertSame($expectedCode->value, $response->getStatus(), "HTTP $expectedCode->name ($expectedCode->value) expected, {$response->getStatus()} received.");
    }

    private function assertHeader(string $header, string $expectedResult, Response $response): void
    {
        $this->assertNotNull($response->getHeader($header), "Expected header $header to be present.");
        $this->assertSame($expectedResult, $response->getHeader($header), "Header $header does not match expectation.");
    }

    private function createRequest(string $uri, HttpMethod $method = HttpMethod::Get): Request
    {
        return new Request(
            $this->createMock(Client::class),
            $method->value,
            Http::createFromString($uri),
            [],
            null,
            '2.0',
            null
        );
    }

    private function getController(): MainController
    {
        return $this->getContainer()->get(MainController::class);
    }
}
