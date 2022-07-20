<?php
declare(strict_types=1);

namespace Mingle\CacheHub\Serializer;

class Json implements SerializerInterface
{


    public function encode($data)
    {
        return is_scalar($data) ? $data : 'cachehub_json:' . json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function decode($data)
    {
        if (empty($data)) {
            return $data;
        }
        $result = (0 === strpos($data, 'cachehub_json:')) ? json_decode(substr($data, 14), true) : $data;
        return $result;
    }
}