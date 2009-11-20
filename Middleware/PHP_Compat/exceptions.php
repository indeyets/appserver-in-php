<?php

namespace MFS\AppServer\Middleware\PHP_Compat;

class UnexpectedValueException extends \UnexpectedValueException {}
class InvalidArgumentException extends \InvalidArgumentException {}

class BadProtocolException extends LogicException {}
