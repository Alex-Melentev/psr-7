<?php

namespace Alex_Melentev\psr7\HTTP\Headers;

use Exception;

class HeadersList
    implements HeadersListInterface
{

    public const SEPARATOR = ';';

    protected array $headers = [];

    public function __construct(array $headers = [])
    {
        foreach ($headers as $header_name => $header_value) {
            $this->addHeaderValue($header_name, $header_value);
        }
    }

    public function addHeaderValue(string $header_name, $header_value) {
        $this->checkHeader($header_name);
        $real_header_name = $this->getHeaderRealName($header_name);
        if (is_null($real_header_name))
            $this->setHeader($header_name, $header_value);
        else
            $this->setHeader($header_name, array_merge($this->headers[$real_header_name], $this->processHeaderValue($header_value)));
    }

    protected function checkHeader(string $name) {
        if (!$name)
            throw new Exception('empty header name' . $name);
    }

    protected function getHeaderRealName(string $header_name) {
        $this->checkHeader($header_name);

        $headers_list = $this->filterHeaderValuesByKey($header_name);
        if (count($headers_list) > 1)
            throw new Exception('ambigous header "' . $header_name . '"');

        return array_keys($headers_list)[0] ?? null;
    }

    protected function filterHeaderValuesByKey($key) {
        $lower_key = mb_strtolower($key);

        return array_filter($this->headers, function($arr_key) use ($key, $lower_key) {
            return $arr_key === $key || mb_strtolower($arr_key) === $lower_key;
        }, ARRAY_FILTER_USE_KEY);
    }

    public function setHeader(string $header_name, $header_value) {
        $this->removeHeader($header_name);
        $this->headers[$header_name] = $this->processHeaderValue($header_value);
    }

    public function removeHeader(string $header_name)
    {
        $this->checkHeader($header_name);
        $real_header_name = $this->getHeaderRealName($header_name);
        if (!is_null($real_header_name))
            unset($this->headers[$real_header_name]);

        return $this;
    }

    protected function processHeaderValue($value)
    {
        if (!is_array($value))
            $value = [$value];

        foreach ($value as $key => $subvalue) {
            if (!is_scalar($subvalue))
                throw new Exception('invalid header value' . $value);
            $value[$key] = (string) $subvalue;
        }

        return $value;
    }

    public function hasHeader(string $header_name)
    {
        return !is_null($this->getHeaderRealName($header_name));
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getHeaderLine(string $header_name, ?string $separator = null)
    {
        return implode($separator ?? self::SEPARATOR, $this->getHeader($header_name) ?? []);
    }

    public function getHeader(string $header_name)
    {
        $real_header_name = $this->getHeaderRealName($header_name);
        if (is_null($real_header_name))
            return null;

        return $this->headers[$real_header_name];
    }

}