<?php
namespace MFS\AppServer\HTTP;

class UnexpectedValueException extends \UnexpectedValueException {}
class BadProtocolException extends UnexpectedValueException {}