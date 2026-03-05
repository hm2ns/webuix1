<?php

/**
 * 权限组管理类
 * 提供权限组的创建、查询、更新、删除等功能
 * 基于 RBAC 框架实现
 */
class AUTHORITIES
{
    const TABLE_NAME = "authority_groups"; // 权限组表名

    const string KEY_ID = "id";               // 权限组ID
    const string KEY_NAME = "name";           // 权限组名称
    const string KEY_CODE = "code";           // 权限组编码
    const string KEY_DESCRIPTION = "description"; // 描述
    const string KEY_PERMISSIONS = "permissions"; // 权限列表（JSON）
    const string KEY_BASE_GROUP = "base_group"; // 基础权限组（继承）
    const string KEY_SORT = "sort";           // 排序
    const string KEY_STATUS = "status";       // 状态
    const string KEY_CREATE_TIME = "create_time"; // 创建时间
    const string KEY_UPDATE_TIME = "update_time"; // 更新时间

    const STATUS_NORMAL = "normal";   // 正常
    const STATUS_DISABLED = "disabled"; // 禁用
    const STATUS_DELETED = "deleted";   // 删除

    const string BASIC_ROWS = "id,name,code,description,base_group,sort,status"; // 基本信息
    const string ALL_ROWS = "*"; // 所有列

    /**
     * 创建权限组表
     * 结构：
     * id: 权限组ID
     * name: 权限组名称
     * code: 权限组编码
     * description: 描述
     * permissions: 权限列表（JSONB）
     * base_group: 基础权限组（继承）
     * sort: 排序
     * status: 状态
     * create_time: 创建时间
     * update_time: 更新时间
     * @return bool
     */
    public static function createTable(): bool
    {
        $db = DB_Connections::default();
        if ($db->createTable(self::TABLE_NAME, [
            "id" => "VARCHAR(255) primary key not null",
            "name" => "VARCHAR(255) not null",
            "code" => "VARCHAR(64) unique",
            "description" => "TEXT",
            "permissions" => "JSONB",
            "base_group" => "VARCHAR(255)",
            "sort" => "INT not null default 0",
            "status" => "VARCHAR(16) not null default '" . self::STATUS_NORMAL . "'",
            "create_time" => "TIMESTAMP not null default CURRENT_TIMESTAMP",
            "update_time" => "TIMESTAMP not null default CURRENT_TIMESTAMP"
        ])) {
            return $db->createIndex(self::TABLE_NAME, "code") &&
                $db->createIndex(self::TABLE_NAME, "base_group") &&
                $db->createIndex(self::TABLE_NAME, "status") &&
                $db->createIndex(self::TABLE_NAME, "sort");
        }
        return false;
    }

    /**
     * 创建权限组
     * @param array $data 权限组数据
     * @return string 权限组ID
     */
    public static function create(array $data): string
    {
        $db = DB_Connections::default();

        // 设置默认值
        $data = array_merge([
            self::KEY_ID => uuidGenerator("auth_"),
            self::KEY_SORT => 0,
            self::KEY_STATUS => self::STATUS_NORMAL,
            self::KEY_PERMISSIONS => []
        ], $data);

        // 编码权限列表
        if (isset($data[self::KEY_PERMISSIONS]) && is_array($data[self::KEY_PERMISSIONS])) {
            $data[self::KEY_PERMISSIONS] = json_encode($data[self::KEY_PERMISSIONS]);
        }

        return $db->insert(self::TABLE_NAME, $data, self::KEY_ID);
    }

    /**
     * 根据ID获取权限组信息
     * @param string $id 权限组ID
     * @param string $rows 返回列
     * @return array|false
     */
    public static function getById(string $id, string $rows = self::BASIC_ROWS): array|false
    {
        $db = DB_Connections::default();
        $result = $db->select(self::TABLE_NAME, [
            DB::whereEncoder(self::KEY_ID, "=", $id),
            DB::whereEncoder(self::KEY_STATUS, "!=", self::STATUS_DELETED)
        ], rows: $rows);

        if (empty($result)) {
            return false;
        }

        $group = $result[0];
        return self::decodeGroup($group);
    }

    /**
     * 根据编码获取权限组信息
     * @param string $code 权限组编码
     * @param string $rows 返回列
     * @return array|false
     */
    public static function getByCode(string $code, string $rows = self::BASIC_ROWS): array|false
    {
        $db = DB_Connections::default();
        $result = $db->select(self::TABLE_NAME, [
            DB::whereEncoder(self::KEY_CODE, "=", $code),
            DB::whereEncoder(self::KEY_STATUS, "!=", self::STATUS_DELETED)
        ], rows: $rows);

        if (empty($result)) {
            return false;
        }

        $group = $result[0];
        return self::decodeGroup($group);
    }

    /**
     * 更新权限组信息
     * @param string $id 权限组ID
     * @param array $data 更新数据
     * @return bool
     */
    public static function update(string $id, array $data): bool
    {
        $db = DB_Connections::default();
        $data[self::KEY_UPDATE_TIME] = date('Y-m-d H:i:s');

        // 编码权限列表
        if (isset($data[self::KEY_PERMISSIONS]) && is_array($data[self::KEY_PERMISSIONS])) {
            $data[self::KEY_PERMISSIONS] = json_encode($data[self::KEY_PERMISSIONS]);
        }

        return $db->update(self::TABLE_NAME, [
            DB::whereEncoder(self::KEY_ID, "=", $id)
        ], $data);
    }

    /**
     * 删除权限组
     * @param string $id 权限组ID
     * @param bool $force 是否强制删除（检查是否有用户使用）
     * @return bool
     */
    public static function delete(string $id, bool $force = false): bool
    {
        if (!$force) {
            // 检查是否有用户使用此权限组
            $db = DB_Connections::default();
            $result = $db->select(USER::TABLE_NAME, [
                DB::whereEncoder("role", "=", $id)
            ], rows: "COUNT(*) as count");

            if (($result[0]['count'] ?? 0) > 0) {
                return false;
            }
        }

        $db = DB_Connections::default();
        return $db->update(self::TABLE_NAME, [
            DB::whereEncoder(self::KEY_ID, "=", $id)
        ], [
            self::KEY_STATUS => self::STATUS_DELETED
        ]);
    }

    /**
     * 获取所有权限组列表
     * @param string $rows 返回列
     * @param array $where 额外查询条件
     * @return array
     */
    public static function getAll(string $rows = self::BASIC_ROWS, array $where = []): array
    {
        $db = DB_Connections::default();

        $conditions = [
            DB::whereEncoder(self::KEY_STATUS, "!=", self::STATUS_DELETED),
            ...$where
        ];

        $result = $db->select(self::TABLE_NAME, $conditions, rows: $rows);

        return array_map(function($group) {
            return self::decodeGroup($group);
        }, $result);
    }

    /**
     * 搜索权限组
     * @param string $keyword 关键词
     * @param array $fields 搜索字段
     * @param string $rows 返回列
     * @return array
     */
    public static function search(string $keyword, array $fields = ['name', 'code', 'description'], string $rows = self::BASIC_ROWS): array
    {
        $db = DB_Connections::default();

        $where = [
            DB::whereEncoder(self::KEY_STATUS, "!=", self::STATUS_DELETED)
        ];

        foreach ($fields as $field) {
            $where[] = DB::whereEncoder($field, "LIKE", "%$keyword%", "OR");
        }

        $result = $db->select(self::TABLE_NAME, $where, rows: $rows);

        return array_map(function($group) {
            return self::decodeGroup($group);
        }, $result);
    }

    /**
     * 批量更新权限组状态
     * @param array $ids 权限组ID数组
     * @param string $status 状态
     * @return bool
     */
    public static function batchUpdateStatus(array $ids, string $status): bool
    {
        $db = DB_Connections::default();
        return $db->update(self::TABLE_NAME, [
            DB::whereEncoder(self::KEY_ID, "IN", $ids)
        ], [
            self::KEY_STATUS => $status,
            self::KEY_UPDATE_TIME => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * 解码权限组信息
     * @param array $group 权限组信息
     * @return array
     */
    private static function decodeGroup(array $group): array
    {
        if (isset($group[self::KEY_PERMISSIONS]) && is_string($group[self::KEY_PERMISSIONS])) {
            $group[self::KEY_PERMISSIONS] = json_decode($group[self::KEY_PERMISSIONS], true);
        }
        return $group;
    }

    /**
     * 获取权限组的完整权限（包含继承的基础权限组）
     * @param string $id 权限组ID
     * @return array
     */
    public static function getFullPermissions(string $id): array
    {
        $group = self::getById($id, self::ALL_ROWS);
        if (!$group) {
            return [];
        }

        $permissions = $group[self::KEY_PERMISSIONS] ?? [];

        // 如果有基础权限组，合并权限
        if (!empty($group[self::KEY_BASE_GROUP])) {
            $basePermissions = self::getFullPermissions($group[self::KEY_BASE_GROUP]);
            $permissions = array_merge($basePermissions, $permissions);
        }

        return $permissions;
    }

    /**
     * 为用户分配权限组
     * @param string $userId 用户ID
     * @param string|array $groupIds 权限组ID或ID数组
     * @return bool
     */
    public static function assignToUser(string $userId, string|array $groupIds): bool
    {
        $user = USER::getUserInfoById($userId);
        if (!$user) {
            return false;
        }

        if (!is_array($groupIds)) {
            $groupIds = [$groupIds];
        }

        // 获取所有权限组的权限
        $allPermissions = [];
        foreach ($groupIds as $groupId) {
            $permissions = self::getFullPermissions($groupId);
            $allPermissions = array_merge($allPermissions, $permissions);
        }

        return USER::updateUser([
            USER::KEY_ID => $userId,
            USER::KEY_AUTHORITY => array_unique($allPermissions)
        ]);
    }

    /**
     * 检查权限组编码是否存在
     * @param string $code 权限组编码
     * @param string|null $excludeId 排除的权限组ID
     * @return bool
     */
    public static function codeExists(string $code, ?string $excludeId = null): bool
    {
        $db = DB_Connections::default();

        $where = [DB::whereEncoder(self::KEY_CODE, "=", $code)];

        if ($excludeId) {
            $where[] = DB::whereEncoder(self::KEY_ID, "!=", $excludeId);
        }

        $result = $db->select(self::TABLE_NAME, $where, rows: "COUNT(*) as count");
        return ($result[0]['count'] ?? 0) > 0;
    }

    /**
     * 获取权限组统计信息
     * @return array
     */
    public static function getStats(): array
    {
        $db = DB_Connections::default();

        $stats = [];

        // 总数
        $result = $db->select(self::TABLE_NAME, [
            DB::whereEncoder(self::KEY_STATUS, "!=", self::STATUS_DELETED)
        ], rows: "COUNT(*) as total");
        $stats['total'] = $result[0]['total'] ?? 0;

        // 按状态统计
        foreach ([self::STATUS_NORMAL, self::STATUS_DISABLED] as $status) {
            $result = $db->select(self::TABLE_NAME, [
                DB::whereEncoder(self::KEY_STATUS, "=", $status)
            ], rows: "COUNT(*) as count");
            $stats[strtolower($status)] = $result[0]['count'] ?? 0;
        }

        return $stats;
    }

    /**
     * 复制权限组
     * @param string $sourceId 源权限组ID
     * @param string $newName 新名称
     * @return string|false 新权限组ID
     */
    public static function copy(string $sourceId, string $newName): string|false
    {
        $sourceGroup = self::getById($sourceId, self::ALL_ROWS);
        if (!$sourceGroup) {
            return false;
        }

        $newData = [
            self::KEY_NAME => $newName,
            self::KEY_CODE => $sourceGroup[self::KEY_CODE] . '_copy_' . time(),
            self::KEY_DESCRIPTION => $sourceGroup[self::KEY_DESCRIPTION] ?? '',
            self::KEY_PERMISSIONS => $sourceGroup[self::KEY_PERMISSIONS] ?? [],
            self::KEY_BASE_GROUP => $sourceGroup[self::KEY_BASE_GROUP] ?? null
        ];

        return self::create($newData);
    }

    /**
     * 导入权限组
     * @param array $groups 权限组数组
     * @return array 导入结果 ['success' => [], 'failed' => []]
     */
    public static function import(array $groups): array
    {
        $result = [
            'success' => [],
            'failed' => []
        ];

        foreach ($groups as $group) {
            try {
                $id = self::create($group);
                $result['success'][] = $id;
            } catch (Exception $e) {
                $result['failed'][] = [
                    'group' => $group,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $result;
    }

    /**
     * 导出权限组
     * @param array $groupIds 权限组ID数组
     * @return array
     */
    public static function export(array $groupIds): array
    {
        $groups = [];
        foreach ($groupIds as $id) {
            $group = self::getById($id, self::ALL_ROWS);
            if ($group) {
                unset($group[self::KEY_ID]); // 移除ID
                unset($group[self::KEY_CREATE_TIME]); // 移除创建时间
                unset($group[self::KEY_UPDATE_TIME]); // 移除更新时间
                $groups[] = $group;
            }
        }
        return $groups;
    }

    /**
     * 根据角色查找对应的权限组（通过编码匹配）
     * @param string $role 用户角色
     * @return array|false 权限组信息，如果未找到返回false
     */
    public static function getByRole(string $role): array|false
    {
        // 使用角色作为权限组编码查找
        return self::getByCode($role, self::ALL_ROWS);
    }

    /**
     * 获取用户的完整权限（包含自动匹配角色权限组）
     * @param array $user 用户信息
     * @return array 权限数组
     */
    public static function getUserFullPermissions(array $user): array
    {
        $authority = $user[USER::KEY_AUTHORITY] ?? [];

        // 如果权限为空（即没有任何键值对），尝试通过ROLE自动匹配权限组
        if (empty($authority) || !is_array($authority)) {
            $role = $user[USER::KEY_ROLE] ?? '';

            // 如果有角色，尝试查找对应的权限组
            if (!empty($role)) {
                $authGroup = self::getByRole($role);
                if ($authGroup) {
                    // 获取权限组的完整权限（包含继承的基础权限组）
                    $authority = self::getFullPermissions($authGroup[self::KEY_ID]);
                }
            }
        }

        return $authority;
    }
}
