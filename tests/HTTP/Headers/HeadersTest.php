<?php

namespace Tests\HTTP\Headers;

use LoneCat\PSR7\HTTP\Headers\HeadersList;
use PHPUnit\Framework\TestCase;

class HeadersTest extends TestCase
{
    public function testConstructor(): void
    {
        $headers_list = [];
        $headers_obj = new HeadersList($headers_list);
        self::assertEquals($headers_list, $headers_obj->getHeaders());

        $headers_list = [
            'Header-1' => [
                'value-1',
                'value-2',
            ],
            'Header-2' => [
                'value-3',
                'value-4',
            ],
        ];
        $headers_obj = new HeadersList($headers_list);
        self::assertEquals($headers_list, $headers_obj->getHeaders());
    }

}