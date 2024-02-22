<?php
declare(strict_types=1);

namespace Haoa\CacheHub\Serializer;

interface SerializerInterface
{

    public function encode($data);

    public function decode($data);

}
