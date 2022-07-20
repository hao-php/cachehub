<?php
declare(strict_types=1);

namespace Mingle\CacheHub\Lock;

abstract class Locker {

    /**
     * @param string $key 键
     * @param mixed $value 值
     * @param int $expire 过期时间, 秒
     * @return bool
     */
    public abstract function tryLock(string $key,  $value, int $expire) : bool;

    /**
     * @param string $key 键
     * @return bool
     */
    public abstract function unLock(string $key) : bool;

    /**
     * @param string $key 键
     * @return bool
     */
    public abstract function isLocked(string $key) : bool;

    public function getLockKey($prefix, $key, $keyParams)
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
        return $key . '_lock';
    }
    
}
