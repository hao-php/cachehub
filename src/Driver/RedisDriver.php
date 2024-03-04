<?php
declare(strict_types=1);

namespace Haoa\CacheHub\Driver;

use Haoa\CacheHub\Common\Common;

class RedisDriver extends BaseDriver
{

    protected bool $canLock = true;

    /** @var \Redis */
    protected $handler;

    public function get($key)
    {
        $value = $this->handler->get($key);
        if (Common::checkEmpty($value)) {
            return null;
        }
        return $this->serializer->decode($value);
    }

    public function multiGet(array $keyArr): array
    {
        $ret = $this->handler->mGet($keyArr);
        $len = count($keyArr);
        $data = [];
        for ($i = 0; $i < $len; $i++) {
            $data[$keyArr[$i]] = $this->serializer->decode($ret[$i] ?? null);
        }
        return $data;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $value = $this->serializer->encode($value);
        if (Common::checkEmpty($value)) {
            return false;
        }
        return (bool)$this->handler->setex($key, $ttl, $value);
    }

    public function multiSet(array $params, int $ttl = 0): bool
    {
        $redis = $this->handler->multi(\Redis::PIPELINE);
        foreach ($params as $key => &$v) {
            $v = $this->serializer->encode($v);
            $redis->setex($key, $ttl, $v);
        }
        $redis->exec();
        return true;

        // $ret = $this->handler->mSet($params);
        // if ($ttl > 0) {
        //     $this->multiExpire(array_keys($params), $ttl);
        // }
        // return $ret;
    }

    private function multiExpire(array $keyArr, int $ttl)
    {
//         $script = <<<LUA
// for i, key in ipairs(KEYS) do
//     redis.call('EXPIRE', key, ARGV[1])
// end
// LUA;
//         $len = count($keyArr);
//         $keyArr[] = $ttl;
//         return $this->handler->eval($script, $keyArr, $len);

        $redis = $this->handler->multi(\Redis::PIPELINE);
        foreach ($keyArr as $key) {
            $redis->expire($key, $ttl);
        }
        $redis->exec();
    }

    public function delete(string $key): bool
    {
        return (bool)$this->handler->del($key);
    }

    public function multiDelete(array $key)
    {
        return $this->handler->del(...$key);
    }

}