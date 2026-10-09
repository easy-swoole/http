<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\Message\Response;
use PHPUnit\Framework\TestCase;

class MessageResponseTest extends TestCase
{
    public function testCanUpdateReasonPhraseWithoutChangingStatusCode(): void
    {
        $response = new Response();

        $response->withStatus(200, 'Custom');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Custom', $response->getReasonPhrase());
    }

    public function testCanRestoreDefaultReasonPhraseWithoutChangingStatusCode(): void
    {
        $response = new Response();
        $response->withStatus(200, 'Custom');

        $response->withStatus(200);

        $this->assertSame('OK', $response->getReasonPhrase());
    }

    public function testChangingStatusUsesDefaultReasonPhrase(): void
    {
        $response = new Response();
        $response->withStatus(404);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getReasonPhrase());
    }

    public function testCookiesCanBeCollected(): void
    {
        $response = new Response();
        $cookie = ['session', 'value', 0, '/'];

        $this->assertSame($response, $response->withAddedCookie($cookie));
        $this->assertSame([$cookie], $response->getCookies());
    }
}
