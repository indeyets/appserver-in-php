<?php
namespace MFS\AppServer\HTTP;

class Request
{
    protected $headers = null;
    protected $cookies = null;
    protected $get = null;
    protected $body = null;

    protected function __construct(array $headers, $body = null)
    {
        $this->headers = $headers;
        $this->body = $body;

        $this->headers['REQUEST_TIME'] = time();

        ksort($this->headers);
    }

    public function __get($property)
    {
        if ($property == 'headers') {
            return $this->headers;
        } elseif ($property == 'get') {
            if ($this->get === null and isset($this->headers['QUERY_STRING'])) {
                parse_str($this->headers['QUERY_STRING'], $this->get);
            }

            return $this->get;
        } elseif ('cookies' == $property) {
            if (null === $this->cookies) {
                $this->cookies = array();

                if (isset($this->headers['HTTP_COOKIE'])) {
                    $pairs = explode('; ', $this->headers['HTTP_COOKIE']);

                    foreach ($pairs as $pair) {
                        list($name, $value) = explode('=', $pair);
                        $this->cookies[$name] = urldecode($value);
                    }
                }
            }

            return $this->cookies;
        }

        throw new UnexpectedValueException();
    }

    public static function factory(array $headers, $body = null)
    {
        if (!isset($headers['REQUEST_METHOD'])) {
            throw new BadProtocolException("Don't know how to handle this request");
        }

        switch ($headers['REQUEST_METHOD']) {
            case 'GET':
                return new GetRequest($headers);
            break;

            case 'HEAD':
                return new HeadRequest($headers);
            break;

            case 'POST':
                if (null === $body) {
                    throw new BadProtocolException('POST request requires body');
                }

                return new PostRequest($headers, $body);
            break;

            default:
                return new UnknownRequest($headers, $body);
            break;
        }
    }
}
