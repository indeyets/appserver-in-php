<?php
namespace AiP\Common;

class StringStream
{
    private $buffer;
    private $position = 0;

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        if ($mode != 'r') {
            throw new InvalidArgumentException('StringStream is a read-only stream');
        }

        $this->buffer = StringStreamKeeper::get($path);
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

stream_wrapper_register(StringStream\Keeper::STREAM_NAME, __NAMESPACE__.'\StringStream');
