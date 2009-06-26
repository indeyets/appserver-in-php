<?php
namespace MFS\AppServer\SCGI;

class Exception extends \Exception {}
class LogicException extends \LogicException {}
class RuntimeException extends \RuntimeException {}

class BadProtocolException extends LogicException {}
class RetryException extends RuntimeException {}