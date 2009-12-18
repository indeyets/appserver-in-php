<?php

namespace MFS\AppServer\Middleware\PHP_Compat;

class PHP_Compat
{
    private $app;
    private $options;

    public function __construct($app, array $options = array())
    {
        if (!is_callable($app))
            throw new InvalidArgumentException('not a valid app');

        $this->app = $app;
        $this->options = $options;
    }

    public function __invoke($context)
    {
        // _GET vars
        $context['_GET'] = array();
        if (isset($context['env']['QUERY_STRING'])) {
            parse_str($context['env']['QUERY_STRING'], $context['_GET']);
        }

        // _COOKIE vars
        if (isset($context['env']['HTTP_COOKIE']))
            $ck = new Cookies($context['env']['HTTP_COOKIE']);
        else
            $ck = new Cookies(null);

        $context['_COOKIE'] = $ck;

        // _POST and _FILES
        if ($context['env']['REQUEST_METHOD'] == 'POST') {
            $context['_POST'] = array();
            $context['_FILES'] = array();

            echo "getting buffer\n";
            $buffer = stream_get_contents($context['stdin'], $context['env']['CONTENT_LENGTH']);
            echo "got buffer\n";

            if (isset($this->options['forward_stream']) and $this->options['forward_stream'] === true) {
                // user asks us to provide a valid stream to app
                $stream_name = StringStreamKeeper::keep($buffer);
                $_old_stdin = $context['stdin'];
                $context['stdin'] = fopen($stream_name, 'r');
            }

            if (isset($context['env']['CONTENT_TYPE']) and strpos($context['env']['CONTENT_TYPE'], 'multipart/form-data') === 0) {
                self::parseMultipart($context['env']['CONTENT_TYPE'], $buffer, $context['_POST'], $context['_FILES']);
            } else {
                echo "parsing…\n";
                parse_str($buffer, $context['_POST']);
            }
            unset($buffer); // free memory
        }

        // EXECUTE
        $result = call_user_func($this->app, $context);

        if (!is_array($result)) {
            return $result;
        }

        // Append cookie-headers
        $result[1] = array_merge($result[1], $ck->_getHeaders());

        // Cleanup
        if (isset($_old_stdin)) {
            // remove our "fake" stream
            fclose($context['stdin']);
            StringStreamKeeper::cleanup();
            $context['stdin'] = $_old_stdin;
        }

        if (isset($context['env']['_FILES'])) {
            // remove created files, if there were any
            foreach ($context['env']['_FILES'] as $file) {
                if ($file['error'] == UPLOAD_ERR_OK and file_exists($file['tmp_name'])) {
                    unlink($file['tmp_name']);
                }
            }
        }

        return $result;
    }


    private static function parseMultipart($ct, $b, &$_POST, &$_FILES)
    {
        $vars_accu = array(); // place to accumulate parts of post-vars string

        foreach (explode('; ', $ct) as $ct_part) {
            $pos = strpos($ct_part, 'boundary=');

            if ($pos !== 0)
                continue;

            $boundary = '--'.substr($ct_part, $pos + 9);
            $boundary_len = strlen($boundary);
        }

        if (!isset($boundary))
            throw new BadProtocolException("Didn't find boundary-declaration in multipart");

        $post_strs = array();
        $pos = 0;
        while (substr($b, $pos + $boundary_len, 2) != '--') {
            // getting headers of part
            $h_start = $pos + $boundary_len + 2;
            $h_end = strpos($b, "\r\n\r\n", $h_start);

            if (false === $h_end) {
                throw new BadProtocolException("Didn't find end of headers-zone");
            }

            $headers = array();
            foreach (explode("\r\n", substr($b, $h_start, $h_end - $h_start)) as $h_str) {
                $divider = strpos($h_str, ':');
                $headers[substr($h_str, 0, $divider)] = html_entity_decode(substr($h_str, $divider + 2), ENT_QUOTES, 'UTF-8');
            }

            if (!isset($headers['Content-Disposition']))
                throw new BadProtocolException("Didn't find Content-disposition in one of the parts of multipart: ".var_export(array_keys($headers), true));

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
                throw new BadProtocolException("Didn't find end of body :-/");
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
                    $_FILES[$disposition['name']] = array(
                        'name' => '',
                        'type' => '',
                        'tmp_name' => '',
                        'error' => UPLOAD_ERR_NO_TMP_DIR,
                        'size' => 0,
                    );
                } elseif ($b_end - $b_start > self::IniString_to_Bytes(ini_get('upload_max_filesize'))) {
                    $_FILES[$disposition['name']] = array(
                        'name' => '',
                        'type' => '',
                        'tmp_name' => '',
                        'error' => UPLOAD_ERR_INI_SIZE,
                        'size' => 0,
                    );
                } elseif (0 === strlen($disposition['filename'])) {
                    $_FILES[$disposition['name']] = array(
                        'name' => '',
                        'type' => '',
                        'tmp_name' => '',
                        'error' => UPLOAD_ERR_NO_FILE,
                        'size' => 0,
                    );
                } elseif (false === $tmp_file = tempnam($tmp_dir, 'SCGI') or false === file_put_contents($tmp_file, $file_data)) {
                    if ($tmp_file !== false)
                        unlink($tmp_file);

                    $_FILES[$disposition['name']] = array(
                        'name' => '',
                        'type' => '',
                        'tmp_name' => '',
                        'error' => UPLOAD_ERR_CANT_WRITE,
                        'size' => 0,
                    );
                } else {
                    $filesize = filesize($tmp_file);
                    $_FILES[$disposition['name']] = array(
                        'name' => $disposition['filename'],
                        'type' => '',
                        'tmp_name' => $tmp_file,
                        'error' => (0 === $filesize) ? 5 : UPLOAD_ERR_OK,
                        'size' => $filesize,
                    );
                }
            } else {
                $post_strs[] = urlencode($disposition['name']).'='.urlencode($file_data);
            }
            unset($file_data);

            $pos = $b_end + 2;
        }

        parse_str(implode('&', $post_strs), $_POST);
    }

    // utility functions
    protected static function IniString_to_Bytes($val)
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
