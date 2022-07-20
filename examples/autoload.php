<?php
spl_autoload_register(function($class) {
    $baseDir = dirname(__DIR__) . '/src';
    $offset = strlen('Mingle\\CacheHub\\');
    $path = substr($class, $offset, strlen($class));
    $path = $baseDir . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $path) . '.php';
    require($path);
});