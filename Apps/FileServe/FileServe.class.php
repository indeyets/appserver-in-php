<?php

namespace MFS\AppServer\Apps\FileServe;

class FileServe
{
    private $path;

    public function __construct($path)
    {
        if (!is_dir($path))
            throw new \Exception('"'.$path.'" is not a directory');

        $this->path = $path;
    }

    public function __invoke($ctx)
    {
        $path = $this->path.'/'.$ctx['env']['PATH_INFO'];

        if (!file_exists($path))
            return array(404, array('Content-type', 'text/plain'), 'File not found');

        if (!is_readable($path))
            return array(403, array('Content-type', 'text/plain'), 'Forbidden');

        return array(200, array('Content-type', self::getContentType($path)), file_get_contents($path));
    }

    private static function getContentType($path)
    {
        $name = basename($path);
        $extension = substr($path, strrpos($path, '.') + 1);

        switch ($extension) {
            case 'css':
                return 'text/css';

            case 'jpg':
                return 'image/jpeg';

            case 'gif':
                return 'image/gif';

            default:
                return 'text/plain';
        }
    }
}
