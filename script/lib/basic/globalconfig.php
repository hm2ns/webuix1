<?php

/**
 * 系统配置管理类
 * 代理全局变量操作，提供统一的配置管理接口
 */
class GLOBAL_CONFIG
{
    private static $config = [];

    /**
     * 获取配置项
     * @param int|string $key 配置项键名
     * @param mixed $default 默认值
     * @return mixed 配置项值
     */
    public static function get(int | string $key,mixed $default = null):mixed
    {
        return self::$config[$key] ?? $default;
    }

    /**
     * 设置配置项
     * ! 系统配置不应该被更改
     * @param int|string $key 配置项键名
     * @param mixed $value 配置项值
     */
    public static function set(int | string $key,mixed $value)
    {
        self::$config[$key] = $value;
        throw new Exception("系统配置不应该被更改");
        return self::$config[$key];
    }

    public static function init():bool{
        $cfgPath = ROOTDIR."config/system.php";
        if (!file_exists($cfgPath)){
            return false;
        }
        $config = require_once $cfgPath;
        self::$config = $config;
        return true;
    }

    public static function is_dev():bool{
        return self::get("dev_mode",false);
    }
}