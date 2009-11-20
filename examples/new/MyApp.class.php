<?php

class MyApp
{
    private $local_storage;
    private $tpl = null;

    public function __construct()
    {
        $this->tpl = file_get_contents(__DIR__.'/template.html'); // caching template in local-memory
        $this->local_storage = array(
            'counter' => 0,
            'prev_memory_peak' => 0,
            'memory_peak_counter' => 0
        );
    }

    public function __invoke($context)
    {
        $context['response']->addHeader('Status', '200 Ok');
        $context['response']->addHeader('Content-type', 'text/html; charset=utf-8');

        if (!isset($context['request']->cookies['Hello']))
            $context['response']->setcookie('Hello', 'world!');

        // replacing {data} in the "template" by our dynamic string and sending it out
        $context['response']->write(str_replace(
            '{data}',
            $this->prepareData($context['request']),
            $this->tpl
        ));
    }

    private function prepareData($req)
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
        $buffer .= "HEADERS:\n".var_export($req->headers, true)."\n";
        $buffer .= "COOKIES:\n".var_export($req->cookies, true)."\n";
        $buffer .= "GET:\n".var_export($req->get, true)."\n";

        if ($req instanceof MFS\AppServer\HTTP\iPostRequest) {
            $buffer .= "POST:\n".var_export($req->post, true)."\n";
            $buffer .= "FILES:\n".var_export($req->files, true)."\n";
        } elseif ($req instanceof MFS\AppServer\HTTP\iUnknownRequest) {
            $buffer .= "BODY:\n".var_export($req->body, true)."\n";
        }

        $buffer .= '</pre>';

        return $buffer;
    }
}
