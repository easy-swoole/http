<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\Response;
use PHPUnit\Framework\TestCase;

class HttpResponseTest extends TestCase
{
    public function testResponseIsSubmittedOnlyOnce(): void
    {
        $swooleResponse = new FakeSwooleResponse();
        $response = new Response($swooleResponse);

        $this->assertTrue($response->__response());
        $this->assertFalse($response->__response());
        $this->assertSame(1, $swooleResponse->endCalls);
    }

    public function testBufferedResponseIsTransferredToSwooleResponse(): void
    {
        $swooleResponse = new FakeSwooleResponse();
        $response = new Response($swooleResponse);
        $response->withStatus(201)->withHeader('X-Test', 'yes');
        $response->setCookie('session', 'abc');
        $response->write('body');

        $this->assertTrue($response->__response());
        $this->assertSame(201, $swooleResponse->statusCode);
        $this->assertContains(['X-Test', 'yes'], $swooleResponse->headers);
        $this->assertContains(['Server', 'EasySwoole'], $swooleResponse->headers);
        $this->assertSame('session', $swooleResponse->cookies[0][0]);
        $this->assertSame('abc', $swooleResponse->cookies[0][1]);
        $this->assertSame(['body'], $swooleResponse->endedContents);
    }

    public function testLogicalEndPreventsFurtherWritesButCanStillBeSubmitted(): void
    {
        $swooleResponse = new FakeSwooleResponse();
        $response = new Response($swooleResponse);
        $response->write('before');
        $response->end();

        $this->assertSame(Response::STATUS_LOGICAL_END, $response->isEndResponse());
        $this->assertFalse($response->write('after'));
        $this->assertTrue($response->__response());
        $this->assertSame(['before'], $swooleResponse->endedContents);
    }

    public function testChunkWritesAreSentImmediately(): void
    {
        $swooleResponse = new FakeSwooleResponse();
        $response = new Response($swooleResponse);
        $response->setIsChunk(true);

        $this->assertTrue($response->write('chunk'));
        $this->assertSame(['chunk'], $swooleResponse->writtenContents);
        $this->assertSame('', (string) $response->getBody());
    }

    public function testSendFileUsesSwooleSendFileInsteadOfEnd(): void
    {
        $swooleResponse = new FakeSwooleResponse();
        $response = new Response($swooleResponse);
        $response->sendFile('/tmp/download.txt');

        $this->assertTrue($response->__response());
        $this->assertSame(['/tmp/download.txt'], $swooleResponse->sentFiles);
        $this->assertSame(0, $swooleResponse->endCalls);
    }

    public function testRedirectSetsStatusAndLocation(): void
    {
        $response = new Response();

        $this->assertTrue($response->redirect('/next', 301));
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame(['/next'], $response->getHeader('Location'));
    }
}

class FakeSwooleResponse extends \Swoole\Http\Response
{
    public int $statusCode = 0;
    public array $headers = [];
    public array $cookies = [];
    public array $endedContents = [];
    public array $writtenContents = [];
    public array $sentFiles = [];
    public int $endCalls = 0;

    public function __construct()
    {
    }

    public function status(int $http_code, string $reason = ''): bool
    {
        $this->statusCode = $http_code;
        return true;
    }

    public function header(string $key, array|string $value, bool $format = true): bool
    {
        $this->headers[] = [$key, $value];
        return true;
    }

    public function cookie(
        \Swoole\Http\Cookie|string $name_or_object,
        string $value = '',
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = false,
        string $samesite = '',
        string $priority = '',
        bool $partitioned = false
    ): bool {
        $this->cookies[] = func_get_args();
        return true;
    }

    public function end(?string $content = null): bool
    {
        $this->endCalls++;
        $this->endedContents[] = $content;
        return true;
    }

    public function write(string $content): bool
    {
        $this->writtenContents[] = $content;
        return true;
    }

    public function sendfile(string $filename, int $offset = 0, int $length = 0): bool
    {
        $this->sentFiles[] = $filename;
        return true;
    }
}
