<?php

namespace MFS\AppServer\Middleware\PHP_Compat;

class Cookies implements \ArrayAccess
{
    private $headers = array();
    private $cookies = array();

    public function __construct($cookiestr = null)
    {
        if (null === $cookiestr) {
            return;
        }

        $pairs = explode('; ', $cookiestr);

        foreach ($pairs as $pair) {
            list($name, $value) = explode('=', $pair);
            $this->cookies[$name] = urldecode($value);
        }
    }


    public function setcookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false)
    {
        $this->addHeader('Set-Cookie', self::cookie_headervalue($name, $value, $expire, $path, $domain, $secure, $httponly, false));
        $this->cookies[$name] = $value;
    }

    public function setrawcookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false)
    {
        $this->addHeader('Set-Cookie', self::cookie_headervalue($name, $value, $expire, $path, $domain, $secure, $httponly, true));
        $this->cookies[$name] = $value;
    }

    public function __toArray()
    {
        return $this->cookies;
    }


    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->cookies);
    }

    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset))
            throw new OutOfBoundException();

        return $this->cookies[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new LogicException();
    }

    public function offsetUnset($offset)
    {
        throw new LogicException();
    }


    public function _getHeaders()
    {
        return $this->headers;
    }

    private function addHeader($name, $value)
    {
        $this->headers[] = $name;
        $this->headers[] = $value;
    }

    // This one almost directly copies php_setcookie() function from php-core
    private static function cookie_headervalue($name, $value, $expire, $path, $domain, $secure, $httponly, $raw)
    {
        if (false !== strpbrk($name, "=,; \t\r\n\013\014")) {
            throw new UnexpectedValueException("Cookie names can not contain any of the following: '=,; \\t\\r\\n\\013\\014'");
        }

        if (true === $raw && false !== strpbrk($value, ",; \t\r\n\013\014")) {
            throw new UnexpectedValueException("Cookie values can not contain any of the following: ',; \\t\\r\\n\\013\\014'");
        }

        $string = $name.'=';

        if ('' == $value) {
            // deleting
            $string .= 'deleted; expires='.date("D, d-M-Y H:i:s T", time() - 31536001);
        } else {
            if (true === $raw) {
                $string .= $value;
            } else {
                $string .= urlencode($value);
            }

            if ($expire > 0) {
                $string .= '; expires='.date("D, d-M-Y H:i:s T", $expire);
            }
        }

        if (null !== $path)
            $string .= '; path='.$path;

        if (null !== $domain)
            $string .= '; domain='.$domain;

        if (true === $secure)
            $string .= '; secure';

        if (true === $httponly)
            $string .= '; httponly';

        return $string;
    }
}