<?php

namespace AiP\Common\StringStream;

class Keeper
{
    const STREAM_NAME = 'aipstring';

    private static $strings = null;

    public static function keep($string)
    {
        if (null === self::$strings) {
            self::$strings = array();
        }

        $name = hash('sha1', $string);
        self::$strings[$name] = $string;

        return self::STREAM_NAME.'://'.$name;
    }

    public static function cleanup($_name)
    {
        $name = substr($_name, strlen(self::STREAM_NAME.'://'));
        unset(self::$strings[$name]);
    }

    public static function get($_name)
    {
        $name = substr($_name, strlen(self::STREAM_NAME.'://'));
        return self::$strings[$name];
    }
}

