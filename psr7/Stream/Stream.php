<?php

namespace Alex_Melentev\psr7\Stream;

use Exception;
use Psr\Http\Message\StreamInterface;

class Stream
    implements StreamInterface
{

    protected $stream;

    public function __construct($resource)
    {
        if (!is_resource($resource) || get_resource_type($resource) !== 'stream') {
            throw new Exception('Invalid stream provided; must be a string stream identifier or stream resource');
        }

        $this->stream = $resource;
    }

    public function __toString()
    {
        if (!$this->isReadable() || !$this->isSeekable())
            return '';

        $pos = $this->tell();
        $this->rewind();
        $result = stream_get_contents($this->stream);
        $this->seek($pos);
        return $result;
    }

    public function isReadable() {
        if (!$this->isOpen())
            return false;

        $mode = $this->getMetadata('mode');

        return (mb_strstr($mode, 'r') || mb_strstr($mode, '+'));
    }

    protected function isOpen() {
        return (bool) $this->stream;
    }

    public function getMetadata($key = null) {
        if (!$this->isOpen())
            throw new Exception('attempting to get metadata from closed stream');

        $metadata = stream_get_meta_data($this->stream);

        return is_null($key) ? $metadata : $metadata[$key] ?? null;
    }

    public function isSeekable() {
        return $this->isOpen() ? (bool) $this->getMetadata('seekable') : false;
    }

    public function tell() {
        if (!$this->isOpen())
            throw new Exception('No resource available; cannot tell position');

        $result = ftell($this->stream);
        if (!is_int($result))
            throw new Exception('Error occurred during tell operation');
        return $result;
    }

    public function rewind() {
        return $this->seek(0);
    }

    public function seek($offset, $whence = SEEK_SET) {
        if (!$this->isSeekable()) {
            throw new Exception('Stream is not seekable');
        }

        $result = fseek($this->stream, $offset, $whence);

        if (0 !== $result) {
            throw new Exception('Error seeking within stream');
        }

        return true;
    }

    public function close()
    {
        if (!$this->isOpen())
            return;
        $stream = $this->detach();
        fclose($stream);
    }

    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;

        return $stream;
    }

    public function getSize()
    {
        if (is_null($this->stream))
            return null;

        $stats = fstat($this->stream);

        return $stats !== false ? $stats['size'] : null;
    }

    public function eof() {
        return $this->isOpen() ? feof($this->stream) : true;
    }

    public function write($string) {
        if (!$this->isOpen())
            throw new Exception('No resource available; cannot write');

        if (!$this->isWritable())
            throw new Exception('Stream is not writable');

        $result = fwrite($this->stream, $string);

        if (false === $result)
            throw new Exception('Error writing to stream');

        return $result;
    }

    public function isWritable() {
        if (!$this->isOpen())
            return false;

        $mode = $this->getMetadata('mode');

        return (bool) preg_match('/[acwx\Q+\E]/u', $mode);
    }

    public function read($length) {
        if (!$this->isOpen())
            throw new Exception('No resource available; cannot read');

        if (!$this->isReadable())
            throw new Exception('Stream is not readable');

        $result = fread($this->stream, (int) $length);

        if ($result === false)
            throw new Exception('Error reading stream');

        return $result;
    }

    public function getContents() {
        if (!$this->isReadable())
            throw new Exception('attempting to read unreadable stream');

        $result = stream_get_contents($this->stream);
        if ($result === false)
            throw new Exception('Error reading from stream');

        return $result;
    }

}