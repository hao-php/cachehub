<?php
declare(strict_types=1);

namespace Haoa\CacheHub\Driver;

use Haoa\CacheHub\Exception\Exception;
use Haoa\CacheHub\Serializer\SerializerInterface;

abstract class BaseDriver
{

    protected $handler;

    /** @var SerializerInterface */
    protected $serializer;

    abstract public function get($key);

    abstract public function set($key, $value, $ttl = null): bool;

    abstract public function delete(string $key): bool;

    abstract public function multiDelete(array $key);

    /** 构建最终的缓存key */
    public function buildKey(string $prefix, string $key, $keyParams = ''): string
    {
        $str = '';
        if (!empty($keyParams)) {
            if (!is_array($keyParams)) {
                $keyParams = [$keyParams];
            }
            $str = implode('_', $keyParams);
        }
        $key = $prefix . $key;
        if (!empty($str)) {
            $key .= ':' . $str;
        }
        return $key;
    }

    public function getHandler()
    {
        if (empty($this->handler)) {
            throw new Exception('handler is empty');
        }
        return $this->handler;
    }

    public function setHandler($handler)
    {
        $this->handler = $handler;
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->handler, $name], $arguments);
    }


}