<?php
declare(strict_types=1);

use Haoa\CacheHub\CacheHub;
use Haoa\CacheHub\Locker\RedisLocker;

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

    public static function getCacheHub($redis = null)
    {
        if (empty($redis)) {
            $redis = self::getRedis();
        }

        $cacheHub = new CacheHub();
        $cacheHub->setPrefix('unit_test:');
        $locker = new RedisLocker($redis);
        $cacheHub->setLocker($locker);
        return $cacheHub;
    }

}