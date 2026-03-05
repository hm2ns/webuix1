<?php

/**
 * 安全控制模块
 * IP黑名单控制，防止IP访问
 * CSRF保护
 * API TOKEN 控制
 */

class SECURITY
{
    private static $cfg;
    private static $ipbanlist = [];
    private static $refererlist = [];
    private static $apiTokens = [];
    const ALLOW_ALL = ["*"];

    public static function init()
    {
        $cfgPath = ROOTDIR . "config/security.php";
        if (!file_exists($cfgPath)) {
            DEBUGGER::LOG("[ERROR] 找不到安全配置文件 $cfgPath", DEBUGGER::DEBUG_LEVEL_FATAL);
            return false;
        }
        self::$cfg = require_once $cfgPath;

        foreach (self::$cfg['banIP'] as $ip) {
            //检测地址形式
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                DEBUGGER::LOG("[ERROR] 安全配置文件中的IP地址 $ip 格式不正确", DEBUGGER::DEBUG_LEVEL_ERROR);
                return false;
            }
            self::$ipbanlist[$ip] = true;
        }

        foreach (self::$cfg['allowedReferers'] as $referer) {
            if ($referer == "*") {
                self::$refererlist = self::ALLOW_ALL;
                break;
            }
            self::$refererlist[$referer] = true;
        }

        foreach (self::$cfg['apiTokens'] as $appname => $token) {
            self::$apiTokens[$appname] = $token;
        }

        return true;
    }

    public static function isIPBanned(array|string $ips = "")
    {
        if ($ips === "") {
            $ips = REQUEST::IP(true);
        }
        if (!is_array($ips)) {
            $ips = [$ips];
        }
        if (count($ips) > 10) {
            DEBUGGER::LOG("[WARN] TOO MANY JUMPS", DEBUGGER::DEBUG_LEVEL_WARNING);
            return true;
        }
        foreach ($ips as $ip) {
            if (isset(self::$ipbanlist[$ip])) {
                return true;
            }
        }
        return false;
    }

    public static function refererCheck(string $referer = "")
    {
        if ($referer == "") {
            $referer = REQUEST::from();
        }
        if ($referer == "") {
            return true;
        }
        if (self::$refererlist === self::ALLOW_ALL) {
            return true;
        }
        return self::$refererlist[$referer] ?? false;
    }

    public static function check()
    {
        return self::refererCheck() && !self::isIPBanned();
    }

    public static function apiTokenCheck(string $appname, string $publicKey, string $hover, int $reqtime)
    {
        if (!isset(self::$apiTokens[$appname])) {
            throw new Exception("Invalid appname");
            return false;
        }
        if ($reqtime > time() || $reqtime < time() - 10) {
            throw new Exception("Invalid request time");
            return false;
        }
        $apitoken = self::$apiTokens[$appname];

        if ($apitoken['publicKey'] != $publicKey) {
            throw new Exception("Invalid public key");
            return false;
        }

        if ($apitoken['eol'] < time()) {
            throw new Exception("Token expired");
            return false;
        }

        $realHover = hash_hmac("sha256", $apitoken['publicKey'] . $apitoken['privateKey'], $reqtime);

        if ($realHover != $hover) {
            throw new Exception("Invalid hover");
            return false;
        }
        return true;
    }
}
