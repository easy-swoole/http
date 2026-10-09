<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\Message\Status;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    #[DataProvider('statusProvider')]
    public function testReasonPhrases(int $status, string $phrase): void
    {
        $this->assertSame($phrase, Status::getReasonPhrase($status));
    }

    public static function statusProvider(): array
    {
        return [
            [Status::CODE_OK, 'OK'],
            [Status::CODE_MOVED_TEMPORARILY, 'Found'],
            [Status::CODE_NOT_FOUND, 'Not Found'],
            [Status::CODE_INTERNAL_SERVER_ERROR, 'Internal Server Error'],
        ];
    }

    public function testUnknownStatusHasNoReasonPhrase(): void
    {
        $this->assertNull(Status::getReasonPhrase(999));
    }
}
