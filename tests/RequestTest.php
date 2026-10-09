<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
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
