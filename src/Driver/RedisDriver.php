<?php
declare(strict_types=1);

namespace Haoa\CacheHub\Driver;

use Haoa\CacheHub\Common\Common;
use Haoa\CacheHub\Exception\Exception;

class RedisDriver extends BaseDriver
{

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

    public function set($key, $value, $ttl = null): bool
    {
        $value = $this->serializer->encode($value);
        if (Common::checkEmpty($value)) {
            return false;
        }
        return (bool)$this->handler->setex($key, $ttl, $value);
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