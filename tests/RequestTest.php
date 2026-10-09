<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\Request;
use EasySwoole\Http\Message\UploadFile;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->temporaryFiles = [];
    }

    public function testHeadersFromSwooleRequestAreNormalizedToLowercase(): void
    {
        $swooleRequest = new FakeSwooleRequest();
        $swooleRequest->header = [
            'Host' => 'example.com',
            'X-Trace-ID' => 'trace-123',
            'ACCEPT' => 'application/json',
        ];

        $request = new Request($swooleRequest);

        $this->assertSame([
            'host' => ['example.com'],
            'x-trace-id' => ['trace-123'],
            'accept' => ['application/json'],
        ], $request->getHeaders());
        $this->assertSame(['example.com'], $request->getHeader('host'));
        $this->assertSame('trace-123', $request->getHeaderLine('x-trace-id'));
        $this->assertSame(['application/json'], $request->getHeader('accept'));
    }

    public function testHeadersWithDifferentCasingAreMergedAfterNormalization(): void
    {
        $swooleRequest = new FakeSwooleRequest();
        $swooleRequest->header = [
            'X-Test' => 'first',
            'x-test' => 'second',
        ];

        $request = new Request($swooleRequest);

        $this->assertSame(['first', 'second'], $request->getHeader('x-test'));
        $this->assertCount(1, $request->getHeaders());
    }

    public function testMissingSwooleHeadersProduceEmptyHeaderCollection(): void
    {
        $swooleRequest = new FakeSwooleRequest();
        $swooleRequest->header = null;

        $request = new Request($swooleRequest);

        $this->assertSame([], $request->getHeaders());
        $this->assertSame([], $request->getHeader('missing'));
        $this->assertSame('', $request->getHeaderLine('missing'));
    }

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

    public function testSingleUploadedFileIsNormalized(): void
    {
        $swooleRequest = new FakeSwooleRequest();
        $swooleRequest->files = [
            'avatar' => $this->uploadFixture('avatar-content', 'avatar.txt', 'text/plain'),
        ];

        $request = new Request($swooleRequest);
        $upload = $request->getUploadedFile('avatar');

        $this->assertInstanceOf(UploadFile::class, $upload);
        $this->assertSame(strlen('avatar-content'), $upload->getSize());
        $this->assertSame(UPLOAD_ERR_OK, $upload->getError());
        $this->assertSame('avatar.txt', $upload->getClientFilename());
        $this->assertSame('text/plain', $upload->getClientMediaType());
        $this->assertSame('avatar-content', (string) $upload->getStream());
    }

    public function testMultipleUploadedFilesAreNormalized(): void
    {
        $swooleRequest = new FakeSwooleRequest();
        $swooleRequest->files = [
            'documents' => [
                $this->uploadFixture('first', 'first.txt', 'text/plain'),
                $this->uploadFixture('second', 'second.json', 'application/json'),
            ],
        ];

        $request = new Request($swooleRequest);
        $uploads = $request->getUploadedFile('documents');

        $this->assertCount(2, $uploads);
        $this->assertContainsOnlyInstancesOf(UploadFile::class, $uploads);
        $this->assertSame('first.txt', $uploads[0]->getClientFilename());
        $this->assertSame('second.json', $uploads[1]->getClientFilename());
    }

    public function testSeparateFileFieldsAreNormalizedIndependently(): void
    {
        $swooleRequest = new FakeSwooleRequest();
        $swooleRequest->files = [
            'file1' => $this->uploadFixture('file-one', 'file1.txt', 'text/plain'),
            'file2' => $this->uploadFixture('file-two', 'file2.json', 'application/json'),
        ];

        $request = new Request($swooleRequest);
        $file1 = $request->getUploadedFile('file1');
        $file2 = $request->getUploadedFile('file2');

        $this->assertInstanceOf(UploadFile::class, $file1);
        $this->assertInstanceOf(UploadFile::class, $file2);
        $this->assertSame('file1.txt', $file1->getClientFilename());
        $this->assertSame('file2.json', $file2->getClientFilename());
        $this->assertSame('text/plain', $file1->getClientMediaType());
        $this->assertSame('application/json', $file2->getClientMediaType());
        $this->assertSame('file-one', (string) $file1->getStream());
        $this->assertSame('file-two', (string) $file2->getStream());
        $this->assertSame(['file1', 'file2'], array_keys($request->getUploadedFiles()));
    }

    public function testEmptyTemporaryNamesAreSkipped(): void
    {
        $swooleRequest = new FakeSwooleRequest();
        $swooleRequest->files = [
            'single' => [
                'tmp_name' => '',
                'size' => 0,
                'error' => UPLOAD_ERR_NO_FILE,
                'name' => '',
                'type' => '',
            ],
            'documents' => [
                [
                    'tmp_name' => '',
                    'size' => 0,
                    'error' => UPLOAD_ERR_NO_FILE,
                    'name' => '',
                    'type' => '',
                ],
                $this->uploadFixture('valid', 'valid.txt', 'text/plain'),
            ],
        ];

        $request = new Request($swooleRequest);

        $this->assertNull($request->getUploadedFile('single'));
        $this->assertCount(1, $request->getUploadedFile('documents'));
        $this->assertSame('valid.txt', $request->getUploadedFile('documents')[0]->getClientFilename());
    }

    public function testIgnoreFileSkipsAllUploadedFiles(): void
    {
        $swooleRequest = new FakeSwooleRequest();
        $swooleRequest->ignoreFile = true;
        $swooleRequest->files = [
            'avatar' => $this->uploadFixture('content', 'avatar.txt', 'text/plain'),
        ];

        $request = new Request($swooleRequest);

        $this->assertSame([], $request->getUploadedFiles());
    }

    public function testUploadErrorStatusIsPreservedWhenTemporaryFileExists(): void
    {
        $swooleRequest = new FakeSwooleRequest();
        $file = $this->uploadFixture('partial', 'partial.txt', 'text/plain');
        $file['error'] = UPLOAD_ERR_PARTIAL;
        $swooleRequest->files = ['document' => $file];

        $request = new Request($swooleRequest);

        $this->assertSame(UPLOAD_ERR_PARTIAL, $request->getUploadedFile('document')->getError());
    }

    public function testMissingFilesCollectionProducesNoUploads(): void
    {
        $swooleRequest = new FakeSwooleRequest();
        $swooleRequest->files = null;

        $request = new Request($swooleRequest);

        $this->assertSame([], $request->getUploadedFiles());
        $this->assertNull($request->getUploadedFile('missing'));
    }

    private function uploadFixture(string $contents, string $name, string $type): array
    {
        $path = tempnam(sys_get_temp_dir(), 'request-upload-');
        file_put_contents($path, $contents);
        $this->temporaryFiles[] = $path;

        return [
            'tmp_name' => $path,
            'size' => strlen($contents),
            'error' => UPLOAD_ERR_OK,
            'name' => $name,
            'type' => $type,
        ];
    }
}

class FakeSwooleRequest extends \Swoole\Http\Request
{
    public bool $ignoreFile = false;

    public function __construct()
    {
        $this->fd = 0;
        $this->server = [
            'server_protocol' => 'HTTP/1.1',
            'path_info' => '/',
            'request_method' => 'GET',
            'server_port' => 80,
        ];
    }

    public function rawContent(): string|false
    {
        return '';
    }
}
