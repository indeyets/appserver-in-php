<?php
namespace MFS\AppServer\MOD_PHP;

class Response
{
    private $request = null;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function addHeader($name, $value)
    {
        if (headers_sent())
            throw new RuntimeException("headers are already sent");

        header($name.': '.$value, false);
    }

    public function write($string)
    {
        echo $string;
    }

    // compatible with PHP's setcookie() function
    public function setcookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false)
    {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    public function setrawcookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false)
    {
        setrawcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
}
