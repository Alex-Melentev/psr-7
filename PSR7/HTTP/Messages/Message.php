<?php

namespace LoneCat\PSR7\HTTP\Messages;

use LoneCat\PSR7\HTTP\Headers\HeadersList;
use Exception;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

abstract class Message
    implements MessageInterface
{

    public const PROTOCOL_VERSIONS = ['2',
                                      '1.1',
                                      '1.0',];

    protected string $protocol_version = self::PROTOCOL_VERSIONS[0];
    protected ?HeadersList $headers = null;
    protected ?StreamInterface $body = null;

    public function getProtocolVersion()
    {
        return $this->protocol_version;
    }

    public function withProtocolVersion($version)
    {
        $new_message = clone $this;
        return $new_message->setProtocolVersion($version);
    }

    public function getHeaders()
    {
        $this->initiateHeaders();

        return $this->headers->getHeaders();
    }

    public function hasHeader($name)
    {
        $this->initiateHeaders();

        return $this->headers->hasHeader($name);
    }

    public function getHeader($name)
    {
        $this->initiateHeaders();

        return $this->headers->getHeader($name);
    }

    public function getHeaderLine($name, ?string $separator = null)
    {
        $this->initiateHeaders();

        return $this->headers->getHeaderLine($name, $separator);
    }

    public function withHeader($name, $value)
    {
        $this->initiateHeaders();

        $new_message = clone $this;
        $new_message->headers->setHeader($name, $value);
        return $new_message;
    }

    public function withAddedHeader($name, $value)
    {
        $this->initiateHeaders();

        $new_message = clone $this;
        $new_message->headers->addHeaderValue($name, $value);
        return $new_message;
    }

    public function withoutHeader($name)
    {
        $this->initiateHeaders();

        $new_message = clone $this;
        $new_message->headers->removeHeader($name);
        return $new_message;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body)
    {
        $new_message = clone $this;
        return $new_message->setBody($body);
    }

    // not inclided into specification:

    protected function initiateHeaders()
    {
        if (!($this->headers instanceof HeadersList))
            $this->headers = new HeadersList();
            ;
    }

    protected function setProtocolVersion(string $version)
    {
        $this->protocol_version = $this->processProtocolVersion($version);
        return $this;
    }

    protected function processProtocolVersion(string $version)
    {
        if (!in_array($version, self::PROTOCOL_VERSIONS, true))
            throw new Exception('invalid protocol version');

        return $version;
    }

    protected function setBody(?StreamInterface $body)
    {
        $this->body = $body;
        return $this;
    }

    public function __clone() {
        foreach (get_object_vars($this) as $var_name => $var_value) {
            if (is_object($var_value)) {
                $this->$var_name = clone $var_value;
            }
        }
    }

}