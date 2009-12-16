<?php
namespace MFS\AppServer\SCGI;

class ClientRequest
{
    private $client;

    protected $method = null;
    protected $uri = null;
    protected $headers = array();
    protected $post_vars = array();
    protected $files = array();

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function reset()
    {
        $this->method = null;
        $this->url = null;
        $this->headers = array();
        $this->post_vars = array();
        $this->files = array();
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function setURI($uri)
    {
        if (substr($uri, 0, 1) != '/')
            throw new UnexpectedValueException('URI must start with "/"-character');

        $this->uri = $uri;
    }

    public function addPostParameter($name, $value)
    {
        if ('POST' != $this->method)
            throw new LogicException("You can't add post parameters to requests other than POST");

        $this->post_vars[$name] = $value;
    }

    public function addFile($name, $body, $type = 'application/octet-stream')
    {
        if ('POST' != $this->method)
            throw new LogicException("You can't add files to requests other than POST");

        $files[] = array($name, $body, $type);
    }

    public function send()
    {
        if (!isset($this->method) or !isset($this->uri))
            throw new LogicException("You can't send request without method or URI");

        $headers = array(
            array('REQUEST_METHOD', $this->method),
            array('REQUEST_URI', $this->uri)
        );

        $headers += $this->headers;

        if ('POST' === $this->method) {
            $body = '';

            $post_vars = array();
            foreach ($this->post_vars as $k => $v) {
                $post_vars[] = urlencode($k).'='.urlencode($v);
            }
            $body .= implode('&', $post_vars);
        } else {
            $body = null;
        }

        $this->client->sendRequest($headers, $body);

        $retval = new \stdClass();
        $retval->headers = $this->client->getHeaders();
        $retval->body = $this->client->getBody();

        return $retval;
    }
}