<?php

namespace MFS\AppServer\Middleware\PHP_Compat;
use MFS\AppServer\StringStreamKeeper;

class PHP_Compat extends \MFS\AppServer\Middleware\HTTPParser\HTTPParser
{
    public function __construct($app, array $options = array())
    {
        parent::__construct($app, $options);
        trigger_error('usage of PHP_Compat middleware is deprecated. Use HTTPParser from now on.', E_USER_WARNING);
    }
}
