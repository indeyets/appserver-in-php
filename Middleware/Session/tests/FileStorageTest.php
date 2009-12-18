<?php

require_once "PHPUnit/Framework/TestCase.php";
error_reporting(E_ALL | E_STRICT);

require '../../../autoload.php';

class FileStorageTest extends PHPUnit_Framework_TestCase
{
    public function test1()
    {
        $dir = dirname(__FILE__).'/sessions';
        mkdir($dir);

        $fs = new MFS_AppServer_Middleware_Session_FileStorage(array('save_path'  => $dir));
        $fs->create('test');

        $file = $dir.'/test.session';

        $this->assertTrue(file_exists($file));

        try {
            $fs->create('test');
            $this->assertTrue(false);
        } catch (LogicException $e) {
            $this->assertTrue(true);
        }

        $fs->save(array('foo' => 'bar'));

        $data = unserialize(file_get_contents($file));

        $this->assertEquals(MFS_AppServer_Middleware_Session_FileStorage::MAGIC, $data['magic']);
        $this->assertEquals('bar', $data['data']['foo']);

        $data = $fs->open('test');
        $this->assertEquals('bar', $data['foo']);

        $data['baz'] = 'bar';
        $fs->save($data);

        $data = unserialize(file_get_contents($file));

        $this->assertEquals(FileStorage::MAGIC, $data['magic']);
        $this->assertEquals('bar', $data['data']['foo']);
        $this->assertEquals('bar', $data['data']['baz']);

        $fs->open('test');
        $fs->destroy();

        $this->assertFalse(file_exists($file));

        rmdir($dir);
    }
}
