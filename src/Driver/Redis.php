<?php
declare(strict_types=1);

namespace Mingle\CacheHub\Driver;

use Mingle\CacheHub\Common\Common;
use Mingle\CacheHub\Exception\Exception;

class Redis extends BaseDriver
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

    public function set($key, $value, $expire = null)
    {
        $value = $this->serializer->encode($value);
        if (Common::checkEmpty($value)) {
            return false;
        }
        return $this->handler->setex($key, $expire, $value);
    }
}