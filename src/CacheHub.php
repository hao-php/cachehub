<?php
declare(strict_types=1);

namespace Haoa\CacheHub;

use Haoa\CacheHub\Exception\Exception;
use Haoa\CacheHub\Locker\Locker;
use Haoa\CacheHub\Serializer\SerializerInterface;

class CacheHub
{

    const DEFAULT_TTL = 300;

    const DEFAULT_NULL_TTL = 60;

    /** 缓存类对象 */
    protected $cacheObjs = [];

    /** @var Locker 用于构建缓存时的锁 */
    protected $locker;

    protected Container $container;

    /**
     * 缓存的key
     * @var array
     */
    protected $keys = [];

    /** 缓存前缀 */
    protected $prefix = 'cachehub:';


    public function __construct()
    {
        $this->container = new Container();
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

    public function setLogger(LoggerInterface $logger)
    {
        $this->container->setLogger($logger);
    }

    public function getCache(string $cacheClass, bool $isNew = false): CacheHandler
    {
        if (!$isNew && isset($this->cacheObjs[$cacheClass])) {
            return $this->cacheObjs[$cacheClass];
        }
        /** @var $obj CacheHandler */
        $obj = new $cacheClass;
        if (!$obj instanceof CacheHandler) {
            throw new Exception("{$cacheClass} must be of type " . SerializerInterface::class);
        }
        $obj->setLocker($this->locker);
        $obj->setPrefix($this->getPrefix());
        $obj->setContainer($this->container);
        $this->cacheObjs[$cacheClass] = $obj;
        return $obj;
    }


}
