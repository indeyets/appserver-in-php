<?php

namespace MFS\AppServer\Middleware\Session;

interface Storage
{
    public function __construct(array $options);
    public function open($name); // should return array of vars
    public function create($name);
    public function save(array $vars);
    public function destroy();
}
