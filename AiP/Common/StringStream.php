<?php
namespace AiP\Common;

use AiP\Common\StringStream\InvalidArgumentException;

class StringStream
{
    public $context;

    private $buffer;
    private $position = 0;

    public function stream_open($path, $mode, $flags, &$opened_path)
    {
        if ($mode != 'r') {
            throw new InvalidArgumentException('StringStream is a read-only stream');
        }

        $options = stream_context_get_options($this->context);

        $k = StringStream\Keeper::STREAM_NAME;
        if (!array_key_exists($k, $options) or ! $options[$k]['string'] instanceof StringStream\Keeper) {
            throw new InvalidArgumentException('String streams must be created using the StringStream\Keeper');
        }

        $this->buffer = $options[$k]['string']->get();
        $this->position = 0;

        return true;
    }

    public function stream_read($count)
    {
        $ret = substr($this->buffer, $this->position, $count);
        $this->position += strlen($ret);

        return $ret;
    }

    public function stream_write($data)
    {
    }

    public function stream_tell()
    {
        return $this->position;
    }

    public function stream_eof()
    {
        return $this->position >= strlen($this->buffer);
    }

    public function stream_seek($offset, $whence)
    {
        switch ($whence) {
            case SEEK_SET:
                if ($offset < strlen($this->buffer) && $offset >= 0) {
                     $this->position = $offset;

                     return true;
                } else {
                     return false;
                }
                break;

            case SEEK_CUR:
                if ($offset >= 0) {
                     $this->position += $offset;

                     return true;
                } else {
                     return false;
                }
                break;

            case SEEK_END:
                if (strlen($this->buffer) + $offset >= 0) {
                     $this->position = strlen($this->buffer) + $offset;

                     return true;
                } else {
                     return false;
                }
                break;

            default:
                return false;
        }
    }
}
