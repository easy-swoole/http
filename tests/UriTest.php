<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\Message\Uri;
use PHPUnit\Framework\TestCase;

class UriTest extends TestCase
{
    public function testParsesAndRebuildsCompleteUri(): void
    {
        $uri = new Uri('https://user:pass@example.com:8443/a/b?x=1#section');

        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(8443, $uri->getPort());
        $this->assertSame('/a/b', $uri->getPath());
        $this->assertSame('x=1', $uri->getQuery());
        $this->assertSame('section', $uri->getFragment());
        $this->assertSame('user:pass@example.com:8443', $uri->getAuthority());
        $this->assertSame('https://user:pass@example.com:8443/a/b?x=1#section', (string) $uri);
    }

    public function testComponentsCanBeBuiltIndividually(): void
    {
        $uri = new Uri();
        $uri->withScheme('HTTP')
            ->withUserInfo('user', 'secret')
            ->withHost('EXAMPLE.COM')
            ->withPort(8080)
            ->withPath('/resource')
            ->withQuery('page=2')
            ->withFragment('top');

        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('HTTP://user:secret@example.com:8080/resource?page=2#top', (string) $uri);
    }

    public function testRelativeUriUsesExpectedDefaults(): void
    {
        $uri = new Uri('/path/to/resource?enabled=1');

        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('127.0.0.1', $uri->getHost());
        $this->assertSame('/path/to/resource', $uri->getPath());
        $this->assertSame('http://127.0.0.1/path/to/resource?enabled=1', (string) $uri);
    }
}
