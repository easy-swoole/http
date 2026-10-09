<?php

namespace EasySwoole\Http\Tests;

use EasySwoole\Http\Exception\FileException;
use EasySwoole\Http\Message\UploadFile;
use PHPUnit\Framework\TestCase;

class UploadFileTest extends TestCase
{
    public function testMoveEmptyFile(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'upload-source-');
        $target = $source . '-target';

        try {
            $upload = new UploadFile(
                $source,
                0,
                UPLOAD_ERR_OK,
                'empty.txt',
                'text/plain'
            );

            $upload->moveTo($target);

            $this->assertFileExists($target);
            $this->assertSame(0, filesize($target));
        } finally {
            if (is_file($source)) {
                unlink($source);
            }
            if (is_file($target)) {
                unlink($target);
            }
        }
    }

    public function testMoveFilePreservesContents(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'upload-source-');
        $target = $source . '-target';
        file_put_contents($source, 'contents');

        try {
            $upload = new UploadFile($source, 8, UPLOAD_ERR_OK, 'file.txt', 'text/plain');
            $upload->moveTo($target);

            $this->assertSame('contents', file_get_contents($target));
            $this->assertSame(8, $upload->getSize());
            $this->assertSame(UPLOAD_ERR_OK, $upload->getError());
            $this->assertSame('file.txt', $upload->getClientFilename());
            $this->assertSame('text/plain', $upload->getClientMediaType());
        } finally {
            if (is_file($source)) {
                unlink($source);
            }
            if (is_file($target)) {
                unlink($target);
            }
        }
    }

    public function testMoveRejectsEmptyTargetPath(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'upload-source-');

        try {
            $upload = new UploadFile($source, 0, UPLOAD_ERR_OK);

            $this->expectException(FileException::class);
            $this->expectExceptionMessage('Please provide a valid path');
            $upload->moveTo('');
        } finally {
            if (is_file($source)) {
                unlink($source);
            }
        }
    }

    public function testMoveRejectsNegativeSize(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'upload-source-');

        try {
            $upload = new UploadFile($source, -1, UPLOAD_ERR_OK);

            $this->expectException(FileException::class);
            $this->expectExceptionMessage('Unable to retrieve stream');
            $upload->moveTo($source . '-target');
        } finally {
            if (is_file($source)) {
                unlink($source);
            }
        }
    }
}
