<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\Message\Uri;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Http\Utility;
use PHPUnit\Framework\TestCase;

class UtilityTest extends TestCase
{
    public function testRequestCanBeSerialized(): void
    {
        $request = new Request();
        $request->withMethod('POST');
        $request->withUri(new Uri('http://example.com/path?a=1'));
        $request->withoutHeader('Host')->withHeader('host', 'example.com');
        $request->withHeader('Content-Type', 'text/plain');
        $request->getBody()->write('payload');

        $this->assertSame(
            "POST /path?a=1 HTTP/1.1\r\nhost: example.com\r\nContent-Type: text/plain\r\n\r\npayload",
            Utility::toString($request)
        );
    }

    public function testResponseCanBeSerialized(): void
    {
        $response = new Response();
        $response->withStatus(201);
        $response->withHeader('Content-Type', 'application/json');
        $response->write('{}');

        $this->assertSame(
            "HTTP/1.1 201 Created\r\nServer: EasySwoole\r\nContent-Type: application/json\r\n\r\n{}",
            Utility::toString($response)
        );
    }

    public function testHeaderItemCanBeSplitAndTrimmed(): void
    {
        $this->assertSame(['gzip', 'deflate', 'br'], Utility::headerItemToArray('gzip, deflate,br'));
    }
}
