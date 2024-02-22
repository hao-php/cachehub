<?php
spl_autoload_register(function($class) {
    $baseDir = dirname(__DIR__) . '/src';
    $offset = strlen('Haoa\\CacheHub\\');
    $path = substr($class, $offset, strlen($class));
    $path = $baseDir . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $path) . '.php';
    require($path);
});