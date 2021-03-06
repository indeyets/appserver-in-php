<?php

namespace AiP\Middleware\Session\Storage;

class FileStorage implements \AiP\Middleware\Session\Storage
{
    const MAGIC = 'AIP_SESSION';

    private $options;
    private $name = null;

    private $_fp = null;
    private $vars = array();

    public function __construct(array $options)
    {
        $this->options = array_merge(
            array(
                'save_path' => ini_get('session.save_path'),
            ),
            $options
        );

        $dir = $this->options['save_path'];

        if (empty($dir) or !is_dir($dir))
            throw new RuntimeException('"'.$dir.'" is not a valid directory');

        if (!is_writable($dir))
            throw new RuntimeException('Noe enough rights to write to "'.$dir.'"');
    }

    public function open($name)
    {
        $this->name = $name;

        $this->validateSessionFile($name);

        $this->lock();
        $this->readData();

        return $this->vars;
    }

    public function create($name)
    {
        if (null !== $this->name) {
            throw new LogicException('session is opened already');
        }

        if (!self::idIsFree($name))
            throw new IdIsTakenException('session-name is already taken');

        $this->name = $name;

        $this->lock();
    }

    public function save(array $vars)
    {
        if (null === $this->name) {
            throw new LogicException('session is not opened');
        }

        $this->vars = $vars;

        $this->flushData();
        $this->unlock();

        $this->name = null;
    }

    public function destroy()
    {
        if (null === $this->name) {
            throw new LogicException('session is not opened');
        }

        $this->unlock();
        unlink($this->getSessionFilename());

        $this->name = null;
    }

    public function isValid($name)
    {
        try {
            $this->validateSessionFile($name);
        } catch (RuntimeException $e) {
            return false;
        }

        $file = $this->getSessionFilename($name);

        if (!file_exists($file)) {
            return false;
        }

        if (filesize($file) === 0) {
            return false;
        }

        return true;
    }

    private function idIsFree($name)
    {
        return !file_exists($this->getSessionFilename($name));
    }

    private function validateSessionFile($name)
    {
        $file = $this->getSessionFilename($name);

        if (file_exists($file) and !is_writable($file)) {
            throw new RuntimeException('Not enough rights to write to "'.$file.'"');
        }
    }

    private function getSessionFilename($name = null)
    {
        if (null === $name)
            $name = $this->name;

        return $this->options['save_path'].'/'.$name.'.session';
    }

    private function lock()
    {
        $file = $this->getSessionFilename();

        if (file_exists($file))
            $this->_fp = @fopen($file, 'r+');
        else
            $this->_fp = @fopen($file, 'w+');

        if (false === $this->_fp) {
            $this->_fp = null;
            throw new RuntimeException('Could not open "'.$this->getSessionFilename().'" file for read&write');
        }

        flock($this->_fp, LOCK_EX);

        return true;
    }

    private function unlock()
    {
        fclose($this->_fp);
        $this->_fp = null;
    }

    private function readData()
    {
        $data = stream_get_contents($this->_fp, -1, 0);
        $this->vars = self::unserialize($data);
    }

    private function flushData()
    {
        $data = self::serialize($this->vars);
        ftruncate($this->_fp, 0);
        fseek($this->_fp, 0);
        fwrite($this->_fp, $data);
    }

    private static function serialize(array $data)
    {
        $container = array(
            'magic' => self::MAGIC,
            'data' => $data
        );

        return \serialize($container);
    }

    private static function unserialize($string)
    {
        $result = @\unserialize($string);

        if (!is_array($result)
            or !array_key_exists('magic', $result) or !array_key_exists('data', $result)
            or $result['magic'] !== self::MAGIC or !is_array($result['data'])
        ) {
            throw new UnexpectedValueException('not a valid session');
        }

        return $result['data'];
    }
}
