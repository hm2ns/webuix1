<?php
/**
 * 状态存储类
 * 用于临时存储当前会话信息，类似全局变量
 * 内存存储，会话结束后自动销毁
 */
class StateStorage
{ 
    private static $storage = [];

    /**
     * 设置状态
     * @param string $key
     * @param mixed $value
     * @param string $domin 域，默认为main，可以用于区分不同模块的状态
     * @return bool 设置成功返回true
     */
    public static function set(string $key, $value,string $domin="main")
    {
        return self::$storage[$domin][$key] = $value;
    }
    /**
     * 获取状态
     * @param string $key
     * @param string $domin 域，默认为main，可以用于区分不同模块的状态
     * @return mixed 获取成功返回状态值
     */
    public static function get(string $key,string $domin="main")
    {
        return self::$storage[$domin][$key] ?? null;
    }
}