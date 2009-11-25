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

    private $storage = null;

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

    public function __isset($varname)
    {
        if (false === $this->is_started)
            throw new LogicException('Session is not started');

        return array_key_exists($varname, $this->vars);
    }

    public function __unset($varname)
    {
        if (false === $this->is_started)
            throw new LogicException('Session is not started');

        unset($this->vars[$varname]);
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
                'hash_algorithm' => 'sha1',
                'storage' => __NAMESPACE__.'\\FileStorage',
                'cookie_lifetime' => ini_get('session.cookie_lifetime'),
                'cookie_path' => ini_get('session.cookie_path'),
                'cookie_domain' => ini_get('session.cookie_domain'),
                'cookie_secure' => ini_get('session.cookie_secure'),
                'cookie_httponly' => ini_get('session.cookie_httponly'),
            ),
            $options
        );

        $class = $this->options['storage'];

        if (!in_array(__NAMESPACE__.'\\Storage', class_implements($class))) {
            throw new UnexpectedValueException($storage.' class does not implement Storage interface');
        }

        $this->storage = new $class($this->options);

        if ($this->cookieIsSet()) {
            $this->fetchIdFromCookie();

            $this->vars = $this->storage->open($this->id);
        } else {
            $this->createSessionWithNewId();
            $this->createCookie();
        }

        $this->is_started = true;
    }

    public function save()
    {
        if (false === $this->is_started)
            throw new LogicException('Session is not started');

        $this->storage->save($this->vars);
        $this->storage = null;

        $this->vars = array();
        $this->is_started = false;
    }

    public function destroy()
    {
        if (false === $this->is_started)
            throw new LogicException('Session is not started');

        $this->storage->destroy();
        $this->storage = null;

        $this->dropCookie();
        $this->id = null;

        $this->vars = array();
        $this->is_started = false;
    }



    private function createSessionWithNewId()
    {
        $callback = array($this->options['storage'], 'idIsFree');

        while (true) {
            $id = hash($this->options['hash_algorithm'], mt_rand());

            try {
                $this->storage->create($id);
                break; // cool, we're first here
            } catch (IdIsTakenException $e) {
            }
        }

        $this->id = $id;
    }

    private function fetchIdFromCookie()
    {
        $this->id = $this->getIdFromCookie();
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
