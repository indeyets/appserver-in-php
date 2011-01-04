<?php

namespace MFS\AppServer\Middleware\Cascade;

class Cascade
{
    private $apps = array();
    private $wrong_status;

    public function __construct(array $apps, $wrong_status = 404)
    {
        if (count($apps) == 0) {
            throw new UnexpectedValueException('$apps array is empty');
        }

        foreach ($apps as $i => $app) {
            if (!is_callable($app))
                throw new \MFS\AppServer\InvalidArgumentException('invalid app supplied on position #'.$i);
        }

        $this->apps = $apps;
        $this->wrong_status = $wrong_status;
    }

    public function __invoke($ctx)
    {
        foreach ($this->apps as $app) {
            $response = $app($ctx);

            if ($response[0] != $this->wrong_status)
                break;
        }

        return $response;
    }
}
