<?php

namespace AiP\Middleware;

class Compress
{
    private $app;

    public function __construct($app)
    {
        if (!is_callable($app))
            throw new InvalidApplicationException('invalid app supplied');

        $this->app = $app;
    }

    public function __invoke($ctx)
    {
        $app = $this->app;
        list($status, $headers, $body) = $app($ctx);

        if (!isset($ctx['env']['HTTP_ACCEPT_ENCODING']) or in_array('Content-Encoding', $headers)) {
            // encoding is not supported or already encoded
            return array($status, $headers, $body);
        }

        $new_headers = array('Vary', 'Accept-Encoding');

        if (is_resource($body)) {
            // FIXME: we should, probably, use pseudo-stream object here, instead
            $_fp = $body;
            $body = stream_get_contents($_fp);
            fclose($_fp);
        }

        // client wants compressed output
        if (false !== strpos($ctx['env']['HTTP_ACCEPT_ENCODING'], 'deflate')) { // will catch x-deflate too
            $body = gzdeflate($body, 3);
            $new_headers[] = 'Content-Encoding';
            $new_headers[] = 'deflate';
        } elseif (false !== strpos($ctx['env']['HTTP_ACCEPT_ENCODING'], 'gzip')) { // will catch x-gzip too
            $body = gzencode($body, 3);
            $new_headers[] = 'Content-Encoding';
            $new_headers[] = 'gzip';
        }

        $new_headers[] = 'Content-Length';
        $new_headers[] = strlen($body);

        for ($i = 0, $cnt = count($headers); $i < $cnt; $i += 2) {
            if (strtolower($headers[$i]) == 'content-length') {
                // skip old Content-Length
                continue;
            }
            $new_headers[] = $headers[$i];
            $new_headers[] = $headers[$i + 1];
        }

        return array($status, $new_headers, $body);
    }
}
