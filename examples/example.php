<?php

use Mingle\CacheHub\CacheHandler;
use Mingle\CacheHub\CacheHub;
use Mingle\CacheHub\Lock\RedisLocker;

require __DIR__ . '/autoload.php';


class ExTest extends CacheHandler {

    public $key = "ex_test";
    public $expire = 60;
    public $buildLock = true;

    protected function build($params)
    {
        return 'ex_data';
    }
}


$redis = new Redis();
$redis->connect('redis');
$redis->select(3);


$registerCaches = [
    'ex_test' => new ExTest,
];
$cacheHub = new CacheHub($registerCaches);
$cacheHub->setPrefix('ex:');
$cacheHub->getDriver('cachehub_redis')->setHandler($redis);

$locker = new RedisLocker($redis);
$cacheHub->setLocker($locker);

$cache = $cacheHub->getCache('ex_test');
$cache = $cacheHub->getCache('ex_test');
$data = $cache->get();
var_Dump($data);
var_Dump($cache->getDataFrom());