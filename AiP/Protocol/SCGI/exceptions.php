<?php
namespace MFS\AppServer\SCGI;

class Exception extends \Exception {}
class LogicException extends \LogicException {}
class RuntimeException extends \RuntimeException {}
class UnexpectedValueException extends \UnexpectedValueException {}
class InvalidArgumentException extends \InvalidArgumentException {}

class BadProtocolException extends LogicException {}
