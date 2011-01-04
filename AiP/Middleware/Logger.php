<?php

namespace AiP\Middleware;

class Logger
{
    // Apache's Common Log Format
    const COMBINED_FORMAT = '%h %l %u %t "%r" %>s %b';

    private $app = null;
    private $stream = null;
    private $format = null;

    private $should_close = false;

    public function __construct($app, $stream = STDOUT, $format = self::COMBINED_FORMAT)
    {
        if (!is_callable($app))
            throw new InvalidApplicationException('invalid app supplied');

        if (is_string($stream)) {
            if (file_exists($stream))
                $stream = fopen($stream, 'a');
            else
                $stream = fopen($stream, 'w');

            if (false === $stream) {
                throw new \UnexpectedValueException("Couldn't open provided path for writing");
            }

            $this->should_close = true;
        } elseif (!is_resource($stream)) {
            throw new InvalidArgumentException('second parameter should be a writable stream');
        }

        $this->app = $app;
        $this->stream = $stream;
        $this->format = $format;
    }

    public function __destruct()
    {
        if ($this->should_close) {
            fclose($this->stream);
        }
    }

    public function __invoke($context)
    {
        // gather pre-request data
        $data = array(
            '%h' => $context['env']['HTTP_HOST'],
            '%l' => '-', // ident
            '%u' => '-', // http-auth user FIXME
            '%t' => date('d/M/Y:H:i:s O'),
            '%r' => $context['env']['REQUEST_METHOD'].' '.$context['env']['REQUEST_URI'].' '.$context['env']['HTTP_VERSION'],
        );

        $result = call_user_func($this->app, $context);

        // gather post-request data
        $data['%>s'] = strval($result[0]);

        if (is_string($result[2])) {
            $len = strlen($result[2]);
        } elseif (is_resource($result[2])) {
            $stat = fstat($result[2]);
            $len = $stat['size'];
        }

        $data['%b'] = ($len == 0 ? '-' : strval($len));

        // output data
        fwrite($this->stream, strtr($this->format, $data)."\n");

        // return app's output
        return $result;
    }
}
