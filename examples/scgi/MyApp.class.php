<?php

require __DIR__.'/../../SCGI/autoload.php';

class MyApp extends MFS\AppServer\SCGI\Application
{
    private $local_storage;
    private $tpl = null;

    public function __construct($socket_url = 'tcp://127.0.0.1:9999')
    {
        parent::__construct($socket_url);

        $this->tpl = file_get_contents(__DIR__.'/template.html'); // caching template in local-memory
        $this->local_storage = array(
            'counter' => 0,
            'prev_memory_peak' => 0,
            'memory_peak_counter' => 0
        );
    }

    protected function requestHandler()
    {
        $out = $this->response();

        $out->addHeader('Status', '200 Ok');
        $out->addHeader('Content-type', 'text/html; charset=utf-8');

        if (!isset($this->request()->cookies['Hello']))
            $out->setcookie('Hello', 'world!');

        // replacing {data} in the "template" by our dynamic string and sending it out
        $out->write(str_replace(
            '{data}',
            $this->prepareData(),
            $this->tpl
        ));
    }

    private function prepareData()
    {
        $c = ++$this->local_storage['counter'];
        $m = memory_get_usage();
        $p = memory_get_peak_usage();

        if ($p > $this->local_storage['prev_memory_peak']) {
            $this->local_storage['prev_memory_peak'] = $p;
            $this->local_storage['memory_peak_counter'] = $c;
        }

        $req = $this->request();

        $buffer = '<pre>';
        $buffer .= 'Hello world! #'.$c."\n";
        $buffer .= 'Memory usage: '.$m."\n";
        $buffer .= 'Peak Memory usage: '.$p."\n";
        $buffer .= 'Memory usage last growed at request#'.$this->local_storage['memory_peak_counter']."\n\n";
        $buffer .= "HEADERS:\n".var_export($req->headers, true)."\n";
        $buffer .= "COOKIES:\n".var_export($req->cookies, true)."\n";
        $buffer .= "GET:\n".var_export($req->get, true)."\n";

        if ($req instanceof MFS\AppServer\HTTP\iPostRequest) {
            $buffer .= "POST:\n".var_export($req->post, true)."\n";
            $buffer .= "FILES:\n".var_export($req->files, true)."\n";
        }

        $buffer .= '</pre>';

        return $buffer;
    }
}
