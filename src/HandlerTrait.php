<?php

namespace Haoa\CacheHub;

use Haoa\CacheHub\Common\Common;
use Haoa\CacheHub\Driver\BaseDriver;
use Haoa\CacheHub\Exception\Exception;
use Haoa\CacheHub\Locker\Locker;
use Haoa\CacheHub\Serializer\OriginalSerializer;
use Haoa\CacheHub\Serializer\SerializerInterface;

trait HandlerTrait
{

    /**
     * @var Locker
     */
    protected $locker;

    /** @var string 数据来源 */
    protected $dataFrom = '';

    /** @var bool 是否初始化 */
    protected $isInit = false;

    protected Container $container;

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    public function setDriver(BaseDriver $driver)
    {
        $this->driver = $driver;
    }

    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->driver->setSerializer($this->serializer);
    }

    public function setLocker($locker)
    {
        $this->locker = $locker;
    }

    public function isInit(): bool
    {
        return $this->isInit;
    }

    public function setInit()
    {
        $this->isInit = true;
    }

    protected function getKey(): string
    {
        if (empty($this->key)) {
            throw new Exception("key is empty");
        }
        return $this->key;
    }

    protected function getDriverKey($keyParams): string
    {
        return $this->driver->buildKey($this->prefix, $this->getKey(), $keyParams);
    }

    protected function checkDataVersion(&$data)
    {
        if (!$this->addVersion) {
            return true;
        }
        if (isset($data['cachehub_version']) && $data['cachehub_version'] == $this->version) {
            $data = $data['data'];
            return true;
        }
        return false;
    }

    protected function addDataVersion($data)
    {
        if (!$this->addVersion) {
            return false;
        }
        $data = [
            'cachehub_version' => $this->version,
            'data' => $data,
        ];
        return $data;
    }

    protected function setDataFrom(string $step)
    {
        $this->dataFrom = $step;
    }

    public function getDataFrom(): string
    {
        return $this->dataFrom;
    }

    public function clearDataFrom()
    {
        $this->dataFrom = '';
    }

    /**
     * 保存空值到缓存
     */
    protected function cacheEmptyValue(BaseDriver $driver, $key, $nullTtl)
    {
        if (!$this->isCacheNull) {
            return true;
        }
        $nullTtl = intval($nullTtl);
        if (empty($nullTtl) || $nullTtl <= 0) {
            $nullTtl = CacheHub::DEFAULT_NULL_TTL;
        }
        return (bool)$driver->set($key, $this->nullValue, $nullTtl);
    }

    protected function checkEmptyValue($value)
    {
        if ($value === $this->nullValue) {
            return true;
        }
        return false;
    }

    /**
     * 加锁, 并等待数据
     */
    protected function lockGetData(BaseDriver $driver, $key, $keyParams, &$stack): array
    {
        if (!$this->buildLock || empty($this->buildWaitCount) || $this->buildWaitCount <= 0) {
            return [false, null];
        }
        if (empty($this->locker)) {
            throw new Exception('locker is empty');
        }
        $lockKey = $this->locker->getLockKey($this->prefix, $this->getKey(), $keyParams);
        $lockExpireTime = (int)round($this->buildWaitCount * $this->buildWaitTime / 1000) + 10;

        if ($this->locker->tryLock($lockKey, 1, $lockExpireTime)) {
            Common::stackDefer($stack, function () use ($lockKey) {
                $this->locker->unLock($lockKey);
            });
            return [false, null];
        }

        for ($i = 0; $i < $this->buildWaitCount; $i++) {
            $data = $driver->get($key);
            list($parseRet, $data) = $this->parseCacheData($data);
            if ($parseRet) {
                return [true, $data];
            }

            $isLocked = $this->locker->isLocked($lockKey);
            if (!$isLocked) {
                return [false, null];
            }
        }

        if ($this->buildWaitMod == 2) {
            throw new Exception("build data timeout");
        }

        return [false, null];

    }

    public function get($keyParams = '', $refresh = false)
    {
        if (empty($this->getCacheList())) {
            throw new \Exception('cacheList is empty');
        }
        $setDrivers = [];
        $len = count($this->getCacheList());
        $index = 0;
        $data = null;
        $get = false;

        if (!$refresh) {
            foreach ($this->getCacheList() as $v) {
                $index++;
                if (empty($v['driver'])) {
                    throw new \Exception('driver is empty');
                }
                $serializerClass = $v['serializer'] ?: OriginalSerializer::class;
                $driver = $this->container->getDriver($v['driver'], $serializerClass, $v['driver_handler'] ?? null);

                $key = $driver->buildKey($this->prefix, $this->getKey(), $keyParams);

                $data = $driver->get($key);
                list($get, $data) = $this->parseCacheData($data);
                if ($get) {
                    $this->setDataFrom($v['driver']);
                    break;
                }

                // 最后一级缓存
                if ($index == $len) {
                    // 加锁等待数据
                    $stack = new \SplStack();
                    list ($get, $data) = $this->lockGetData($driver, $key, $keyParams, $stack);
                    if ($get) {
                        $this->setDataFrom($v['driver']);
                        break;
                    }
                }

                $setDrivers[] = [
                    'driver_class' => $v['driver'],
                    'driver' => $driver,
                    'key' => $key,
                    'ttl' => $v['ttl'] ?? 0,
                    'null_ttl' => $v['null_ttl'] ?? 0,
                ];
            }
        } else {
            foreach ($this->getCacheList() as $v) {
                if (empty($v['driver'])) {
                    throw new \Exception('driver is empty');
                }
                $serializerClass = $v['serializer'] ?: OriginalSerializer::class;
                $driver = $this->container->getDriver($v['driver'], $serializerClass, $v['driver_handler'] ?? null);
                $key = $driver->buildKey($this->prefix, $this->getKey(), $keyParams);

                $setDrivers[] = [
                    'driver_class' => $v['driver'],
                    'driver' => $driver,
                    'key' => $key,
                    'ttl' => $v['ttl'] ?? 0,
                    'null_ttl' => $v['null_ttl'] ?? 0,
                ];
            }
        }

        if (!$get) {
            $data = $this->build($keyParams);
            $this->setDataFrom('build');
        }
        $setLen = count($setDrivers);
        if ($setLen > 0) {
            for ($i = $setLen - 1; $i >= 0; $i--) {
                /** @var BaseDriver $driver */
                $driver = $setDrivers[$i]['driver'];
                $key = $setDrivers[$i]['key'];
                $driverClass = $setDrivers[$i]['driver_class'];
                $ret = $this->setBuildData($driver, $key, $data, $setDrivers[$i]['ttl'], $setDrivers[$i]['null_ttl']);
                if (!$ret) {
                    $this->container->getLogger() and $this->container->getLogger()->error("{$driverClass} fail to set");
                } else {
                    $this->container->getLogger() and $this->container->getLogger()->debug("{$driverClass} set successfully");
                }
            }
        }

        return $this->wrapData($data);
    }

    protected function parseCacheData($data)
    {
        // 检查数据版本
        if (!Common::checkEmpty($data) && $data !== '' && $this->checkDataVersion($data)) {
            return [true, $data];
        }

        // 是否为空值
        if ($this->checkEmptyValue($data)) {
            return [true, null];
        }

        return [false, null];
    }

    protected function setBuildData(BaseDriver $driver, $key, $data, $ttl, $nullTtl)
    {
        // 缓存空值
        if ($data === '' || Common::checkEmpty($data)) {
            $data = null;
            return $this->cacheEmptyValue($driver, $key, $nullTtl);
        }

        $ttl = intval($ttl);
        if ($ttl == 0 || $ttl < 0) {
            $ttl = CacheHub::DEFAULT_TTL;
        }
        // 添加版本号
        $versionData = $this->addDataVersion($data);
        if ($versionData) {
            return (bool)$driver->set($key, $versionData, $ttl);
        } else {
            return (bool)$driver->set($key, $data, $ttl);
        }
    }

    public function update($keyParams = ''): int
    {
        $data = $this->build($keyParams);
        $successNum = 0;
        $cacheList = $this->getCacheList();
        $cacheList = array_reverse($cacheList);
        foreach ($cacheList as $v) {
            if (empty($v['driver'])) {
                throw new \Exception('driver is empty');
            }
            $serializerClass = $v['serializer'] ?: OriginalSerializer::class;
            $driver = $this->container->getDriver($v['driver'], $serializerClass, $v['driver_handler'] ?? null);

            $key = $driver->buildKey($this->prefix, $this->getKey(), $keyParams);
            $ret = $this->setBuildData($driver, $key, $data, $v['ttl'] ?? 0, $v['null_ttl'] ?? 0);
            if (!$ret) {
                $this->container->getLogger() and $this->container->getLogger()->error($v['driver'] . " fail to set");
            } else {
                $successNum++;
                $this->container->getLogger() and $this->container->getLogger()->debug("{$driverClass} set successfully");
            }
        }

        return $successNum;
    }

    public function __call($name, $arguments)
    {
        if (count($this->getCacheList()) == 1) {
            $cacheList = $this->getCacheList();
            $v = reset($cacheList);
            $serializerClass = $v['serializer'] ?? OriginalSerializer::class;
            $driver = $this->container->getDriver($v['driver'], $serializerClass, $v['driver_handler'] ?? null);

            return call_user_func_array([$driver, $name], $arguments);
        }
        throw new \Exception("{$name} is unsupported");
    }

}