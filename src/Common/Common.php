<?php
declare(strict_types=1);

namespace Haoa\CacheHub\Common;

class Common
{

    public static function checkEmpty($data)
    {
        return (is_null($data) || false === $data);
    }


    /**
     * 在方法结束的时候调用$callback, 注意: 当$stack变量被销毁的时候触发回调
     * @param SplStack|null $stack
     * @param callable $callback
     * @return void
     */
    public static function stackDefer(?\SplStack &$stack, callable $callback)
    {
        $stack = $stack ?? new \SplStack();
        $stack->push(new class($callback) {

                private $callback;

                public function __construct(callable $callback)
                {
                    $this->callback = $callback;
                }

                public function __destruct()
                {
                    \call_user_func($this->callback);
                }
            }
        );
    }
}