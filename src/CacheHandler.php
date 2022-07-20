<?php
declare(strict_types=1);

namespace Mingle\CacheHub;

use Mingle\CacheHub\Driver\BaseDriver;
use Mingle\CacheHub\Locker\Locker;
use Mingle\CacheHub\Serializer\SerializerInterface;

abstract class CacheHandler
{

    use HandlerTrait;

    /** 缓存前缀 */
    public $prefix = '';

    /** 缓存key */
    public $key = '';

    /** 过期时间, 秒 */
    public $expire = 300;

    /** 当数据为空时存的值, _cachehub_null */
    public $nullValue = '';

    /** 空值过期时间, 秒 */
    public $nullExpire = 60;

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

    /**
     * @var Locker
     */
    public $locker;

    /** @var BaseDriver */
    protected $driver;

    /** 使用的缓存驱动 */
    public $driverName = 'cachehub_redis';

    /** 序列化器名称 */
    public $serializerName = 'cachehub_json';

    /** @var SerializerInterface */
    protected $serializer;

    /** @var string 数据来源 */
    protected $dataFrom = '';

    /** @var bool 是否初始化 */
    protected $isInit = false;

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

}