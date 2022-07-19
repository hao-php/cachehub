<?php
declare(strict_types=1);

use Quhao\CacheHub\CacheHub;
use Quhao\CacheHub\Lock\RedisLocker;

class Common
{


    /**
     * @return Redis
     */
    public static function getRedis()
    {
        $redis = new Redis();
        $redis->connect('redis');
        $redis->select(3);
        return $redis;
    }

    public static function getCacheHub($registerCaches)
    {
        $redis = self::getRedis();

        $cacheHub = new CacheHub($registerCaches);
        $cacheHub->setPrefix('unit_test:');
        $cacheHub->getDriver('cachehub_redis')->setHandler($redis);
        $locker = new RedisLocker($redis);
        $cacheHub->setLocker($locker);
        return $cacheHub;
    }

}