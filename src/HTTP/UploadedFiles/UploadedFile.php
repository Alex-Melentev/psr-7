<?php

namespace LoneCat\PSR7\HTTP\UploadedFiles;

use LoneCat\PSR7\Stream\Stream;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class UploadedFile
    implements UploadedFileInterface
{

    protected const ERRORS = [UPLOAD_ERR_OK => 'There is no error, the file uploaded with success',
                              UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                              UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                              UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                              UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                              UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                              UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                              UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',];

    protected ?string $filename = null;
    protected int $size;
    protected int $error;
    protected ?string $client_filename = null;
    protected ?string $client_media_type = null;
    protected ?StreamInterface $stream = null;
    protected bool $moved = false;

    public function __construct($streamOrFile, int $size, int $errorStatus, ?string $client_filename = null,
                                ?string $client_media_type = null) {
        if ($errorStatus === UPLOAD_ERR_OK) {
            if (is_string($streamOrFile))
                $this->filename = $streamOrFile;

            elseif (is_resource($streamOrFile) && get_resource_type($streamOrFile) === 'stream') {
                $this->stream = new Stream($streamOrFile)
                ;
            }

            elseif ($streamOrFile instanceof StreamInterface)
                $this->stream = $streamOrFile;

            else
                throw new InvalidArgumentException('Invalid stream or file provided for UploadedFile');
        }

        $this->setSize($size);
        $this->setErrorStatus($errorStatus);

        $this->client_filename = $client_filename;

        $this->client_media_type = $client_media_type;
    }

    public function getStream()
    {
        $this->checkError();

        if ($this->moved)
            throw new RuntimeException('Cannot retrieve stream after it has already been moved');

        if (!is_null($this->stream))
            return $this->stream;

        $stream = fopen('file://' . $this->filename, 'rb');
        $this->stream = new Stream($stream);

        return $this->stream;
    }

    public function moveTo($targetPath) {
        if ($this->moved)
            throw new RuntimeException('Cannot move file; already moved!');

        $this->checkError();

        if (!is_string($targetPath) || empty($targetPath))
            throw new InvalidArgumentException('Invalid path provided for move operation; must be a non-empty string');

        $this->checkDirectory($targetPath);

        $is_sapi = !(empty(PHP_SAPI) || mb_strpos(PHP_SAPI, 'cli') === 0);

        if ($is_sapi && $this->filename) {
            if (move_uploaded_file($this->filename, $targetPath) === false)
                throw new RuntimeException('Error occurred while moving uploaded file');
        }
        else {
            $resource = fopen($targetPath, 'wb+');
            if ($resource === false)
                throw new RuntimeException('Unable to write to designated path');

            $stream = $this->getStream();
            $stream->rewind();
            while (!$stream->eof()) {
                fwrite($resource, $stream->read(4096));
            }
            fclose($resource);
        }

        $this->moved = true;
    }

    public function getSize() {
        return $this->size;
    }

    public function getError() {
        return $this->error;
    }

    public function getClientFilename() {
        return $this->client_filename;
    }

    public function getClientMediaType() {
        return $this->client_media_type;
    }

    protected function setSize(int $size) {
        if ($size < 0)
            throw new Exception('File has no size');
        $this->size = $size;

        return $this;
    }

    protected function setErrorStatus(int $error) {
        if ($error < 0 || $error > 8)
            throw new InvalidArgumentException('Invalid error status for UploadedFile; must be an UPLOAD_ERR_* constant');
        $this->error = $error;

        return $this;
    }

    protected function checkError() {
        if ($this->error !== UPLOAD_ERR_OK)
            throw new RuntimeException('Cannot retrieve stream due to upload error: ' . self::ERRORS[$this->error]);
    }

    protected function checkDirectory(string $path) {
        $directory = dirname($path);
        if (!is_dir($directory) || !is_writable($directory))
            throw new RuntimeException('The target directory `' . $directory . '` does not exists or is not writable');
    }
}