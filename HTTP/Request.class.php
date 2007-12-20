<?php
namespace MFS::AppServer::HTTP;

class Request
{
    protected $headers = null;
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
            if ($this->get === null) {
                parse_str($this->headers['QUERY_STRING'], $this->get);
            }

            return $this->get;
        }

        throw new UnexpectedValueException();
    }

    public static function factory(array $headers, $body = null)
    {
        if (!isset($headers['REQUEST_METHOD'])) {
            throw new UnexpectedValueException("Don't know how to handle this request");
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
                    throw new UnexpectedValueException('POST request requires body');
                }

                return new PostRequest($headers, $body);
            break;

            default:
                return new UnknownRequest($headers, $body);
            break;
        }
    }
}
