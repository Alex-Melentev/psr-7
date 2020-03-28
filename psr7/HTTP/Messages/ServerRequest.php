<?php

namespace Alex_Melentev\psr7\HTTP\Messages;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class ServerRequest
    extends Request
    implements ServerRequestInterface
{

    protected array $server_params = [];
    protected array $cookies = [];
    protected array $query_params = [];
    protected array $uploaded_files = [];
    protected $parsed_body = null;
    protected array $attributes = [];

    public function __construct(string $method, $uri, array $server_params)
    {
        parent::__construct($method, $uri);
        $this->server_params = $server_params;
    }

    public function getServerParams()
    {
        return $this->server_params;
    }

    public function getCookieParams()
    {
        return $this->cookies;
    }

    public function withCookieParams(array $cookies)
    {
        $new_server_request = clone $this;
        return $new_server_request->setCookieParams($cookies);
    }

    public function getQueryParams()
    {
        return $this->query_params;
    }

    public function withQueryParams(array $query)
    {
        $new_server_request = clone $this;
        return $new_server_request->setQueryParams($query);
    }

    public function getUploadedFiles()
    {
        return $this->uploaded_files;
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        $new_server_request = clone $this;
        return $new_server_request->setUploadedFiles($uploadedFiles);
    }

    public function getParsedBody()
    {
        return $this->parsed_body;
    }

    public function withParsedBody($data)
    {
        $new_server_request = clone $this;
        return $new_server_request->setParsedBody($data);
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null)
    {
        $name = $this->processArrayKey($name);

        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    public function withAttribute($name, $value)
    {
        $new_server_request = clone $this;
        return $new_server_request->setAttribute($this->processArrayKey($name), $value);
    }

    public function withoutAttribute($name)
    {
        $new_server_request = clone $this;
        return $new_server_request->removeAttribute($name);
    }

    protected function setCookieParams(array $cookies)
    {
        $this->cookies = $cookies;
        return $this;
    }

    protected function setQueryParams(array $query_params)
    {
        $this->query_params = $query_params;
        return $this;
    }

    protected function setUploadedFiles(array $uploaded_files)
    {
        $this->checkUploadedFiles($uploaded_files);
        $this->uploaded_files = $uploaded_files;
        return $this;
    }

    protected function setParsedBody($parsed_body)
    {
        if (!is_array($parsed_body) && !is_object($parsed_body) && !is_null($parsed_body))
            throw new Exception('invalid parsed body!');

        $this->parsed_body = $parsed_body;
        return $this;
    }

    protected function setAttribute($name, $value)
    {
        $this->attributes[$this->processArrayKey($name)] = $value;

        return $this;
    }

    protected function removeAttribute($name)
    {
        $name = $this->processArrayKey($name);
        unset($this->attributes[$name]);

        return $this;
    }

    protected function processArrayKey($name)
    {
        if (!is_string($name) && !is_int($name))
            throw new Exception('invalid array key value!');

        return mb_strtolower($name);
    }

    private function checkUploadedFiles(array $uploaded_files)
    {
        foreach ($uploaded_files as $file) {
            if (is_array($file)) {
                $this->checkUploadedFiles($file);
                continue;
            }

            if (!($file instanceof UploadedFileInterface)) {
                throw new Exception('Invalid leaf in uploaded files structure');
            }
        }

        return $uploaded_files;
    }

}