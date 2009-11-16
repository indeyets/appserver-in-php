<?php
namespace MFS\AppServer\MOD_PHP;

class Request implements \MFS\AppServer\HTTP\iRequest
{
    protected $headers = null;
    protected $cookies = null;

    protected function __construct()
    {
        $this->headers = $_SERVER;

        ksort($this->headers);
    }

    public function __get($property)
    {
        if ($property == 'headers') {
            return $this->headers;
        } elseif ($property == 'get') {
            return $_GET;
        } elseif ('cookies' == $property) {
            return $_COOKIE;
        }

        throw new UnexpectedValueException();
    }

    public static function factory()
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            throw new BadProtocolException("Don't know how to handle this request");
        }

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                return new GetRequest();
            break;

            case 'HEAD':
                return new HeadRequest();
            break;

            case 'POST':
                return new PostRequest();
            break;

            default:
                return new UnknownRequest();
            break;
        }
    }
}
