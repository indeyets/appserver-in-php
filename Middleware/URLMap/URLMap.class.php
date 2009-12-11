<?php

namespace MFS\AppServer\Middleware\URLMap;

class URLMap
{
    private $app;

    public function __construct(array $map)
    {
        $this->mapping = array();
        foreach ($map as $location => $app) {
            if (!is_callable($app))
                throw new InvalidArgumentException('invalid app supplied for "'.$location.'" path');

            $i = new \stdClass();
            $i->app = \MFS\AppServer\callable($app);

            if (false !== mb_ereg('\Ahttps?://(.*?)(/.*)', $location, $parts)) {
                $i->host = $parts[1];
                $i->location = $parts[2];
            } else {
                $i->host = null;
                $i->location = $location;
            }

            if ($i->location[0] != '/')
                throw new UnexpectedValueException('Location has to start with "/"');

            $this->mapping[] = $i;
        }

        usort($this->mapping, function($a, $b){
            if (0 != $h = strlen($b->host) - strlen($a->host))
                return $h;

            return strlen($b->location) - strlen($a->location);
        });
    }

    public function __invoke($ctx)
    {
        $path = self::squeeze($ctx['env']['PATH_INFO'], '/');
        $script_name = $ctx['env']['SCRIPT_NAME'];
        $host        = $ctx['env']['HTTP_HOST'];
        $server_name = $ctx['env']['SERVER_NAME'];
        $server_port = $ctx['env']['SERVER_PORT'];

        foreach ($this->mapping as $i) {
            if ($i->host != $host and
                $i->host != $server_name and
                !($i->host === null and ($host === $server_name or $host === $server_name.':'.$server_port))
            )
                continue;

            if (strpos($path, $i->location) !== 0)
                continue;

            if (strlen($i->location) != strlen($path) and $path[strlen($i->location)] != '/')
                continue;

            $ctx['env']['SCRIPT_NAME'] = $script_name.$i->location;
            $ctx['env']['PATH_INFO'] = substr($path, strlen($i->location));

            $app = $i->app; // php doesn't allow to call properties as methods
            return $app($ctx);
        }

        return array(404, array("Content-Type", "text/plain"), "Not Found: ".$path);
    }

    // helpers
    private static function squeeze($where, $what)
    {
        return mb_ereg_replace($what.'+', $what, $where);
    }
}
