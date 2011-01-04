<?php

namespace MFS\AppServer\Middleware\ConditionalGet;

class ConditionalGet
{
    private $app = null;

    public function __construct($app)
    {
        if (!is_callable($app))
            throw new \MFS\AppServer\InvalidArgumentException('invalid app supplied');

        $this->app = $app;
    }

    public function __invoke($ctx)
    {
        $app = $this->app;
        list($status, $headers, $body) = $app($ctx);

        $etag =    isset($ctx['env']['HTTP_IF_NONE_MATCH'])     ? $ctx['env']['HTTP_IF_NONE_MATCH']     : null;
        $lastmod = isset($ctx['env']['HTTP_IF_MODIFIED_SINCE']) ? $ctx['env']['HTTP_IF_MODIFIED_SINCE'] : null;

        if ($this->matches($headers, $etag, $lastmod)) {
            return array(304, array(), '');
        }

        return array($status, $headers, $body);
    }

    private function matches($headers, $etag, $lastmod)
    {
        for ($i = 0, $cnt = count($headers); $i < $cnt; $i += 2) {
            $name = strtolower($headers[$i]);
            $value = $headers[$i + 1];

            if ($name == 'etag' and $value == $etag) {
                return true;
            }

            if ($name == 'last-modified' and $value == $lastmod) {
                return true;
            }
        }

        return false;
    }
}
