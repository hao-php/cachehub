<?php

namespace Haoa\CacheHub;

interface LoggerInterface
{

    public function debug(string $msg): void;

    public function error(string $msg): void;

}