<?php

namespace Mingle\CacheHub;

use Mingle\CacheHub\Common\Common;
use Mingle\CacheHub\Driver\BaseDriver;
use Mingle\CacheHub\Exception\Exception;
use Mingle\CacheHub\Serializer\SerializerInterface;

trait HandlerTrait
{
    public function setDriver(BaseDriver $driver)
    {
        $this->driver = $driver;
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
    protected function cacheEmptyValue($key)
    {
        if (!$this->isCacheNull) {
            return true;
        }
        if (empty($this->nullExpire) || $this->nullExpire <= 0) {
            $this->nullExpire = 60;
        }
        return (bool)$this->driver->set($key, $this->nullValue, $this->nullExpire);
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
    protected function lockGetData($key, $keyParams, &$stack): array
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
            $data = $this->driver->get($key);
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

    function getExpire(): int
    {
        if (empty($this->expire) || $this->expire <= 0) {
            $this->expire = 300;
        }
        return $this->expire;
    }

    public function get($keyParams = '', $refresh = false)
    {
        $key = $this->getDriverKey($keyParams);

        if (!$refresh) {
            $data = $this->driver->get($key);
            list($parseRet, $data) = $this->parseCacheData($data);
            if ($parseRet) {
                $this->setDataFrom($this->driverName);
                return $this->wrapData($data);
            }

            // 加锁等待数据
            $stack = new \SplStack();
            list ($get, $data) = $this->lockGetData($key, $keyParams, $stack);
            if ($get) {
                $this->setDataFrom($this->driverName);
                return $this->wrapData($data);
            }
        }

        $data = $this->build($keyParams);
        $this->setDataFrom('build');

        $this->setBuildData($key, $data);

        return $this->wrapData($data);
    }

    public function getFromCache($keyParams = '')
    {
        $key = $this->getDriverKey($keyParams);
        $data = $this->driver->get($key);
        list($parseRet, $data) = $this->parseCacheData($data);
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

    protected function setBuildData($key, &$data)
    {
        // 缓存空值
        if ($data === '' || Common::checkEmpty($data)) {
            $data = null;
            return $this->cacheEmptyValue($key);
        }

        // 添加版本号
        $versionData = $this->addDataVersion($data);
        if ($versionData) {
            return (bool)$this->driver->set($key, $versionData, $this->getExpire());
        } else {
            return (bool)$this->driver->set($key, $data, $this->getExpire());
        }
    }

    public function update($keyParams = '')
    {
        $key = $this->getDriverKey($keyParams);
        $data = $this->build($keyParams);
        return $this->setBuildData($key, $data);
    }

    public function set($keyParams = '', $data)
    {
        $key = $this->getDriverKey($keyParams);
        return (bool)$this->driver->set($key, $data, $this->getExpire());
    }

}