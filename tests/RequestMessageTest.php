<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\Message\Request;
use EasySwoole\Http\Message\Uri;
use PHPUnit\Framework\TestCase;

class RequestMessageTest extends TestCase
{
    public function testRequestTargetContainsPathAndQuery(): void
    {
        $request = new Request('GET', new Uri('/items?page=2'));

        $this->assertSame('/items?page=2', $request->getRequestTarget());
    }

    public function testExplicitRequestTargetTakesPrecedence(): void
    {
        $request = new Request('GET', new Uri('/items'));
        $request->withRequestTarget('*');

        $this->assertSame('*', $request->getRequestTarget());
    }

    public function testMethodIsNormalizedToUppercase(): void
    {
        $request = new Request();
        $request->withMethod('post');

        $this->assertSame('POST', $request->getMethod());
    }

    public function testChangingUriUpdatesHostHeader(): void
    {
        $request = new Request();
        $request->withUri(new Uri('https://example.com:8443/path'));

        $this->assertSame(['example.com:8443'], $request->getHeader('Host'));
    }

    public function testChangingUriCanPreserveHostHeader(): void
    {
        $request = new Request();
        $request->withHeader('Host', 'original.example');
        $request->withUri(new Uri('https://new.example/path'), true);

        $this->assertSame(['original.example'], $request->getHeader('Host'));
    }
}
