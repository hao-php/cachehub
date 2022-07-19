<?php
declare(strict_types=1);

namespace Quhao\CacheHub\Driver;

use Quhao\CacheHub\Exception\Exception;
use Quhao\CacheHub\Serializer\SerializerInterface;

abstract class BaseDriver
{

    protected $handler;
    /** @var SerializerInterface */
    protected $serializer;

    /** 对于非标量数据是否要序列化 */
    // abstract public function needSerialize(): bool;

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


}