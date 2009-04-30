<?php
namespace MFS\AppServer\SCGI;
use MFS\AppServer\HTTP\Request;

use \RuntimeException;
use \UnexpectedValueException;

class Response
{
    private $conn = null;
    private $request = null;

    private $headers = array();
    private $sent_headers = false;

    private $content_type = null;
    private $status = '200 Ok';

    public function __construct($conn, Request $request)
    {
        $this->conn = $conn;
        $this->request = $request;

        $this->content_type = ini_get('default_mimetype');

        if ($charset = ini_get('default_charset')) {
            $this->content_type .= '; charset='.$charset;
        }
    }

    public function addHeader($name, $value)
    {
        if ($this->sent_headers)
            throw new RuntimeException("headers are already sent");

        if ($name == 'Status') {
            $this->status = $value;
        } elseif ($name == 'Content-type') {
            $this->content_type = $value;
        } else {
            $this->headers[] = $name.': '.$value;
        }
    }

    public function write($string)
    {
        if (!$this->sent_headers) {
            $this->sendHeaders();
        }

        fwrite($this->conn, $string);
    }

    // compatible with PHP's setcookie() function
    public function setcookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false)
    {
        $this->addHeader('Set-Cookie', self::cookie_headervalue($name, $value, $expire, $path, $domain, $secure, $httponly, false));
    }

    public function setrawcookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false)
    {
        $this->addHeader('Set-Cookie', self::cookie_headervalue($name, $value, $expire, $path, $domain, $secure, $httponly, true));
    }

    // This one almost directly copies php_setcookie() function from php-core
    public static function cookie_headervalue($name, $value, $expire, $path, $domain, $secure, $httponly, $raw)
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

    private function sendHeaders()
    {
        fwrite($this->conn, 'Status: '.$this->status."\r\n");
        fwrite($this->conn, 'Content-type: '.$this->content_type."\r\n");
        fwrite($this->conn, implode("\r\n", $this->headers));
        fwrite($this->conn, "\r\n\r\n");

        $this->sent_headers = true;
    }
}
