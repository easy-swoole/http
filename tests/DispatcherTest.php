<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\Dispatcher;
use EasySwoole\Http\Message\Uri;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use PHPUnit\Framework\TestCase;

class DispatcherTest extends TestCase
{
    private const CONTROLLER_NAMESPACE = 'EasySwoole\\Http\\Tests\\Fixtures\\Dispatch';

    public function testControllerActionCanBeDispatched(): void
    {
        $response = $this->dispatch('/index/hello');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('hello', (string) $response->getBody());
    }

    public function testCallableRouteCanStopControllerDispatch(): void
    {
        $response = $this->dispatch('/route');

        $this->assertSame('route', (string) $response->getBody());
    }

    public function testStringRouteCanForwardToController(): void
    {
        $response = $this->dispatch('/forward');

        $this->assertSame('hello', (string) $response->getBody());
    }

    public function testControllerCanForwardToAnotherAction(): void
    {
        $response = $this->dispatch('/index/forward');

        $this->assertSame('hello', (string) $response->getBody());
    }

    public function testMissingActionReturnsNotFoundResponse(): void
    {
        $response = $this->dispatch('/index/missing');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('has not action for missing', (string) $response->getBody());
    }

    public function testExceptionCanBeHandledByDispatcher(): void
    {
        $dispatcher = new Dispatcher(self::CONTROLLER_NAMESPACE);
        $dispatcher->setHttpExceptionHandler(
            function (\Throwable $throwable, Request $request, Response $response): void {
                $response->withStatus(500)->write($throwable->getMessage());
            }
        );
        $response = new Response();

        $dispatcher->dispatch($this->request('/index/exception'), $response);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('controller error', (string) $response->getBody());
    }

    public function testDefaultDispatcherDoesNotAccessUninitializedNamespace(): void
    {
        $response = new Response();
        (new Dispatcher())->dispatch($this->request('/'), $response);

        $this->assertSame(404, $response->getStatusCode());
    }

    private function dispatch(string $path): Response
    {
        $response = new Response();
        (new Dispatcher(self::CONTROLLER_NAMESPACE))->dispatch($this->request($path), $response);
        return $response;
    }

    private function request(string $path): Request
    {
        $request = new Request();
        $request->withMethod('GET')->withUri(new Uri($path));
        return $request;
    }
}
