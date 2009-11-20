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

        $context['mfs.session'] = new _Engine($context);
        $result = $this->app($context);
    }
}
