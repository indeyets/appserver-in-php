<?php

namespace AiP\Middleware;

class Directory
{
    private $path;
    private $file_app = null;
    private $directory_listings = false;

    public function __construct($file_app, $path, $directory_listings = false)
    {
        if (!is_dir($path))
            throw new \RuntimeException('"'.$path.'" is not a directory');

        if (!is_callable($file_app))
            throw new InvalidApplicationException('invalid app supplied');

        $this->path = realpath($path);
        $this->file_app = $file_app;
        $this->directory_listings = $directory_listings;
    }

    public function __invoke($ctx)
    {
        $url = $ctx['env']['PATH_INFO'];

        // Normalize URL
        $_pieces = explode('/', trim($url, '/'));
        $_result = array();
        foreach ($_pieces as $piece) {
            if (strlen($piece) == 0)
                continue;

            if ($piece == '.')
                continue;

            if ($piece == '..') {
                if (count($_result) == 0) {
                    // gone out of "chroot"?
                    return array(404, array('Content-Type', 'text/plain'), 'File not found');
                }
                array_pop($_result);
                continue;
            }

            $_result[] = $piece;
        }

        if (count($_result) == 0) {
            $_result_url = '/';
        } else {
            $_result_url = '/'.implode('/', $_result);
            if (strlen($url) > 1 and substr($url, -1) == '/') {
                $_result_url .= '/';
            }
        }

        if ($_result_url !== $url) {
            return $this->redirect($_result_url, $ctx['env']);
        }

        $path = $this->path.$url;

        // Sanity checks
        if (!file_exists($path))
            return array(404, array('Content-Type', 'text/plain'), 'File not found');

        $path = realpath($path);

        if (false === $path) {
            // resolving failed. not enough rights for intermediate folder?
            return array(404, array('Content-Type', 'text/plain'), 'File not found');
        }

        if (strpos($path, $this->path) !== 0) {
            // gone out of "chroot"?
            return array(404, array('Content-Type', 'text/plain'), 'File not found');
        }

        if (!is_readable($path))
            return array(403, array('Content-Type', 'text/plain'), 'Forbidden');

        // Serve directory listing
        if (is_dir($path)) {
            // directories should have trailing slash
            if (substr($url, -1) !== '/') {
                return $this->redirect($url.'/', $ctx['env']);
            }

            return $this->serveListing($path, $url);
        }

        $ctx['Directory']['path'] = $path;

        $app = $this->file_app;
        return $app($ctx);
    }


    private function serveListing($dir, $dir_as_requested)
    {
        if ($this->directory_listings !== true) {
            // Listings are forbidden
            return array(403, array('Content-Type', 'text/plain'), 'Forbidden');
        }

        $title = 'Files from '.htmlspecialchars($dir_as_requested);
        $aip_ad = '<a href="http://github.com/indeyets/appserver-in-php">AiP</a>';

        $html_prefix = '<!DOCTYPE html><html lang="en"><head><title>'.$title.'</title></head><body>';
        $html_suffix = '<hr>Served by '.$aip_ad.', on '.gmdate('D, d M Y H:i:s', time()).' GMT</body></html>';

        $body = '<h1>Directory listing of '.htmlspecialchars($dir_as_requested).'</h1>';

        $body .= '<ul>';

        if ($dir_as_requested != '/') {
            $body .= '<li><a href="../">⇧ to upper level</a></li>';
        }

        $di = new \DirectoryIterator($dir);
        foreach ($di as $item) {
            if ($item->isDot())
                continue;

            $path = $item->getFilename();
            if ($item->isDir()) {
                $path .= '/';
            }

            $body .= '<li><a href="'.$path.'">'.$path.'</a></li>';
        }

        $body .= '</ul>';

        return array(200, array('Content-type', 'text/html; charset=utf-8'), $html_prefix.$body.$html_suffix);
    }


    private function redirect($to, $env)
    {
        $new_path = 'http://'.$env['HTTP_HOST'].$to;

        return array(
            301,
            array(
                'Content-Type', 'text/plain',
                'Location', $new_path
            ),
            'Document moved to: '.$new_path
        );
    }
}
