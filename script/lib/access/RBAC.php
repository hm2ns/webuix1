<?php

/**
 * 提供一个基础的RBAC权限控制框架
 *  * 需要通过用户系统获取用户权限组或自定义权限信息
 * 一个完整调用过程如下：
 * RBAC::init();
 * ....
 * RBAC::check("<permission>", RBAC::defaultGroup("<groupid>"));  // 检查权限
 */
class RBAC
{
    private static $defaultGroups = [];
    private static $permissions = [];

    public static function init()
    {
        if (self::$defaultGroups) {
            return;
        }
        $cfgPath = ROOTDIR . "/config/RBAC.php";
        if (file_exists($cfgPath)) {
            $config = require $cfgPath;
            self::$defaultGroups = $config['groups'] ?? [];
            self::$permissions = $config['permissions'] ?? [];
        } else {
            DEBUGGER::LOG("RBAC: No config file found.", DEBUGGER::DEBUG_LEVEL_FATAL);
        }
    }

    /**
     * 获取权限名称
     * @param string $permissionCode 权限代码
     * @return string 权限名称
     */
    public static function getPermissionName(string $permissionCode): string
    {
        if (empty(self::$permissions)) {
            self::init();
        }
        return self::$permissions[$permissionCode] ?? $permissionCode;
    }

    /**
     * 获取所有权限定义
     * @return array 权限数组
     */
    public static function getAllPermissions(): array
    {
        if (empty(self::$permissions)) {
            self::init();
        }
        return self::$permissions;
    }

    /**
     * 检查权限
     * @param string $permission 权限名称
     * @param array $group 用户权限组
     * @return bool 是否有权限
     */
    public static function check(string $permission, array $group)
    {
        return $group[$permission] ?? false;
    }

    /**
     * 获取默认权限组
     * @param string $groupid 权限组ID
     * @return array 权限组
     */
    public static function defaultGroup(string $groupid = "default")
    {
        if (empty($groupid)) {
            $groupid = "default";
        }
        if ($groupid == "default") {
            return self::$defaultGroups['default'] ?? [];
        }
        return self::mergeGroups(
            [
                self::$defaultGroups['default'] ?? [],
                self::$defaultGroups[$groupid] ?? []
            ]
        );
    }

    const MERGER_MODE_OVERWRITE = 0;  // 覆盖
    const MERGER_MODE_OR = 1; // 或
    const MERGER_MODE_AND = 2; //  与

    /**
     * 合并权限组
     * @param array $customGroups 自定义权限组
     * @return array 合并后的权限组
     */
    public static function mergeGroups(array $customGroups, int $mode = 0)
    {
        $merged = self::$defaultGroups['default'];
        foreach ($customGroups as $group => $permissions) {
            if (!is_array($permissions)) {
                DEBUGGER::LOG("RBAC: Invalid permissions for group $group.", DEBUGGER::DEBUG_LEVEL_WARNING);
                continue;
            }
            foreach ($permissions as $permission => $value) {
                switch ($mode) {
                    case self::MERGER_MODE_OVERWRITE:
                        $merged[$permission] = $value;
                        break;
                    case self::MERGER_MODE_OR:
                        if (isset($merged[$permission])) {
                            $merged[$permission] = $merged[$permission] || $value;
                        }
                        break;
                    case self::MERGER_MODE_AND:
                        if (!isset($merged[$permission])) {
                            $merged[$permission] = false;
                        }
                        $merged[$permission] = $merged[$permission] && $value;
                        break;
                }
            }
        }
        return $merged;
    }
}
