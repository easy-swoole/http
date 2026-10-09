<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\Message\ServerRequest;
use PHPUnit\Framework\TestCase;

class ServerRequestTest extends TestCase
{
    public function testRequestCollectionsCanBeSetAndRead(): void
    {
        $request = new ServerRequest(serverParams: ['REMOTE_ADDR' => '127.0.0.1']);
        $request->withCookieParams(['session' => 'abc'])
            ->withQueryParams(['page' => 2])
            ->withParsedBody(['name' => 'test'])
            ->withUploadedFiles(['avatar' => 'file'])
            ->withAttribute('request-id', 42);

        $this->assertSame(['REMOTE_ADDR' => '127.0.0.1'], $request->getServerParams());
        $this->assertSame('abc', $request->getCookieParams('session'));
        $this->assertSame(2, $request->getQueryParam('page'));
        $this->assertSame('test', $request->getParsedBody('name'));
        $this->assertSame('file', $request->getUploadedFile('avatar'));
        $this->assertSame(42, $request->getAttribute('request-id'));
    }

    public function testAddMethodsPreferNewValues(): void
    {
        $request = new ServerRequest();
        $request->withCookieParams(['same' => 'old', 'old' => true]);
        $request->withQueryParams(['same' => 'old', 'old' => true]);
        $request->withParsedBody(['same' => 'old', 'old' => true]);
        $request->withAttribute('same', 'old');

        $request->addCookieParams(['same' => 'new', 'new' => true]);
        $request->addQueryParams(['same' => 'new', 'new' => true]);
        $request->addParsedBody(['same' => 'new', 'new' => true]);
        $request->addAttributes(['same' => 'new', 'new' => true]);

        $this->assertSame(['same' => 'new', 'new' => true, 'old' => true], $request->getCookieParams());
        $this->assertSame(['same' => 'new', 'new' => true, 'old' => true], $request->getQueryParams());
        $this->assertSame(['same' => 'new', 'new' => true, 'old' => true], $request->getParsedBody());
        $this->assertSame(['same' => 'new', 'new' => true], $request->getAttributes());
    }

    public function testMissingValuesReturnDefaults(): void
    {
        $request = new ServerRequest();

        $this->assertNull($request->getCookieParams('missing'));
        $this->assertNull($request->getQueryParam('missing'));
        $this->assertNull($request->getParsedBody('missing'));
        $this->assertNull($request->getUploadedFile('missing'));
        $this->assertSame('fallback', $request->getAttribute('missing', 'fallback'));
    }

    public function testAttributeCanBeRemoved(): void
    {
        $request = new ServerRequest();
        $request->withAttribute('key', 'value')->withoutAttribute('key');

        $this->assertSame([], $request->getAttributes());
    }
}
