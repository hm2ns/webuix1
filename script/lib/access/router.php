<?php

/**
 * 一个基本路由系统
 */

class Router
{
    const MODULE_NONE = 'none';
    const MODULE_LOGINED = 'none_LOGINED';
    const MODULE_NOT_LOGINED = 'none_NOT_LOGINED';

    private static $RBACPermissions = [];
    private static $usrlogined = false;
    private static $routes = [];
    private static $currentParams = []; // 用于存储当前匹配到的参数
    private static $routePatterns = []; // 用于存储路由的正则表达式

    /**
     * 加载路由映射
     */
    public static function loadRouteMap(): void
    {
        self::$routes = [];
        $path = ROOTDIR . 'config/route.php';
        if (!file_exists($path)) {
            DEBUGGER::LOG("No route config file found.", DEBUGGER::DEBUG_LEVEL_FATAL);
            return;
        }
        $routeConfig = require_once ROOTDIR . 'config/route.php';

        if (empty($routeConfig) || !is_array($routeConfig)) {
            return;
        }

        foreach ($routeConfig as $route) {
            if (count($route) >= 2) {
                $uri = $route[0];
                $script = $route[1];
                $moudleID = $route[2] ?? self::MODULE_NONE;
                self::registerRoute($uri, $script, $moudleID);
            }
        }
    }

    public static function init($usrgroups = [], $logined = false)
    {
        if (!empty(self::$routes)) return;
        RBAC::init();
        if (empty($usrgroups)) {
            $usrgroups = RBAC::defaultGroup();
        }
        self::$RBACPermissions = $usrgroups;
        self::$usrlogined = $logined;
        self::loadRouteMap();
    }
    /**
     * 注册 RBAC 路由（支持 <param> 形式的路径参数）
     */
    public static function registerRoute(string $uri, string $script, string $moudleID): void
    {
        $originalUri = trim($uri, '/');
        $originalUri = $originalUri === '' ? '/' : $originalUri;

        self::$routes[$originalUri] = [
            'script' => $script,
            'moudleID' => $moudleID,
        ];

        // 生成正则表达式用于匹配带参数的路径
        $pattern = self::convertToRegex($originalUri);
        self::$routePatterns[] = [
            'original' => $originalUri,
            'pattern' => $pattern,
            'isParametric' => strpos($originalUri, '<') !== false
        ];
    }

    /**
     * 将路由路径（如 'exams/<exid>/info'）转换为正则表达式
     */
    private static function convertToRegex(string $route): string
    {
        // 转义特殊字符，但保留 <...> 作为参数占位符
        $route = preg_quote($route, '#');
        // 替换 <name> 为捕获组 (?<name>[^/]+)
        $route = preg_replace('#\\\\<([^>]+)\\\\>#', '(?<$1>[^/]+)', $route);
        return '#^' . $route . '$#';
    }

    /**
     * 匹配当前 URI 到注册的路由，并提取参数
     * @return string 路由标识（原始注册的 URI，如 'exams/<exid>/info'），或空字符串表示未匹配
     */
    public static function matchUri(string $uri): string
    {
        if($uri[0]=="/" && strlen($uri)>1){
            $uri = substr($uri, 1);
        }
        self::$currentParams = []; // 重置参数

        // 先尝试精确匹配（无参数路由）
        if (isset(self::$routes[$uri])) {
            return $uri;
        }

        // 再尝试参数化路由
        foreach (self::$routePatterns as $item) {
            if (!$item['isParametric']) continue;
            if (preg_match($item['pattern'], $uri, $matches)) {
                // 提取命名捕获组（PHP 7.0+）
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        self::$currentParams[$key] = $value;
                    }
                }
                return $item['original'];
            }
        }

        return ''; // 未匹配
    }

    /**
     * 获取当前请求的所有路径参数
     */
    public static function getParams(): array
    {
        return self::$currentParams;
    }

    /**
     * 获取指定名称的路径参数
     */
    public static function getParam(string $name, $default = null)
    {
        return self::$currentParams[$name] ?? $default;
    }

    /**
     * 获取脚本路径（支持参数化路由）
     */
    public static function getScriptPath(string $rawUri): string | bool
    {
        $matchedRoute = self::matchUri($rawUri);
        if ($matchedRoute === '') {
            UI_ERRORPAGES::show(404);
            return false;
        }

        $route = self::$routes[$matchedRoute];
        $permission = $route['moudleID'];

        if($permission == self::MODULE_NOT_LOGINED && !self::$usrlogined){
            return $route['script'];
        }

        if (
            $permission === self::MODULE_NONE ||
            ($permission === self::MODULE_LOGINED && self::$usrlogined) ||
            RBAC::check($permission, self::$RBACPermissions)
        ) {
            return $route['script'];
        }

        if ($permission === self::MODULE_LOGINED && !self::$usrlogined) {
            return "user/login";
        }

        if ($permission[0]=="#" && !self::$usrlogined){ //带有#的权限组，则表示需要登录
            return "user/login";
        }

        UI_ERRORPAGES::show(403);
        return false;
    }
}
