<?php
declare(strict_types=1);


use Quhao\CacheHub\CacheHandler;

class TestCache extends CacheHandler
{

    public $key = 'test';
    public $isCacheNull = true;
    public $nullExpire = 10;
    public $nullValue = '';
    public $valueFunc;

    public function build($params)
    {
        if (empty($this->valueFunc)) {
            return '';
        }
        return call_user_func($this->valueFunc, $params);
    }

}

class TestRepeatedCache extends CacheHandler
{

    public $key = 'test';
    public $isCacheNull = true;
    public $nullExpire = 10;
    public $nullValue = '';
    public $valueFunc;

    public function build($params)
    {
        if (empty($this->valueFunc)) {
            return '';
        }
        return call_user_func($this->valueFunc, $params);
    }

}