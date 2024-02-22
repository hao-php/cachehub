<?php
declare(strict_types=1);

namespace Haoa\CacheHub;

use Haoa\CacheHub\Driver\ApcuDriver;
use Haoa\CacheHub\Driver\RedisDriver;
use Haoa\CacheHub\Serializer\JsonSerializer;
use Haoa\CacheHub\Serializer\OriginalSerializer;

abstract class CacheHandler
{

    use HandlerTrait;

    /** 缓存前缀 */
    public $prefix = '';

    /** 缓存key */
    public $key = '';

    /** 当数据为空时存的值, _cachehub_null */
    public $nullValue = '';

    /** 是否缓存空值 */
    public $isCacheNull = true;

    /** 是否给数据添加版本号 */
    public $addVersion = false;

    /**  数据版本号, 当addVersion=true时生效 */
    public $version = 1;

    /** 构建数据, 当遇到锁重试次数, 需要buildLock=true */
    public $buildWaitCount = 3;

    /** 构建数据, 当遇到锁重试时间间隔, 毫秒 */
    public $buildWaitTime = 100;

    /** 构建数据超时处理模式, 1:放行到build, 2:抛出异常 */
    public $buildWaitMod = 1;

    /** build数据时是否加锁 */
    public $buildLock = false;

    /** @var array */
    public $cacheListExample = [
        [
            'driver' => ApcuDriver::class,
            'serializer' => OriginalSerializer::class, // default
            'null_ttl' => 5,
            'ttl' => 5,
        ],
        [
            'driver' => RedisDriver::class,
            'driver_handler' => null,
            'serializer' => JsonSerializer::class,
            'ttl' => 300,
            'null_ttl' => 60,
        ],
    ];


    abstract protected function getCacheList(): array;

    /**
     * 构建数据
     * @param mixed $params
     * @return mixed
     */
    abstract protected function build($params);

    /**
     * 拿到数据后, 包装数据再返回
     * @param mixed $params
     * @return mixed
     */
    protected function wrapData($data)
    {
        return $data;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

}