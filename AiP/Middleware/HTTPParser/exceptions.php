<?php

namespace MFS\AppServer\Middleware\HTTPParser;

class LogicException extends \LogicException {}

class OutOfBoundsException extends \OutOfBoundsException {}
class UnexpectedValueException extends \UnexpectedValueException {}

class BadProtocolException extends LogicException {}
