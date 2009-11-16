<?php
namespace MFS\AppServer\HTTP;

interface iRequest
{
    public function __get($parameter);
}

interface iUnknownRequest extends iRequest {}

interface iHeadRequest extends iRequest {}
interface iGetRequest extends iRequest {}
interface iPostRequest extends iRequest {}

interface iResponse
{
    public function write($string);
    public function addHeader($name, $value);
    public function setcookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false);
    public function setrawcookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false);
}
