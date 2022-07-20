<?php
declare(strict_types=1);

namespace Mingle\CacheHub\Driver;

use Mingle\CacheHub\Exception\Exception;
use Mingle\CacheHub\Serializer\SerializerInterface;

abstract class BaseDriver
{

    protected $handler;
    /** @var SerializerInterface */
    protected $serializer;

    abstract public function get($key);

    abstract public function set($key, $value, $expire = null);

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