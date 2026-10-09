<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Http\AbstractInterface\REST;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
    public function testActionAndAfterActionHooksRun(): void
    {
        $response = new Response();
        $controller = new ControllerFixture(new Request(), $response, 'action');

        $this->assertNull($controller->__hook());
        $this->assertSame('action', (string) $response->getBody());
        $this->assertSame(['action'], $controller->afterActions);
    }

    public function testOnRequestCanPreventAction(): void
    {
        $response = new Response();
        $controller = new ControllerFixture(new Request(), $response, 'action');
        $controller->allowRequest = false;

        $controller->__hook();

        $this->assertSame('', (string) $response->getBody());
        $this->assertSame(['action'], $controller->afterActions);
    }

    public function testMissingActionUsesNotFoundHandler(): void
    {
        $response = new Response();
        $controller = new ControllerFixture(new Request(), $response, 'missing');

        $controller->__hook();

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('has not action for missing', (string) $response->getBody());
    }

    public function testActionExceptionIsPassedToControllerExceptionHandler(): void
    {
        $controller = new ControllerFixture(new Request(), new Response(), 'failure');

        $controller->__hook();

        $this->assertSame(['action failed'], $controller->exceptions);
        $this->assertSame(['failure'], $controller->afterActions);
    }

    public function testActionArgumentsAreForwarded(): void
    {
        $response = new Response();
        $controller = new ControllerFixture(new Request(), $response, 'withArguments');

        $controller->__hook(['first', 2]);

        $this->assertSame('first-2', (string) $response->getBody());
    }

    public function testRestControllerMapsMethodToAction(): void
    {
        $request = new Request();
        $request->withMethod('GET');
        $response = new Response();
        $controller = new RestFixture($request, $response, 'index');

        $controller->__hook();

        $this->assertSame('get-index', (string) $response->getBody());
    }
}

class ControllerFixture extends Controller
{
    public bool $allowRequest = true;
    public array $afterActions = [];
    public array $exceptions = [];

    public function action(): void
    {
        $this->response()->write('action');
    }

    public function withArguments(string $first, int $second): void
    {
        $this->response()->write($first . '-' . $second);
    }

    public function failure(): void
    {
        throw new \RuntimeException('action failed');
    }

    protected function onRequest(?string $action): ?bool
    {
        return $this->allowRequest;
    }

    protected function afterAction(?string $actionName): void
    {
        $this->afterActions[] = $actionName;
    }

    protected function onException(\Throwable $throwable): void
    {
        $this->exceptions[] = $throwable->getMessage();
    }
}

class RestFixture extends REST
{
    public function GETIndex(): void
    {
        $this->response()->write('get-index');
    }
}
