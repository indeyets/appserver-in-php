<?php

namespace MFS\AppServer\Middleware\Session;

class Session
{
    private $app;

    public function __construct($app)
    {
        if (!is_callable($app))
            throw new InvalidArgumentException('not a valid app');

        $this->app = $app;
    }

    public function __invoke($context)
    {
        if (isset($context['mfs.session']))
            throw new LogicException('"mfs.session" key is already occupied in context');

        $ck = $context['mfs.session'] = new _Engine($context);

        $result = call_user_func($this->app, $context);

        // Append cookie-headers
        $result[1] = array_merge($result[1], $ck->_getHeaders());

        return $result;
    }
}
