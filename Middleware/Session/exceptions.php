<?php

namespace MFS\AppServer\Middleware\Session;

class RuntimeException extends \RuntimeException {}
class LogicException extends \LogicException {}

class UnexpectedValueException extends \UnexpectedValueException {}
class OutOfBoundsException extends \OutOfBoundsException {}

class IdIsTakenException extends RuntimeException {}