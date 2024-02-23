<?php
declare(strict_types=1);


use Haoa\CacheHub\CacheHandler;
use Haoa\CacheHub\Driver\ApcuDriver;
use Haoa\CacheHub\Driver\RedisDriver;
use Haoa\CacheHub\Serializer\JsonSerializer;
use Haoa\CacheHub\Serializer\OriginalSerializer;

class TestCache extends CacheHandler
{

    public $key = 'test';
    public $isCacheNull = true;
    public $nullValue = '';
    public $valueFunc;
    public $wrapFunc;

    public $ttl = 60;

    public $nullTtl = 60;

    public function build($params)
    {
        if (empty($this->valueFunc)) {
            return '';
        }
        return call_user_func($this->valueFunc, $params);
    }

    public function wrapData($data)
    {
        if (empty($this->wrapFunc)) {
            return $data;
        }
        return call_user_func($this->wrapFunc, $data);
    }

    protected function getCacheList(): array
    {
        return [
            // [
            //     'driver' => ApcuDriver::class,
            //     'serializer' => OriginalSerializer::class, // default
            //     'null_ttl' => 5,
            //     'ttl' => 5,
            // ],
            [
                'driver' => RedisDriver::class,
                'driver_handler' => new RedisPool(),
                'serializer' => JsonSerializer::class,
                'ttl' => $this->ttl,
                'null_ttl' => $this->nullTtl,
            ],
        ];
    }
}

class TestCache2 extends CacheHandler
{

    public $key = 'test';
    public $isCacheNull = true;
    public $nullValue = '';
    public $valueFunc;
    public $wrapFunc;

    public $multiBuildFunc;

    public $ttl = 60;

    public $nullTtl = 60;

    public function build($params)
    {
        if (empty($this->valueFunc)) {
            return '';
        }
        return call_user_func($this->valueFunc, $params);
    }

    public function wrapData($data)
    {
        if (empty($this->wrapFunc)) {
            return $data;
        }
        return call_user_func($this->wrapFunc, $data);
    }

    public function multiBuild(array $params): array
    {
        return call_user_func($this->multiBuildFunc, $params);
    }

    protected function getCacheList(): array
    {
        return [
            [
                'driver' => ApcuDriver::class,
                'serializer' => OriginalSerializer::class, // default
                'null_ttl' => 5,
                'ttl' => 5,
            ],
            [
                'driver' => RedisDriver::class,
                'driver_handler' => new RedisPool(),
                'serializer' => JsonSerializer::class,
                'ttl' => $this->ttl,
                'null_ttl' => $this->nullTtl,
            ],
        ];
    }
}

class TestRepeatedCache extends CacheHandler
{

    public $key = 'test';
    public $isCacheNull = true;
    public $nullValue = '';
    public $valueFunc;

    protected function getCacheList(): array
    {
        return [
            [
                'driver' => ApcuDriver::class,
                'serializer' => OriginalSerializer::class, // default
                'null_ttl' => 5,
                'ttl' => 5,
            ],
            [
                'driver' => RedisDriver::class,
                'driver_handler' => null,
                'serializer' => JsonSerializer::class,
                'ttl' => 300,
                'null_ttl' => 60,
            ],
        ];
    }

    public function build($params)
    {
        if (empty($this->valueFunc)) {
            return '';
        }
        return call_user_func($this->valueFunc, $params);
    }

}