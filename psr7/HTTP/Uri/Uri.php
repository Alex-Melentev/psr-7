<?php

namespace Alex_Melentev\psr7\HTTP\Uri;

use Exception;
use Psr\Http\Message\UriInterface;

class URI
    implements UriInterface
{

    protected const DEFAULT_PORTS = ['http' => 80,
                                     'https' => 443,];

    protected ?string $scheme = null;
    protected ?string $user = null;
    protected ?string $password = null;
    protected ?string $host = null;
    protected ?int $port = null;
    protected ?string $path = null;
    protected ?string $query = null;
    protected ?string $fragment = null;

    public function __construct(string $uri = '')
    {
        $parsed_uri = parse_url($uri);

        if ($parsed_uri === false)
            throw new Exception('Invalid uri passed');

        if (!isset($parsed_uri['path']))
            $parsed_uri['path'] = '/';

        $this->setScheme($parsed_uri['scheme'] ?? null);

        $this->setUser($parsed_uri['user'] ?? null);
        $this->setPassword($parsed_uri['pass'] ?? null);

        $this->setHost($parsed_uri['host'] ?? null);
        $this->setPort($parsed_uri['port'] ?? null);
        $this->setPath($parsed_uri['path'] ?? null);
        $this->setQuery($parsed_uri['query'] ?? null);
        $this->setFragment($parsed_uri['fragment'] ?? null);
    }

    public function getScheme() {
        return $this->scheme ?? '';
    }

    public function getAuthority() {
        if (is_null($this->getHost()))
            return '';

        $user_info = $this->getUserInfo();
        if ($user_info)
            $user_info .= '@';

        $port = $this->getPort();
        $port = $port ? ':' . $port : '';

        return $user_info . $this->getHost() . $port;
    }

    public function getUserInfo() {
        if (is_null($this->user))
            return '';
        $result = $this->user;
        if (!is_null($this->password))
            $result .= ':' . $this->password;

        return $result;
    }

    public function getHost() {
        return $this->host ?? '';
    }

    public function getPort() {
        return $this->port;
    }

    public function getPath() {
        return $this->path ?? '';
    }

    public function getQuery() {
        return $this->query ?? '';
    }

    public function getFragment() {
        return $this->fragment ?? '';
    }

    public function withScheme($scheme) {
        $new_uri = clone $this;
        return $new_uri->setScheme($scheme);
    }

    public function withUserInfo($user, $password = null) {
        $new_uri = clone $this;
        return $new_uri->setUser($user)->setPassword($password);
    }

    public function withHost($host) {
        $new_uri = clone $this;
        return $new_uri->setHost($host);
    }

    public function withPort($port) {
        $new_uri = clone $this;
        return $new_uri->setPort($port);
    }

    public function withPath($path) {
        $new_uri = clone $this;
        return $new_uri->setPath($path);
    }

    public function withQuery($query) {
        $new_uri = clone $this;
        return $new_uri->setQuery($query);
    }

    public function withFragment($fragment) {
        $new_uri = clone $this;
        return $new_uri->setFragment($fragment);
    }

    public function __toString() {
        $result = '';

        $scheme = $this->getScheme();
        if ($scheme)
            $scheme .= ':';

        $authority = $this->getAuthority();
        if ($authority)
            $result = $scheme . '//' . $authority;

        $path = $this->getPath();
        if (mb_strpos($path, '/') === 0) {
            $result .= $path;
        }
        else
            $result = $path;

        $query = $this->getQuery();
        if ($query)
            $result .= '?' . $query;

        $fragment = $this->getFragment();
        if ($fragment)
            $result .= '#' . $fragment;

        return $result;
    }


    // Processors

    protected function setScheme(?string $scheme) {
        if (!$scheme)
            $scheme = null;

        if (is_string($scheme)) {
            $scheme = mb_strtolower($scheme);
            if (!array_key_exists($scheme, self::DEFAULT_PORTS))
                throw new Exception('invalid scheme');
        }

        $this->scheme = $scheme;

        return $this;
    }

    protected function setUser(?string $user) {
        if (!$user)
            $user = null;

        if (is_string($user)) {
            $valid_symbols = '[a-z0-9_-]{1,}';
            $valid_pattern = '/^' . $valid_symbols . '$/ui';
            if (!preg_match($valid_pattern, $user))
                throw new Exception('user contains invalid symbols');
        }

        $this->user = $user;

        return $this;
    }

    protected function setPassword(?string $password) {
        if (!$password)
            $password = null;

        if (is_string($password)) {
            $valid_symbols = '[a-z0-9_-]{1,}';
            $valid_pattern = '/^' . $valid_symbols . '$/ui';
            if (!preg_match($valid_pattern, $password))
                throw new Exception('password contains invalid symbols');
        }

        $this->password = $password;

        return $this;
    }

    protected function setHost(?string $host) {
        if (!$host)
            $host = null;

        if (is_string($host)) {
            $host = trim(mb_strtolower($host), '/');
            $valid_symbols = '[a-z0-9]{1}(?:[a-z0-9_-]{0,}[a-z0-9]{1})?';
            $valid_pattern = '/^' . $valid_symbols . '+(?:\.' . $valid_symbols . '){0,}$/u';
            if (!preg_match($valid_pattern, $host))
                throw new Exception('host contains invalid symbols');
        }

        $this->host = $host;

        return $this;
    }

    protected function setPort(?int $port) {
        if (!$port)
            $port = null;

        if (is_int($port)) {
            if ($port < 1 || $port > 65535)
                throw new Exception('invalid port number');
            if (isset($this->scheme) && isset(self::DEFAULT_PORTS[$this->scheme]) && self::DEFAULT_PORTS[$this->scheme] === $port)
                $port = null;
        }

        $this->port = $port;

        return $this;
    }

    protected function setPath(?string $path) {
        if (!$path)
            $path = null;

        if (is_string($path)) {
            $absolute = (mb_strpos($path, '/') === 0);
            $path = $this->normalizePathBySegments($path);
            if ($absolute)
                $path = '/' . $path;
        }

        $this->path = $path;

        return $this;
    }

    protected function setQuery(?string $query) {
        if (!$query)
            $query = null;

        $this->query = $query;

        return $this;
    }

    protected function setFragment(?string $fragment) {
        if (!$fragment)
            $fragment = null;

        $this->fragment = $fragment;

        return $fragment;
    }

    protected function normalizePathBySegments(?string $path) {
        $segments = explode('/', $path);
        $result_segments = [];
        foreach ($segments as $segment) {
            if (!$segment)
                continue;
            if ($segment === '..' && count($result_segments) > 0)
                array_pop($result_segments);
            elseif (preg_match('/^[.]{1,}$/u', $segment))
                continue;
            else $result_segments[] = $segment;
        }

        return implode('/', $result_segments);
    }

}