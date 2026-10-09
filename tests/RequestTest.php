<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testHeadersFromSwooleRequestAreNormalizedToLowercase(): void
    {
        $swooleRequest = new FakeSwooleRequest();
        $swooleRequest->header = [
            'Host' => 'example.com',
            'X-Trace-ID' => 'trace-123',
            'ACCEPT' => 'application/json',
        ];

        $request = new Request($swooleRequest);

        $this->assertSame([
            'host' => ['example.com'],
            'x-trace-id' => ['trace-123'],
            'accept' => ['application/json'],
        ], $request->getHeaders());
        $this->assertSame(['example.com'], $request->getHeader('host'));
        $this->assertSame('trace-123', $request->getHeaderLine('x-trace-id'));
        $this->assertSame(['application/json'], $request->getHeader('accept'));
    }

    public function testHeadersWithDifferentCasingAreMergedAfterNormalization(): void
    {
        $swooleRequest = new FakeSwooleRequest();
        $swooleRequest->header = [
            'X-Test' => 'first',
            'x-test' => 'second',
        ];

        $request = new Request($swooleRequest);

        $this->assertSame(['first', 'second'], $request->getHeader('x-test'));
        $this->assertCount(1, $request->getHeaders());
    }

    public function testMissingSwooleHeadersProduceEmptyHeaderCollection(): void
    {
        $swooleRequest = new FakeSwooleRequest();
        $swooleRequest->header = null;

        $request = new Request($swooleRequest);

        $this->assertSame([], $request->getHeaders());
        $this->assertSame([], $request->getHeader('missing'));
        $this->assertSame('', $request->getHeaderLine('missing'));
    }

    public function testRequestParametersPreferParsedBodyOverQuery(): void
    {
        $request = new Request();
        $request->withParsedBody(['same' => 'body', 'body' => true]);
        $request->withQueryParams(['same' => 'query', 'query' => true]);

        $this->assertSame([
            'same' => 'body',
            'body' => true,
            'query' => true,
        ], $request->getRequestParam());
        $this->assertSame('body', $request->getRequestParam('same'));
        $this->assertSame([
            'body' => true,
            'missing' => null,
        ], $request->getRequestParam('body', 'missing'));
    }
}

class FakeSwooleRequest extends \Swoole\Http\Request
{
    public function __construct()
    {
        $this->fd = 0;
        $this->server = [
            'server_protocol' => 'HTTP/1.1',
            'path_info' => '/',
            'request_method' => 'GET',
            'server_port' => 80,
        ];
    }

    public function rawContent(): string|false
    {
        return '';
    }
}
