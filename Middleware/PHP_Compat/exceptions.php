<?php

namespace MFS\AppServer\Middleware\PHP_Compat;

class LogicException extends \LogicException {}

class OutOfBoundsException extends \OutOfBoundsException {}
class UnexpectedValueException extends \UnexpectedValueException {}
class InvalidArgumentException extends \InvalidArgumentException {}

class BadProtocolException extends LogicException {}
