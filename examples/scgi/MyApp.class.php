<?php

require dirname(__FILE__).'/../../SCGI/autoload.php';

class MyApp extends MFS::AppServer::SCGI::Application
{
    private $local_storage;
    private $tpl = null;

    public function __construct($socket_url = 'tcp://127.0.0.1:9999')
    {
        parent::__construct($socket_url);

        $this->tpl = file_get_contents(dirname(__FILE__).'/template.html'); // caching template in local-memory
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

        $buffer = '<pre>';
        $buffer .= 'Hello world! #'.$c."\n";
        $buffer .= 'Memory usage: '.$m."\n";
        $buffer .= 'Peak Memory usage: '.$p."\n";
        $buffer .= 'Memory usage last growed at request#'.$this->local_storage['memory_peak_counter']."\n\n";
        $buffer .= var_export($this->request()->headers, true);
        $buffer .= var_export($this->request()->files, true);
        $buffer .= '</pre>';

        return $buffer;
    }
}
