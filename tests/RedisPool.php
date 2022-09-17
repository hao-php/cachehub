<?php

class RedisPool
{

    public function __call($name, $arguments)
    {
        $redis = Common::getRedis();
        return call_user_func_array([$redis, $name], $arguments);
    }

}