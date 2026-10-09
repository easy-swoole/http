<?php

namespace EasySwoole\Http\Tests;

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
}
