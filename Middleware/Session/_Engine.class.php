<?php

namespace MFS\AppServer\Middleware\Session;

class _Engine
{
    private $cookies = array();
    private $headers = array();

    private $options;

    private $is_started = false;
    private $is_saved = false;

    private $id = null;
    private $vars = array();

    private $_fp = null;

    public function __construct($context)
    {
        if (isset($context['env']['HTTP_COOKIE']))
            $this->parseCookies($context['env']['HTTP_COOKIE']);
    }

    public function __get($varname)
    {
        if (false === $this->is_started)
            throw new LogicException('Session is not started');

        if (!array_key_exists($varname, $this->vars))
            throw new OutOfBoundsException('there is no "'.$varname.'" var in session');

        return $this->vars[$varname];
    }

    public function __set($varname, $value)
    {
        if (false === $this->is_started)
            throw new LogicException('Session is not started');

        $this->vars[$varname] = $value;
    }

    public function __isset($varame)
    {
        if (false === $this->is_started)
            throw new LogicException('Session is not started');

        return array_key_exists($varname, $this->vars);
    }

    public function getId()
    {
        if (false === $this->is_started)
            throw new LogicException('Session is not started');

        return $this->id;
    }

    public function start(array $options = array())
    {
        if (true === $this->is_started)
            throw new LogicException('Session is already started');

        $this->options = array_merge(
            array(
                'cookie_name' => ini_get('session.name'),
                'save_path' => ini_get('session.save_path'),
                'hash_algorithm' => 'sha1',
                'cookie_lifetime' => ini_get('session.cookie_lifetime'),
                'cookie_path' => ini_get('session.cookie_path'),
                'cookie_domain' => ini_get('session.cookie_domain'),
                'cookie_secure' => ini_get('session.cookie_secure'),
                'cookie_httponly' => ini_get('session.cookie_httponly'),
            ),
            $options
        );

        if ($this->cookieIsSet()) {
            $this->id = $this->getIdFromCookie();
            $this->validateSessionFile();

            $this->lock();
            $this->vars = unserialize(file_get_contents($this->getSessionFilename()));
        } else {
            $this->generateId();
            $this->createCookie();

            $this->lock();
        }

        $this->is_started = true;
    }

    public function save()
    {
        if (false === $this->is_started)
            throw new LogicException('Session is not started');

        file_put_contents($this->getSessionFilename(), serialize($this->vars));
        $this->unlock();

        $this->vars = array();
        $this->is_started = false;
    }

    public function destroy()
    {
        if (false === $this->is_started)
            throw new LogicException('Session is not started');

        $this->unlock();
        unlink($this->getSessionFilename());

        $this->dropCookie();
        $this->id = null;

        $this->vars = array();
        $this->is_started = false;
    }




    private function generateId()
    {
        while (true) {
            $this->id = hash($this->options['hash_algorithm'], mt_rand());

            if (!file_exists($this->getSessionFilename()))
                break; // cool, we're first here
        }
    }

    private function getSessionFilename()
    {
        return $this->options['save_path'].'/'.$this->id.'.session';
    }

    private function validateSessionFile()
    {
        $dir = $this->options['save_path'];

        if (empty($dir) or !is_dir($dir))
            throw new RuntimeException('"'.$dir.'" is not a valid directory');

        if (!is_writable($dir))
            throw new RuntimeException('Noe enough rights to write to "'.$dir.'"');

        $file = $dir.'/'.$this->id.'.session';

        if (file_exists($file) and !is_writable($file))
            throw new RuntimeException('Noe enough rights to write to "'.$file.'"');
    }

    private function lock()
    {
        $this->_fp = @fopen($this->getSessionFilename(), 'r+');
        if (false === $this->_fp) {
            $this->_fp = null;
            throw new RuntimeException('Could not open "'.$this->getSessionFilename().'" file for read&write');
        }

        flock($this->_fp, LOCK_EX);
        return true;
    }

    private function unlock()
    {
        fclose($this->_fp);
        $this->_fp = null;
    }


    // cookie stuff
    private function parseCookies($cookiestr)
    {
        $pairs = explode('; ', $cookiestr);

        $this->cookies = array();

        foreach ($pairs as $pair) {
            list($name, $value) = explode('=', $pair);
            $this->cookies[$name] = urldecode($value);
        }
    }

    private function cookieIsSet()
    {
        $name = $this->options['cookie_name'];
        return isset($this->cookies[$name]);
    }

    private function getIdFromCookie()
    {
        $name = $this->options['cookie_name'];
        return $this->cookies[$name];
    }

    private function createCookie()
    {
        $lifetime = $this->options['cookie_lifetime'] === 0 ? 0 : $this->options['cookie_lifetime'] + time();

        $this->setcookie($this->id, $lifetime);
    }

    private function dropCookie()
    {
        $this->setcookie('', time() - 3600);
    }



    // low-level stuff
    private function setcookie($value, $expire)
    {
        $name = $this->options['cookie_name'];

        $this->headers[] = 'Set-Cookie';
        $this->headers[] = self::cookie_headervalue(
            $name, $value,
            $expire,
            $this->options['cookie_path'], $this->options['cookie_domain'],
            $this->options['cookie_secure'], $this->options['cookie_httponly']
        );

        $this->cookies[$name] = $value;
    }

    private static function cookie_headervalue($name, $value, $expire, $path, $domain, $secure, $httponly)
    {
        if (false !== strpbrk($name, "=,; \t\r\n\013\014")) {
            throw new UnexpectedValueException("Cookie names can not contain any of the following: '=,; \\t\\r\\n\\013\\014'");
        }

        $string = $name.'=';

        if ('' == $value) {
            // deleting
            $string .= 'deleted; expires='.date("D, d-M-Y H:i:s T", time() - 31536001);
        } else {
            $string .= urlencode($value);

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


    public function _getHeaders()
    {
        return $this->headers;
    }
}
