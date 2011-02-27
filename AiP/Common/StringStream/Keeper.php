<?php

namespace AiP\Common\StringStream;

class Keeper
{
    const STREAM_NAME = 'aipstring';

    private $string;
    private $name;

    public static function create($string)
    {
        return new Keeper($string);
    }

    public function __construct($string)
    {
        $this->name = self::STREAM_NAME.'://'.hash('sha1', $string);
        $this->string = $string;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function get()
    {
        return $this->string;
    }

    public function fopen()
    {
        $ctx = stream_context_create(array(
            self::STREAM_NAME => array(
                'string' => $this
            ),
        ));

        return fopen($this->name, 'r', false, $ctx);
    }
}

stream_wrapper_register(Keeper::STREAM_NAME, 'AiP\Common\StringStream');
