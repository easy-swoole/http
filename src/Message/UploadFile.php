<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/5/24
 * Time: 下午3:20
 */

namespace EasySwoole\Http\Message;


use EasySwoole\Http\Exception\FileException;
use EasySwoole\Utility\File;
use Psr\Http\Message\UploadedFileInterface;

class UploadFile implements UploadedFileInterface
{
    private $tempName;
    private Stream|null $stream = null;
    private $size;
    private $error;
    private $clientFileName;
    private $clientMediaType;

    function __construct( $tempName,$size, $errorStatus, $clientFilename = null, $clientMediaType = null)
    {
        $this->tempName = $tempName;
        $this->error = $errorStatus;
        $this->size = $size;
        $this->clientFileName = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }

    public function getTempName()
    {
        return $this->tempName;
    }

    public function getStream():Stream
    {
        if ($this->stream === null) {
            $resource = fopen($this->tempName, 'rb');
            if ($resource === false) {
                throw new FileException('Unable to open uploaded file stream');
            }
            $this->stream = new Stream($resource);
        }
        return $this->stream;
    }

    public function moveTo($targetPath)
    {
        if (!(is_string($targetPath) && false === empty($targetPath))) {
            throw new FileException('Please provide a valid path');
        }

        if ($this->size < 0) {
            throw new FileException('Unable to retrieve stream');
        }

        $dir = dirname($targetPath);
        if (!File::createDirectory($dir)) {
            throw new FileException(sprintf('Directory "%s" was not created', $dir));
        }

        $stream = $this->getStream();
        $stream->rewind();

        $targetStream = fopen($targetPath, 'wb');
        if ($targetStream === false) {
            throw new FileException(sprintf('Unable to open target file %s', $targetPath));
        }

        try {
            $movedSize = stream_copy_to_stream($stream->getStreamResource(), $targetStream);
        } finally {
            fclose($targetStream);
        }

        if ($movedSize === false) {
            unlink($targetPath);
            throw new FileException(sprintf('Uploaded file could not be move to %s', $targetPath));
        }

        if ($movedSize !== $this->size) {
            unlink($targetPath);
            throw new FileException(sprintf('File upload specified path(%s) interrupted', $targetPath));
        }
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getClientFilename()
    {
        return $this->clientFileName;
    }

    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }

    public function __destruct()
    {
        $this->stream?->close();
    }
}
