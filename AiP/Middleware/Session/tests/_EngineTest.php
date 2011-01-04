<?php

require_once "PHPUnit/Framework/TestCase.php";
error_reporting(E_ALL | E_STRICT);

require '../autoload.php';

use \MFS\AppServer\Middleware\Session\_Engine;
use \MFS\AppServer\Middleware\Session\Storage;

class DumbStorage implements Storage
{
    private static $data = array();

    private $name;

    public function __construct(array $options)
    {
    }

    public function open($name)
    {
        if (!isset(self::$data[$name]))
            throw new RuntimeException();

        $this->name = $name;

        return self::$data[$name];
    }

    public function create($name)
    {
        if (isset(self::$data[$name]))
            throw new RuntimeException();

        $this->name = $name;

        self::$data[$name] = array();
    }

    public function save(array $vars)
    {
        self::$data[$this->name] = $vars;
        $this->name = null;
    }


    public function destroy()
    {
        unset(self::$data[$this->name]);
        $this->name = null;
    }
}

class _EngineDump extends PHPUnit_Framework_TestCase
{
    public function test1()
    {
        $context = array('env' => array());

        $sess = new _Engine($context);
        $sess->start(array(
            'storage'       => 'DumbStorage',
            'cookie_name'   => 'session',
        ));

        $sess->foo = 'bar';
        $sess->baz = 'bar2';

        $sess->save();

        try {
            $this->assertFalse(isset($sess->foo));
            $this->assertFalse(isset($sess->baz));
            $this->assertFalse(isset($sess->bar));
        } catch (LogicException $e) {
            $this->assertTrue(true);
        }

        $headers = $sess->_getHeaders();

        $this->assertEquals(2, count($headers));
        $this->assertEquals('Set-Cookie', $headers[0]);
        $this->assertTrue(strpos($headers[1], 'session=') === 0);

        $sess->start(array(
            'storage'       => 'DumbStorage',
            'cookie_name'   => 'session',
        ));

        $this->assertTrue(isset($sess->foo));
        $this->assertTrue(isset($sess->baz));
        $this->assertFalse(isset($sess->bar));

        $this->assertEquals('bar', $sess->foo);
        $this->assertEquals('bar2', $sess->baz);

        unset($sess->foo);
        $this->assertFalse(isset($sess->foo));

        $sess->destroy();

        try {
            $this->assertFalse(isset($sess->foo));
            $this->assertFalse(isset($sess->baz));
            $this->assertFalse(isset($sess->bar));
        } catch (LogicException $e) {
            $this->assertTrue(true);
        }

        $headers = $sess->_getHeaders();

        $this->assertEquals(4, count($headers));
        $this->assertEquals('Set-Cookie', $headers[0]);
        $this->assertTrue(strpos($headers[1], 'session=') === 0);
        $this->assertEquals('Set-Cookie', $headers[2]);
        $this->assertTrue(strpos($headers[3], 'session=deleted') === 0);
    }
}
