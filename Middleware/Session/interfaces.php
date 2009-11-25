<?php

namespace MFS\AppServer\Middleware\Session;

interface Storage
{
    public function __construct(array $options);
    public function open($name);
    public function create($name);
    public function save();
    public function destroy();
}
