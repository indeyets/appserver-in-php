<?php

namespace MFS\AppServer\MOD_PHP;

class Cookies implements \ArrayAccess
{
    private $cookies = array();

    public function __construct()
    {
        $this->cookies = $_COOKIE;
    }


    public function setcookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false)
    {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        $this->cookies[$name] = $value;
    }

    public function setrawcookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false)
    {
        setrawcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
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
}