<?php

use Mingle\CacheHub\CacheHandler;
use Mingle\CacheHub\CacheHub;
use Mingle\CacheHub\Locker\RedisLocker;

require __DIR__ . '/autoload.php';


class ExTest extends CacheHandler {

    public $key = "ex_test";
    public $expire = 60;
    public $buildLock = true;
    public $driverName = 'cachehub_redis';
    public $serializerName = 'cachehub_json';

    /**
     * 生成数据
     */
    protected function build($params)
    {
        return 'ex_data';
    }

    /**
     * 包装数据
     */
    protected function wrapData($data)
    {
        return $data;
    }
}

class AppCacheHub
{
    /** 测试用 */
    const EX_TEXT = 'ex_test';

    /**
     * 获取cacheHub对象, 自行处理单例, 初始化
     */
    public static function getCacheHub() : CacheHub
    {
        $redis = new Redis();
        $redis->connect('redis');
        $redis->select(3);

        $cacheHub = new CacheHub(self::getRegisterCaches());

        // 添加缓存驱动
        // $cacheHub->addDrivers();

        // 添加序列化器
        // $cacheHub->addSerializer();

        // 设置key的前缀
        $cacheHub->setPrefix('ex:');

        // 把redis缓存注入到内置的驱动上
        $cacheHub->getDriver('cachehub_redis')->setHandler($redis);

        // 注入redis锁
        $locker = new RedisLocker($redis);
        $cacheHub->setLocker($locker);

        return $cacheHub;
    }

    public static function getRegisterCaches()
    {
        return [
            self::EX_TEXT => new ExTest,
        ];
    }

    public static function test()
    {
        $cacheHub = self::getCacheHub();
        $cache = $cacheHub->getCache(AppCacheHub::EX_TEXT);

        // 获取数据
        $data = $cache->get();
        $from = $cache->getDataFrom();
        var_dump($data, $from);

        // 强制刷新, 获取数据
        $cache->get('', true);

        // 更新数据
        $cache->update('');

        // 设置数据
        $cache->set('', 'test_data');

        // 调用原生驱动的方法
        $cache->lPush('test', 1);
    }

}

AppCacheHub::test();