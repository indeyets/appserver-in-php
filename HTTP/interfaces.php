<?php
namespace MFS\AppServer\HTTP;

interface iUnknownRequest {}

interface iHeadRequest {}
interface iGetRequest {}
interface iPostRequest {}

interface iResponse
{
    public function write($string);
    public function addHeader($name, $value);
    public function setcookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false);
    public function setrawcookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false);
}
