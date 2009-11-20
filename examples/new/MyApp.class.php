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
        if (!isset($context['_COOKIE']['Hello']))
            $context['_COOKIE']->setcookie('Hello', 'world!');

        $status = 200;
        $headers = array('Conent-type', 'text/html; charset=utf-8');
        // replacing {data} in the "template" by our dynamic string and sending it out
        $body = str_replace('{data}', $this->prepareData($context), $this->tpl);

        return array($status, $headers, $body);
    }

    private function prepareData($context)
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
        $buffer .= "HEADERS:\n".var_export($context['env'], true)."\n";
        $buffer .= "COOKIES:\n".var_export($context['_COOKIE']->__toArray(), true)."\n";
        $buffer .= "GET:\n".var_export($context['_GET'], true)."\n";

        if ($context['env']['REQUEST_METHOD'] === 'POST') {
            $buffer .= "POST:\n".var_export($context['_POST'], true)."\n";
            $buffer .= "FILES:\n".var_export($context['_FILES'], true)."\n";
        } elseif (!in_array($context['env']['REQUEST_METHOD'], array('GET', 'HEAD'))) {
            $buffer .= "BODY:\n".var_export(stream_get_contents($context['stdin']), true)."\n";
        }

        $buffer .= '</pre>';

        return $buffer;
    }
}
