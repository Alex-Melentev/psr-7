<?php

namespace Alex_Melentev\psr7\HTTP\Messages;

use Alex_Melentev\psr7\HTTP\Uri\URI;
use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Request
    extends Message
    implements RequestInterface
{

    protected const METHODS = ['GET',
                               'POST',];

    protected ?string $request_target = null;
    protected string $method;
    protected UriInterface $uri;

    public function __construct(string $method, $uri)
    {
        $this->setMethod($method);
        $this->uri = $this->importUri($uri);
    }

    public function getRequestTarget()
    {
        if (!is_null($this->request_target)) {
            return $this->request_target;
        }

        if (is_null($this->uri))
            return '/';

        $path = $this->uri->getPath() ?: '/';
        $query = $this->uri->getQuery();
        if ($query)
            $path .= '?' . $query;

        return $path;
    }

    public function withRequestTarget($requestTarget) {
        $new_request = clone $this;
        return $new_request->setRequestTarget($requestTarget);
    }

    public function getMethod() {
        return $this->method;
    }

    public function withMethod($method) {
        $new_request = clone $this;
        return $new_request->setMethod($method);
    }

    public function getUri() {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false) {
        $new_request = clone $this;
        return $new_request->setUri($uri, $preserveHost);
    }

    protected function setRequestTarget(?string $request_target) {
        if ((is_string($request_target)) && (preg_match('#\s#', $request_target)))
            throw new Exception('Invalid request target provided; cannot contain whitespace');

        $this->request_target = $request_target;
        return $this;
    }

    protected function setMethod(string $method) {
        if (!in_array($method, self::METHODS, true))
            throw new Exception('Unsupported HTTP method "' . $method . '" provided');

        $this->method = $method;
        return $this;
    }

    protected function setUri($uri, bool $preserve_host = false) {
        $uri = $this->importUri($uri);

        $this->uri = $uri;

        if ($preserve_host && $this->headers->hasHeader('Host'))
            return $this;

        $host = $uri->getHost();
        if (!$host)
            return $this;

        $port = $uri->getPort();
        if ($port)
            $host .= ':' . $port;

        $this->headers->setHeader('Host', $host);

        return $this;
    }

    protected function importUri($uri)
    {
        if ($uri instanceof UriInterface)
            return $uri;

        return new URI($uri)
            ;
    }

}