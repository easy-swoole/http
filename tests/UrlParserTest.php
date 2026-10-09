<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\UrlParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UrlParserTest extends TestCase
{
    #[DataProvider('pathProvider')]
    public function testPathInfo(string $input, string $expected): void
    {
        $this->assertSame($expected, UrlParser::pathInfo($input));
    }

    public static function pathProvider(): array
    {
        return [
            'root index' => ['/index.php', '/'],
            'nested index' => ['/api/index.php', '/api'],
            'extension removed' => ['/api/user.html', '/api/user'],
            'plain path' => ['/api/user', '/api/user'],
        ];
    }

    public function testAppendQueryAddsAndOverridesArguments(): void
    {
        $url = UrlParser::appendQuery('https://example.com/path?a=old&keep=yes', [
            'a' => 'new',
            'page' => 2,
        ]);

        $this->assertSame('https://example.com/path?a=new&page=2&keep=yes', $url);
    }
}
