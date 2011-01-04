<?php

namespace MFS\AppServer
{
    class DaemonicHandler extends \AiP\Handler\Daemonic
    {
        public function __construct($socket_url, $protocol_name, $transport_name = 'Socket')
        {
            trigger_error('You are using deprecated '.__CLASS__.' class. Please switch to the new AiP\Handler\Daemonic class', E_USER_WARNING);
            parent::__construct($socket_url, $protocol_name, $transport_name);
        }
    }
}

namespace MFS\AppServer\Apps\FileServe
{
    class FileServe extends \AiP\App\FileServe
    {
        public function __construct($path)
        {
            trigger_error('You are using deprecated '.__CLASS__.' class. Please switch to the new AiP\App\FileServe class', E_USER_WARNING);
            parent::__construct($path);
        }
    }
}

namespace MFS\AppServer\Middleware\PHP_Compat
{
    class PHP_Compat extends \AiP\Middleware\HTTPParser
    {
        public function __construct($app, array $options = array())
        {
            trigger_error('You are using deprecated '.__CLASS__.' class. Please switch to the new AiP\Middleware\HTTPParser class', E_USER_WARNING);
            parent::__construct($app, $options);
        }
    }
}

namespace MFS\AppServer\Middleware\Logger
{
    class Logger extends \AiP\Middleware\Logger
    {
        public function __construct($app, $stream = STDOUT, $format = \AiP\Middleware\Logger::COMBINED_FORMAT)
        {
            trigger_error('You are using deprecated '.__CLASS__.' class. Please switch to the new AiP\Middleware\Logger class', E_USER_WARNING);
            parent::__construct($app, $stream, $format);
        }
    }
}

namespace MFS\AppServer\Middleware\Session
{
    class Session extends \AiP\Middleware\Session
    {
        public function __construct($app)
        {
            trigger_error('You are using deprecated '.__CLASS__.' class. Please switch to the new AiP\Middleware\Session class', E_USER_WARNING);
            parent::__construct($app);
        }
    }
}

namespace MFS\AppServer\Middleware\URLMap
{
    class URLMap extends \AiP\Middleware\URLMap
    {
        public function __construct(array $map)
        {
            trigger_error('You are using deprecated '.__CLASS__.' class. Please switch to the new AiP\Middleware\URLMap class', E_USER_WARNING);
            parent::__construct($map);
        }
    }
}
