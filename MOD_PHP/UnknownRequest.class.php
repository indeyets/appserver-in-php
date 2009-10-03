<?php
namespace MFS\AppServer\MOD_PHP;

use MFS\AppServer\HTTP\iGetRequest;

class UnknownRequest extends Request implements iUnknownRequest
{
    public function __get($property)
    {
        if ('body' == $property) {
            return file_get_contents('php://stdin');
        }

        return parent::__get($property);
    }
}
