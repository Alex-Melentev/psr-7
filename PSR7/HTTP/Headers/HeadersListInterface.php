<?php

namespace LoneCat\PSR7\HTTP\Headers;

interface HeadersListInterface
{

    public function __construct(array $headers);

    public function hasHeader(string $header_name);

    public function getHeaders();

    public function getHeader(string $header_name);

    public function getHeaderLine(string $header_name, ?string $separator = null);

    public function setHeader(string $header_name, $header_value);

    public function addHeaderValue(string $header_name, $header_value);

    public function removeHeader(string $header_name);

}