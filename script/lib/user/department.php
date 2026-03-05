<?php

/**
 * 部门管理类
 * 提供部门的创建、查询、更新、删除等功能
 */
class DEPARTMENT
{
    const TABLE_NAME = "departments"; // 部门表名

    const string KEY_ID = "id";               // 部门ID
    const string KEY_NAME = "name";           // 部门名称
    const string KEY_CODE = "code";           // 部门编码
    const string KEY_PARENT_ID = "parent_id"; // 父部门ID
    const string KEY_LEVEL = "level";         // 部门层级
    const string KEY_PATH = "path";           // 部门路径
    const string KEY_SORT = "sort";           // 排序
    const string KEY_LEADER = "leader_id";    // 部门负责人ID
    const string KEY_DESCRIPTION = "description"; // 部门描述
    const string KEY_STATUS = "status";       // 状态
    const string KEY_CREATE_TIME = "create_time"; // 创建时间
    const string KEY_UPDATE_TIME = "update_time"; // 更新时间

    const STATUS_NORMAL = "normal";   // 正常
    const STATUS_DISABLED = "disabled"; // 禁用
    const STATUS_DELETED = "deleted";   // 删除

    const string BASIC_ROWS = "id,name,code,parent_id,level,path,sort,leader_id,description,status"; // 基本信息
    const string ALL_ROWS = "*"; // 所有列

    /**
     * 创建部门表
     * 结构：
     * id: 部门ID
     * name: 部门名称
     * code: 部门编码
     * parent_id: 父部门ID
     * level: 部门层级
     * path: 部门路径
     * sort: 排序
     * leader_id: 部门负责人ID
     * description: 部门描述
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
            "parent_id" => "VARCHAR(255)",
            "level" => "INT not null default 1",
            "path" => "VARCHAR(1024)",
            "sort" => "INT not null default 0",
            "leader_id" => "VARCHAR(255)",
            "description" => "TEXT",
            "status" => "VARCHAR(16) not null default '" . self::STATUS_NORMAL . "'",
            "create_time" => "TIMESTAMP not null default CURRENT_TIMESTAMP",
            "update_time" => "TIMESTAMP not null default CURRENT_TIMESTAMP"
        ])) {
            return $db->createIndex(self::TABLE_NAME, "parent_id") &&
                $db->createIndex(self::TABLE_NAME, "code") &&
                $db->createIndex(self::TABLE_NAME, "level") &&
                $db->createIndex(self::TABLE_NAME, "status") &&
                $db->createIndex(self::TABLE_NAME, "sort");
        }
        return false;
    }

    /**
     * 创建部门
     * @param array $data 部门数据
     * @return string 部门ID
     */
    public static function create(array $data): string
    {
        $db = DB_Connections::default();

        // 设置默认值
        $data = array_merge([
            self::KEY_ID => uuidGenerator("dept_"),
            self::KEY_LEVEL => 1,
            self::KEY_SORT => 0,
            self::KEY_STATUS => self::STATUS_NORMAL
        ], $data);

        // 处理父部门逻辑
        if (!empty($data[self::KEY_PARENT_ID])) {
            $parentDept = self::getById($data[self::KEY_PARENT_ID]);
            if ($parentDept) {
                $data[self::KEY_LEVEL] = $parentDept[self::KEY_LEVEL] + 1;
                $data[self::KEY_PATH] = $parentDept[self::KEY_PATH] . '/' . $data[self::KEY_ID];
            } else {
                $data[self::KEY_PARENT_ID] = null;
                $data[self::KEY_PATH] = '/' . $data[self::KEY_ID];
            }
        } else {
            $data[self::KEY_PATH] = '/' . $data[self::KEY_ID];
        }

        return $db->insert(self::TABLE_NAME, $data, self::KEY_ID);
    }

    /**
     * 根据ID获取部门信息
     * @param string $id 部门ID
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

        return $result[0] ?? false;
    }

    /**
     * 根据编码获取部门信息
     * @param string $code 部门编码
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

        return $result[0] ?? false;
    }

    /**
     * 更新部门信息
     * @param string $id 部门ID
     * @param array $data 更新数据
     * @return bool
     */
    public static function update(string $id, array $data): bool
    {
        $db = DB_Connections::default();
        $data[self::KEY_UPDATE_TIME] = date('Y-m-d H:i:s');
        return $db->update(self::TABLE_NAME, [
            DB::whereEncoder(self::KEY_ID, "=", $id)
        ], $data);
    }

    /**
     * 删除部门
     * @param string $id 部门ID
     * @param bool $force 是否强制删除（包括子部门）
     * @return bool
     */
    public static function delete(string $id, bool $force = false): bool
    {
        $db = DB_Connections::default();

        if ($force) {
            // 递归删除所有子部门
            $children = self::getChildren($id);
            foreach ($children as $child) {
                self::delete($child[self::KEY_ID], true);
            }
        } else {
            // 检查是否有子部门
            $children = self::getChildren($id);
            if (!empty($children)) {
                return false;
            }
        }

        return $db->update(self::TABLE_NAME, [
            DB::whereEncoder(self::KEY_ID, "=", $id)
        ], [
            self::KEY_STATUS => self::STATUS_DELETED
        ]);
    }

    /**
     * 获取所有部门列表
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

        return $db->select(self::TABLE_NAME, $conditions, rows: $rows);
    }

    /**
     * 获取树形结构的部门列表
     * @param string|null $parentId 父部门ID
     * @param string $rows 返回列
     * @return array
     */
    public static function getTree(?string $parentId = null, string $rows = self::BASIC_ROWS): array
    {
        $conditions = [
            DB::whereEncoder(self::KEY_STATUS, "!=", self::STATUS_DELETED)
        ];

        if ($parentId === null) {
            $conditions[] = DB::whereEncoder(self::KEY_PARENT_ID, "IS", null);
        } else {
            $conditions[] = DB::whereEncoder(self::KEY_PARENT_ID, "=", $parentId);
        }

        $db = DB_Connections::default();
        $departments = $db->select(self::TABLE_NAME, $conditions, rows: $rows);

        foreach ($departments as &$dept) {
            $dept['children'] = self::getTree($dept[self::KEY_ID], $rows);
        }

        return $departments;
    }

    /**
     * 获取子部门
     * @param string $parentId 父部门ID
     * @param string $rows 返回列
     * @return array
     */
    public static function getChildren(string $parentId, string $rows = self::BASIC_ROWS): array
    {
        $db = DB_Connections::default();
        return $db->select(self::TABLE_NAME, [
            DB::whereEncoder(self::KEY_PARENT_ID, "=", $parentId),
            DB::whereEncoder(self::KEY_STATUS, "!=", self::STATUS_DELETED)
        ], rows: $rows);
    }

    /**
     * 获取所有子孙部门
     * @param string $parentId 父部门ID
     * @param string $rows 返回列
     * @return array
     */
    public static function getDescendants(string $parentId, string $rows = self::BASIC_ROWS): array
    {
        $db = DB_Connections::default();
        $parentDept = self::getById($parentId);
        if (!$parentDept) {
            return [];
        }

        return $db->select(self::TABLE_NAME, [
            DB::whereEncoder(self::KEY_PATH, "LIKE", $parentDept[self::KEY_PATH] . '/%'),
            DB::whereEncoder(self::KEY_STATUS, "!=", self::STATUS_DELETED)
        ], rows: $rows);
    }

    /**
     * 获取父部门
     * @param string $childId 子部门ID
     * @param string $rows 返回列
     * @return array|false
     */
    public static function getParent(string $childId, string $rows = self::BASIC_ROWS): array|false
    {
        $childDept = self::getById($childId);
        if (!$childDept || empty($childDept[self::KEY_PARENT_ID])) {
            return false;
        }

        return self::getById($childDept[self::KEY_PARENT_ID], $rows);
    }

    /**
     * 获取部门路径
     * @param string $deptId 部门ID
     * @param string $rows 返回列
     * @return array
     */
    public static function getPath(string $deptId, string $rows = self::BASIC_ROWS): array
    {
        $dept = self::getById($deptId);
        if (!$dept) {
            return [];
        }

        $path = [];
        $pathIds = array_filter(explode('/', trim($dept[self::KEY_PATH], '/')));
        foreach ($pathIds as $id) {
            $path[] = self::getById($id, $rows);
        }

        return array_filter($path);
    }

    /**
     * 移动部门
     * @param string $deptId 部门ID
     * @param string|null $newParentId 新父部门ID
     * @return bool
     */
    public static function move(string $deptId, ?string $newParentId = null): bool
    {
        $dept = self::getById($deptId, self::ALL_ROWS);
        if (!$dept) {
            return false;
        }

        // 检查是否移动到自己的子部门
        if ($newParentId) {
            $descendants = self::getDescendants($deptId);
            foreach ($descendants as $descendant) {
                if ($descendant[self::KEY_ID] === $newParentId) {
                    return false;
                }
            }
        }

        $oldPath = $dept[self::KEY_PATH];
        $oldLevel = $dept[self::KEY_LEVEL];

        if ($newParentId) {
            $newParent = self::getById($newParentId);
            if (!$newParent) {
                return false;
            }
            $newLevel = $newParent[self::KEY_LEVEL] + 1;
            $newPath = $newParent[self::KEY_PATH] . '/' . $deptId;
        } else {
            $newLevel = 1;
            $newPath = '/' . $deptId;
        }

        // 更新当前部门
        self::update($deptId, [
            self::KEY_PARENT_ID => $newParentId,
            self::KEY_LEVEL => $newLevel,
            self::KEY_PATH => $newPath
        ]);

        // 更新所有子孙部门
        $descendants = self::getDescendants($deptId, self::ALL_ROWS);
        $levelDiff = $newLevel - $oldLevel;
        $oldPathPrefix = $oldPath . '/';
        $newPathPrefix = $newPath . '/';

        foreach ($descendants as $descendant) {
            $descendantPath = $descendant[self::KEY_PATH];
            $descendantNewPath = str_replace($oldPathPrefix, $newPathPrefix, $descendantPath);
            self::update($descendant[self::KEY_ID], [
                self::KEY_LEVEL => $descendant[self::KEY_LEVEL] + $levelDiff,
                self::KEY_PATH => $descendantNewPath
            ]);
        }

        return true;
    }

    /**
     * 搜索部门
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

        return $db->select(self::TABLE_NAME, $where, rows: $rows);
    }

    /**
     * 批量更新部门状态
     * @param array $ids 部门ID数组
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
     * 获取部门统计信息
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

        // 按层级统计
        for ($i = 1; $i <= 5; $i++) {
            $result = $db->select(self::TABLE_NAME, [
                DB::whereEncoder(self::KEY_LEVEL, "=", $i),
                DB::whereEncoder(self::KEY_STATUS, "!=", self::STATUS_DELETED)
            ], rows: "COUNT(*) as count");
            $stats['level_' . $i] = $result[0]['count'] ?? 0;
        }

        return $stats;
    }

    /**
     * 检查部门编码是否存在
     * @param string $code 部门编码
     * @param string|null $excludeId 排除的部门ID
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
     * 设置部门负责人
     * @param string $deptId 部门ID
     * @param string|null $leaderId 负责人用户ID
     * @return bool
     */
    public static function setLeader(string $deptId, ?string $leaderId): bool
    {
        if ($leaderId) {
            // 验证用户存在
            $user = USER::getUserInfoById($leaderId);
            if (!$user) {
                return false;
            }
        }

        return self::update($deptId, [self::KEY_LEADER => $leaderId]);
    }
}