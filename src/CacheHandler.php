<?php
declare(strict_types=1);

namespace Quhao\CacheHub;

use Quhao\CacheHub\Common\Common;
use Quhao\CacheHub\Driver\BaseDriver;
use Quhao\CacheHub\Exception\Exception;
use Quhao\CacheHub\Lock\Locker;
use Quhao\CacheHub\Serializer\SerializerInterface;

abstract class CacheHandler
{

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
    public $buildWaitCount = 10;

    /** 构建数据, 当遇到锁重试时间间隔, 毫秒 */
    public $buildWaitTime = 10;

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

    public static function make(): CacheHandler
    {
        return new static;
    }

    /**
     * 拿到数据后, 包装数据再返回
     * @param mixed $params
     * @return mixed
     */
    protected function wrapData($data)
    {
        return $data;
    }

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

    // protected function dataEncode()
    // {
    //     if ($this->driver)
    // }

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
        $this->driver->set($key, $this->nullValue, $this->nullExpire);
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
            // 检查数据版本
            if (!Common::checkEmpty($data) && $data !== '' && $this->checkDataVersion($data)) {
                $this->setDataFrom($this->driverName);
                return [true, $data];
            }

            // 是否为空值
            if ($this->checkEmptyValue($data)) {
                $this->setDataFrom($this->driverName);
                return [true, null];
            }

            $lockValue = $this->locker->getLockValue($lockKey);
            if (empty($lockValue)) {
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
        return $this->expire;
    }

    public function get($keyParams = '')
    {
        $key = $this->getDriverKey($keyParams);
        $data = $this->driver->get($key);

        // 检查数据版本
        if (!Common::checkEmpty($data) && $data !== '' && $this->checkDataVersion($data)) {
            $this->setDataFrom($this->driverName);
            return $data;
        }

        // 是否为空值
        if ($this->checkEmptyValue($data)) {
            $this->setDataFrom($this->driverName);
            return null;
        }

        // 加锁等待数据
        $stack = new \SplStack();
        list ($get, $data) = $this->lockGetData($key, $keyParams, $stack);
        // var_dump($get, $data);
        if ($get) {
            return $this->wrapData($data);
        }

        $data = $this->build($keyParams);
        $this->setDataFrom('build');

        // 缓存空值
        if ($data === '' || Common::checkEmpty($data)) {
            $this->cacheEmptyValue($key);
            return null;
        }

        // 添加版本号
        $versionData = $this->addDataVersion($data);
        if ($versionData) {
            $this->driver->set($key, $versionData, $this->getExpire());
        } else {
            $this->driver->set($key, $data, $this->getExpire());
        }

        return $this->wrapData($data);
    }

}