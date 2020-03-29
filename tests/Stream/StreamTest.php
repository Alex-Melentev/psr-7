<?php

namespace Tests\HTTP\Headers;

use LoneCat\PSR7\Stream\Stream;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    public function testConstructor(): void
    {
        $stream = fopen('php://temp', 'r+');
        $stream_obj = new Stream($stream);
        self::assertEquals($stream, $stream_obj->detach());

    }

}