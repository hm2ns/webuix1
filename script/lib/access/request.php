<?php

/**
 * 请求数据处理类
 */
class REQUEST
{
    private static $request = [];   // 请求数据
    private static $get = [];       // GET请求数据
    private static $post = [];
    private static $method = "";    // 请求方法
    private static $uri = "";       // 请求uri
    private static $reqtree = [];   // 请求树
    private static $ip = [];        // 请求ip
    private static $cookies = [];   // 请求cookie
    private static $session = [];   // 请求SESSION
    private static $files = [];      // 请求文件
    private static $from = "";      // 请求来源

    public static function init(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ips = explode(",", $ip);

        self::$cookies = $_COOKIE;
        self::$method = $method;
        self::$ip = $ips;
        self::$request = $_REQUEST;
        self::$get = $_GET;
        self::$post = $_POST;
        self::$files = $_FILES;
        self::$session = $_SESSION ?? [];
        self::$from = $_SERVER['HTTP_REFERER'] ?? null;

        //开始拆解uri
        $uri = explode("?", $uri);
        $uri = strtolower($uri[0]);
        self::$uri = $uri;

        //拆解请求树
        $reqtree = explode("/", self::$uri);
        //去除空元素
        $reqtree = array_filter($reqtree);
        self::$reqtree = $reqtree;

        return true;
    }

    /**
     * 获取请求方法
     * @return string 请求方法
     */
    public static function method(): string
    {
        return self::$method;
    }

    /**
     * 获取请求树
     * @return array 请求树
     */
    public static function reqTree(): array
    {
        return self::$reqtree;
    }

    /**
     * 获取请求uri
     * @return string 请求uri
     */
    public static function uri(): string
    {
        return self::$uri;
    }

    /**
     * 获取IP
     */
    public static function IP(int | bool $all = false): string | array
    {

        return $all ? self::$ip : self::$ip[0];
    }
    /**
     * 获取全部cookie
     */
    public static function cookies(): array
    {
        return self::$cookies;
    }
    /**
     * 获取指定cookie
     * @param string $name cookie名称
     * @param string $default 默认值
     * @return string cookie值
     */
    public static function cookie(string $name, string $default = ""): string
    {
        return self::$cookies[$name] ?? $default;
    }

    /**
     * 获取全部参数
     * @return array 全部参数
     */
    public static function requests(): array
    {
        return self::$request;
    }

    /**
     * 获取请求参数
     * @param string $name 参数名称
     * @param string $default 默认值
     * @return string 参数值
     */
    public static function request(string $name, string $default = ""): string
    {
        return self::$request[$name] ?? $default;
    }

    /**
     * 获取GET参数
     * @param string $name 参数名称
     * @param string $default 默认值
     * @return string 参数值
     */
    public static function get(string $name, string $default = ""): string
    {
        return self::$get[$name] ?? $default;
    }

    /**
     * 获取POST参数
     * @param string $name 参数名称
     * @param string $default 默认值
     * @return string 参数值
     */
    public static function post(string $name, string $default = ""): string
    {
        return self::$post[$name] ?? $default;
    }

    /**
     * 获取文件参数
     * @param string $name 文件名称
     * @return array 文件参数
     */
    public static function file(string $name): array
    {
        return self::$files[$name] ?? [];
    }

    /**
     * 获取SESSION参数
     * @param string $name SESSION名称
     * @param string $default 默认值
     * @return string SESSION值
     */
    public static function session(string $name, string $default = ""): string
    {
        return self::$session[$name] ?? $default;
    }

    /**
     * 获取请求来源
     * @return string 请求来源
     */
    public static function from(): string
    {
        return self::$from?:"";
    }
}
