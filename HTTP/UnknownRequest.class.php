<?php
namespace MFS\AppServer\HTTP;

class UnknownRequest extends Request implements iUnknownRequest
{
    public function __get($property)
    {
        if ('body' == $property) {
            return $this->body;
        }

        return parent::__get($property);
    }
}
