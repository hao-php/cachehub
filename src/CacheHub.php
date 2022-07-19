<?php
declare(strict_types=1);

namespace Quhao\CacheHub;

use Quhao\CacheHub\Driver\BaseDriver;
use Quhao\CacheHub\Driver\Redis;
use Quhao\CacheHub\Exception\Exception;
use Quhao\CacheHub\Lock\Locker;
use Quhao\CacheHub\Serializer\Json;
use Quhao\CacheHub\Serializer\SerializerInterface;

class CacheHub
{

    /** 可用的缓存驱动 */
    protected $drivers = [];

    /** 数据序列化器  */
    protected $serializers = [];

    /** 注册的缓存 */
    protected $registerCaches = [];

    /** @var Locker 用于构建缓存时的锁 */
    protected $locker;

    /**
     * 缓存的key
     * @var array
     */
    protected $keys = [];

    /** 缓存前缀 */
    public $prefix = 'cachehub:';

    /**
     * @return array
     */
    public function getDrivers(): array
    {
        return $this->drivers;
    }

    /**
     * @param array|BaseDriver[] $drivers
     */
    public function addDrivers(array $drivers): void
    {
        foreach ($drivers as $name => $driver) {
            if (isset($this->drivers[$name])) {
                throw new Exception("driver[{$name}] already exists");
            }
            if (!$driver instanceof BaseDriver) {
                throw new Exception("driver[{$name}] must be be an instance of " . BaseDriver::class);
            }
        }
        $this->drivers = array_merge($this->drivers, $drivers);
    }

    /**
     * @return array|SerializerInterface[]
     */
    public function getSerializers(): array
    {
        return $this->serializers;
    }

    /**
     * @param array $serializers
     */
    public function addSerializer(array $serializers): void
    {
        foreach ($serializers as $name => $obj) {
            if (isset($this->serializers[$name])) {
                throw new Exception("serializer[{$name}] already exists");
            }
            if (!$obj instanceof SerializerInterface) {
                throw new Exception("serializer[{$name}] must be be an instance of " . SerializerInterface::class);
            }
        }
        $this->serializers = array_merge($this->serializers, $serializers);
    }

    /**
     * @return array
     */
    public function getRegisterCaches(): array
    {
        return $this->registerCaches;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function setLocker(Locker $locker)
    {
        $this->locker = $locker;
    }

    public function __construct($registerCaches)
    {
        foreach ($registerCaches as $name => $cache) {
            if (!$cache instanceof CacheHandler) {
                throw new Exception("cache[{$name}] must be an instance of " . CacheHandler::class);
            }
            if (empty($cache->key)) {
                throw new Exception("cache[{$name}] key is empty");
            }
            if (isset($this->keys[$cache->key])) {
                throw new Exception("cache[{$name}] key[$cache->key] is repeated");
            }
            $this->keys[$cache->key] = 1;
        }
        $this->registerCaches = $registerCaches;
        $this->serializers = [
            'cachehub_json' => new Json(),
        ];
        $this->drivers = [
            'cachehub_redis' => new Redis(),
        ];
    }

    public function getDriver($name) : BaseDriver
    {
        return $this->drivers[$name];
    }

    public function getCache(string $cacheName): CacheHandler
    {
        if (empty($this->registerCaches[$cacheName])) {
            throw new Exception("cache[{$cacheName}] is not registered");
        }
        /** @var $obj CacheHandler */
        $obj = $this->registerCaches[$cacheName];
        // if (!$obj instanceof CacheHandler) {
        //     throw new Exception("cache[{$cacheName}] must be be an instance of " . CacheHandler::class);
        // }
        if (!$obj->isInit()) {
            $this->setCacheDriver($obj);
            $this->setCacheSerializer($obj);
            $obj->setLocker($this->locker);
            $obj->prefix = $this->getPrefix();
            $obj->setInit();
        }
        return $obj;
    }

    public function setCacheDriver(CacheHandler &$cacheHandler)
    {
        if (empty($cacheHandler->driverName)) {
            throw new Exception("driverName is empty");
        }
        if (!isset($this->getDrivers()[$cacheHandler->driverName])) {
            throw new Exception("driver[{$cacheHandler->driverName}] is not exists");
        }
        $cacheHandler->setDriver($this->getDrivers()[$cacheHandler->driverName]);
    }

    public function setCacheSerializer(CacheHandler &$cacheHandler)
    {
        if (empty($cacheHandler->serializerName)) {
            throw new Exception("serializerName is empty");
        }
        if (!isset($this->getSerializers()[$cacheHandler->serializerName])) {
            throw new Exception("serializer[{$cacheHandler->serializerName}] is not exists");
        }
        $cacheHandler->setSerializer($this->getSerializers()[$cacheHandler->serializerName]);
    }


}
