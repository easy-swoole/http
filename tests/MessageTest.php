<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\Message\Message;
use EasySwoole\Http\Message\Stream;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testProtocolVersionCanBeChanged(): void
    {
        $message = new Message();

        $this->assertSame('1.1', $message->getProtocolVersion());
        $this->assertSame($message, $message->withProtocolVersion('2'));
        $this->assertSame('2', $message->getProtocolVersion());
    }

    public function testHeadersCanBeAddedReplacedAndRemoved(): void
    {
        $message = new Message();
        $message->withHeader('Accept', 'text/plain');
        $message->withAddedHeader('Accept', ['application/json', 'text/html']);

        $this->assertTrue($message->hasHeader('Accept'));
        $this->assertSame(
            ['text/plain', 'application/json', 'text/html'],
            $message->getHeader('Accept')
        );
        $this->assertSame('text/plain; application/json; text/html', $message->getHeaderLine('Accept'));

        $message->withHeader('Accept', 'application/xml');
        $this->assertSame(['application/xml'], $message->getHeader('Accept'));

        $message->withoutHeader('Accept');
        $this->assertFalse($message->hasHeader('Accept'));
        $this->assertSame([], $message->getHeader('Accept'));
        $this->assertSame('', $message->getHeaderLine('Accept'));
    }

    public function testBodyIsCreatedLazilyAndCanBeReplaced(): void
    {
        $message = new Message();
        $body = $message->getBody();

        $this->assertSame($body, $message->getBody());
        $this->assertSame('', (string) $body);

        $replacement = new Stream('payload');
        $this->assertSame($message, $message->withBody($replacement));
        $this->assertSame($replacement, $message->getBody());
    }
}
