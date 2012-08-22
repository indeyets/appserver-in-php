<?php

require_once "PHPUnit/Framework/TestCase.php";
error_reporting(E_ALL | E_STRICT);

require __DIR__.'/../../../autoload.php';

use AiP\Middleware\Session\Storage\FileStorage;

class FileStorageTest extends PHPUnit_Framework_TestCase
{
    private $dir;

    public function setUp()
    {
        $this->dir = __DIR__.'/sessions';

        if (!is_dir($this->dir)) {
            mkdir($this->dir);
        }
    }

    public function tearDown()
    {
        if (is_dir($this->dir)) {
            rmdir($this->dir);
        }
    }

    public function test1()
    {
        $fs = new FileStorage(array('save_path' => $this->dir));
        $fs->create('test');

        $file = $this->dir.'/test.session';

        $this->assertTrue(file_exists($file));

        try {
            $fs->create('test');
            $this->assertTrue(false);
        } catch (LogicException $e) {
            $this->assertTrue(true);
        }

        $fs->save(array('foo' => 'bar'));

        $data = unserialize(file_get_contents($file));

        $this->assertEquals(FileStorage::MAGIC, $data['magic']);
        $this->assertEquals('bar', $data['data']['foo']);

        $data = $fs->open('test');
        $this->assertEquals('bar', $data['foo']);

        $data['baz'] = 'bar';
        $fs->save($data);

        $str = file_get_contents($file);
        $data = unserialize($str);

        $this->assertEquals(FileStorage::MAGIC, $data['magic']);
        $this->assertEquals('bar', $data['data']['foo']);
        $this->assertEquals('bar', $data['data']['baz']);

        $fs->open('test');
        $fs->destroy();

        $this->assertFalse(file_exists($file));
    }
}
