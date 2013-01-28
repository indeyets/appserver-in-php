<?php

class FileserverTest extends PHPUnit_Framework_TestCase
{
    private $process;
    private $pipes = array();
    public function setUp()
    {
        $path = dirname(__DIR__);
        $pipes = array();
        $this->process = proc_open("php {$path}/bin/aip files", array(), $this->pipes, $path);
        sleep(2);
    }

    public function testLoadFile()
    {
        $config = file_get_contents('http://127.0.0.1:8080/composer.json');
        $configParsed = json_decode($config);
        $this->assertEquals('aip/aip', $configParsed->name);
    }

    public function tearDown()
    {
        proc_terminate($this->process);
    }
}

