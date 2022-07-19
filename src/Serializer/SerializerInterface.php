<?php
declare(strict_types=1);

namespace Quhao\CacheHub\Serializer;

interface SerializerInterface {

    public function encode($data);

    public function decode($data);
    
}
