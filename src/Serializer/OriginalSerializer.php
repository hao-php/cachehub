<?php

namespace Haoa\CacheHub\Serializer;

class OriginalSerializer implements SerializerInterface
{

    public function encode($data)
    {
        return $data;
    }

    public function decode($data)
    {
        return $data;
    }

}