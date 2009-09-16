<?php
namespace MFS\AppServer\MOD_PHP;

use MFS\AppServer\HTTP\iPostRequest;

class PostRequest extends Request implements iPostRequest
{
    public function __destruct()
    {
        foreach ($this->files as $file) {
            if ($file['error'] == UPLOAD_ERR_OK and file_exists($file['tmp_name'])) {
                unlink($file['tmp_name']);
            }
        }
    }

    public function __get($property)
    {
        if ($property == 'post') {
            return $_POST;
        } elseif ($property == 'files') {
            return $_FILES;
        }

        return parent::__get($property);
    }
}
