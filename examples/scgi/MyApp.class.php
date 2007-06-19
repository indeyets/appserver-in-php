<?php

require '../../SCGI/autoload.php';

class MyApp extends SCGI_Application
{
    private $counter = 0;
    private $tpl = null;

    public function __construct($socket_url = 'tcp://127.0.0.1:9999')
    {
        parent::__construct($socket_url);

        $this->tpl = file_get_contents('template.html'); // caching template in local-memory
    }

    protected function requestHandler()
    {
        $out = $this->response();

        $out->addHeader('Status', '200 Ok');
        $out->addHeader('Content-type', 'text/html');

        // replacing {data} in the "template" by our dynamic string and sending it out
        $out->write(str_replace(
            '{data}',
            $this->prepareData(),
            $this->tpl
        ));
    }

    private function prepareData()
    {
        $buffer = '<pre>';
        $buffer .= 'Hello world! #'.(++$this->counter)."\n\n";
        $buffer .= 'Memory usage: '.memory_get_usage()."\n\n";
        $buffer .= 'Peak Memory usage: '.memory_get_peak_usage()."\n\n";
        $buffer .= var_export($this->request()->getAllVars(), true);
        $buffer .= '</pre>';

        return $buffer;
    }
}
