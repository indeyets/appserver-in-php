<?php
namespace MFS::AppServer::SCGI;

class Request
{
    private $headers = array();
    private $body = null;
    private $get = array();
    private $post = array();
    private $files = array();

    public function __construct($conn)
    {
        if (!is_numeric($len = stream_get_line($conn, 20, ':')))
            throw new RuntimeException("invalid protocol");

        $_headers = explode("\0", stream_get_line($conn, $len)); // getting headers
        $divider = stream_get_line($conn, 1); // ","

        $first = null;
        foreach ($_headers as $element) {
            if (null === $first) {
                $first = $element;
            } else {
                $this->headers[$first] = $element;
                $first = null;
            }
        }
        unset($_headers, $first);

        if (!isset($this->headers['SCGI']) or $this->headers['SCGI'] != '1')
            throw new RuntimeException("Reqest is not SCGI/1 Compliant");

        if (!isset($this->headers['CONTENT_LENGTH']))
            throw new RuntimeException("CONTENT_LENGTH header not present");

        if ($this->headers['CONTENT_LENGTH'] > 0) {
            if ($this->headers['CONTENT_LENGTH'] > self::IniString_to_Bytes(ini_get('post_max_size')))
                throw new RuntimeException("POST is larger than allowed by post_max_size");

            // $this->body = stream_get_line($conn, $this->headers['CONTENT_LENGTH'], '');
            $this->body = stream_get_contents($conn, $this->headers['CONTENT_LENGTH']);

            if (strlen($this->body) != $this->headers['CONTENT_LENGTH']) {
                throw new RuntimeException("Didn't get all of the request: ".strlen($this->body).' of '.$this->headers['CONTENT_LENGTH']);
            }
        }

        unset($this->headers['SCGI'], $this->headers['CONTENT_LENGTH']);

        parse_str($this->headers['QUERY_STRING'], $this->get);

        if ($this->isPost()) {
            if (isset($this->headers['CONTENT_TYPE']) and strpos($this->headers['CONTENT_TYPE'], 'multipart/form-data') === 0) {
                $this->parseMultipart();
            } else {
                parse_str(urldecode($this->body), $this->post);
            }
        }
    }

    public function __destruct()
    {
        foreach ($this->files as $file) {
            if ($file['error'] == UPLOAD_ERR_OK and file_exists($file['tmp_name'])) {
                unlink($file['tmp_name']);
            }
        }
    }

    private function parseMultipart()
    {
        $vars_accu = array(); // place to accumulate parts of post-vars string

        $ct = $this->headers['CONTENT_TYPE'];
        $b = $this->body;

        $pos = strpos($ct, '=-') + 1;
        $boundary = '--'.substr($ct, $pos);
        $boundary_len = strlen($boundary);

        $pos = 0;
        while (substr($b, $pos + $boundary_len, 2) != '--') {
            // getting headers of part
            $h_start = $pos + $boundary_len + 2;
            $h_end = strpos($b, "\r\n\r\n", $h_start);

            $headers = array();
            foreach (explode("\r\n", substr($b, $h_start, $h_end - $h_start)) as $h_str) {
                $divider = strpos($h_str, ':');
                $headers[substr($h_str, 0, $divider)] = html_entity_decode(substr($h_str, $divider + 2), ENT_QUOTES, 'UTF-8');
            }

            if (!isset($headers['Content-Disposition']))
                throw new RuntimeException("Didn't find Content-disposition in one of the parts of multipart: ".var_export(array_keys($headers), true));

            // parsing dispositin-header of part
            $disposition = array();
            foreach (explode("; ", $headers['Content-Disposition']) as $d_part) {
                if ($d_part == 'form-data')
                    continue;

                $divider = strpos($d_part, '=');
                $disposition[substr($d_part, 0, $divider)] = substr($d_part, $divider + 2, -1);
            }

            // getting body of part
            $b_start = $h_end + 4;
            $b_end = strpos($b, "\r\n".$boundary, $b_start);

            if (false === $b_end) {
                throw new RuntimeException("Didn't find end of body :-/");
            }

            $file_data = substr($b, $b_start, $b_end - $b_start);

            if (isset($disposition['filename'])) {
                $tmp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();

                // ToDo:
                //  UPLOAD_ERR_FORM_SIZE
                //  UPLOAD_ERR_PARTIAL (?)
                //  UPLOAD_ERR_NO_FILE (?)
                //  UPLOAD_ERR_EXTENSION

                if (empty($tmp_dir)) {
                    $this->files[$disposition['name']] = array(
                        'name' => '',
                        'type' => '',
                        'size' => 0,
                        'tmp_name' => '',
                        'error' => UPLOAD_ERR_NO_TMP_DIR
                    );
                } elseif ($b_end - $b_start > self::IniString_to_Bytes(ini_get('upload_max_filesize'))) {
                    $this->files[$disposition['name']] = array(
                        'name' => '',
                        'type' => '',
                        'size' => 0,
                        'tmp_name' => '',
                        'error' => UPLOAD_ERR_INI_SIZE
                    );
                } elseif (false === $tmp_file = tempnam($tmp_dir, 'SCGI') or false === file_put_contents($tmp_file, $file_data)) {
                    if ($tmp_file !== false)
                        unlink($tmp_file);

                    $this->files[$disposition['name']] = array(
                        'name' => '',
                        'type' => '',
                        'size' => 0,
                        'tmp_name' => '',
                        'error' => UPLOAD_ERR_CANT_WRITE
                    );
                } else {
                    $this->files[$disposition['name']] = array(
                        'name' => $disposition['filename'],
                        'type' => '',
                        'size' => filesize($tmp_file),
                        'tmp_name' => $tmp_file,
                        'error' => UPLOAD_ERR_OK
                    );
                }
            } else {
                $vars_accu[] = $disposition['name'].'='.urlencode($file_data);
            }
            unset($file_data);

            $pos = $b_end + 2;
        }

        // registering not-files as post-vars
        $vars_accu = implode('&', $vars_accu);
        parse_str($vars_accu, $this->post);
    }

    public function isPost()
    {
        return $this->headers['REQUEST_METHOD'] == 'POST';
    }

    // HEADERS from web-server
    public function getHeader($name)
    {
        if (!isset($this->headers[$name]))
            return false;

        return $this->headers[$name];
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    // vars from QUERY_STRING
    public function getGetVar($name)
    {
        if (!isset($this->get[$name]))
            return false;

        return $this->get[$name];
    }

    public function getGetVars()
    {
        return $this->get;
    }

    // POST request vars
    public function getPostVar($name)
    {
        if (!isset($this->post[$name]))
            return false;

        return $this->post[$name];
    }

    public function getPostVars()
    {
        return $this->post;
    }

    // FILES request vars
    public function getFiles()
    {
        return $this->files;
    }

    // returns array, with keys corresponding to standars autoglobal-names _GET/_POST/_SERVER
    public function getAllVars()
    {
        $res = array(
            '_SERVER' => $this->headers,
            '_GET' => $this->get
        );

        if ($this->isPost()) {
            $res['_POST'] = $this->post;

            if (count($this->files) > 0) {
                $res['_FILES'] = $this->files;
            }
        }

        return $res;
    }

    // available in POST, for example
    public function getBody()
    {
        return $this->body;
    }

    // utility functions
    public static function IniString_to_Bytes($val)
    {
        $val = trim($val);
        $last = strtolower($val{strlen($val)-1});
        switch($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}
