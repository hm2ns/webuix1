<?php
/**
 * 学生管理类
 * 提供学生信息的增删改查等操作功能
 * 
 * @author webUI_X Team
 * @version 1.0
 * @package script.lib
 */

REQUIRE_CLASS('DB');

class Stu
{
    /**
     * 数据库表名
     * @var string
     */
    private static string $tableName = 'student';
    
    /**
     * 数据库实例
     * @var object|null
     */
    private static object|null $dbinst = null;

    /**
     * 获取数据库实例
     * @return DB 数据库连接对象
     */
    private static function DBInstance(): DB
    {
        return DB_Connections::default();
    }

    /**
     * 初始化学生表结构
     * 建议在系统安装时调用一次
     * 
     * @return void
     * @throws PDOException 数据库操作异常
     */
    public static function initTable(): bool
    {
        try {
            $db = self::DBInstance();
            if (!$db->tableExists(self::$tableName)) {
                $db->createTable(self::$tableName, [
                    'id' => 'VARCHAR(40) PRIMARY KEY UNIQUE',
                    'name' => 'TEXT NOT NULL',
                    'xh' => 'VARCHAR(10) NOT NULL UNIQUE',
                    'class' => 'VARCHAR(5) NOT NULL',
                    'grade' => 'VARCHAR(5) NOT NULL',
                    'others' => 'JSONB'
                ]);
                $db->createIndex(self::$tableName, 'xh', 'idx_xh');
                $db->createIndex(self::$tableName, 'class', 'idx_class');
                $db->createIndex(self::$tableName, 'grade', 'idx_grade');
                $db->createIndex(self::$tableName, 'name', 'idx_name');
                
                DEBUGGER::LOG("学生表初始化完成", DEBUGGER::DEBUG_LEVEL_INFO);
                return true;
            }
            return false;
        } catch (PDOException $e) {
            DEBUGGER::LOG("学生表初始化失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
            return false;
        } catch (Exception $e) {
            DEBUGGER::LOG("学生表初始化异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
            return false;
        }
    }

    /**
     * 添加学生信息
     * 
     * @param string $name 学生姓名
     * @param string $xh 学号
     * @param string $class 班级
     * @param string $grade 年级
     * @param mixed $others 其他信息（数组或JSON字符串）
     * @param bool $forceAdd 是否强制添加（忽略学号重复检查）
     * @return int|false 成功返回插入的ID，失败返回false
     * @throws InvalidArgumentException 参数验证失败
     * @throws PDOException 数据库操作异常
     */
    public static function add(string $name, string $xh, string $class, string $grade, $others = null, bool $forceAdd = false): int|false
    {
        try {
            // 参数验证
            if (empty($name) || empty($xh) || empty($class) || empty($grade)) {
                DEBUGGER::LOG('[FATAL ERROR] 学生信息不完整 (name/xh/class/grade 不能为空)', DEBUGGER::DEBUG_LEVEL_ERROR);
                throw new InvalidArgumentException('学生信息不完整');
            }

            // 非强制添加时，检查 xh 是否已存在（业务唯一）
            if (!$forceAdd) {
                if (self::getByXh($xh) !== null) {
                    DEBUGGER::LOG("学号已存在: $xh", DEBUGGER::DEBUG_LEVEL_INFO);
                    return false;
                }
            }

            // 处理 others 参数
            if (is_array($others)) {
                $others = json_encode($others, JSON_UNESCAPED_UNICODE);
            } elseif (!is_string($others) && !is_null($others)) {
                DEBUGGER::LOG('[FATAL ERROR] others 必须为数组、JSON 字符串或 null', DEBUGGER::DEBUG_LEVEL_ERROR);
                throw new InvalidArgumentException('others 参数类型错误');
            }

            $db = self::DBInstance();
            // 显式返回 id
            $result = $db->insert(self::$tableName, [
                'id' => uuidGenerator("stu"),
                'name' => $name,
                'xh' => $xh,
                'class' => $class,
                'grade' => $grade,
                'others' => $others
            ], 'id');
            
            if ($result !== null) {
                DEBUGGER::LOG("学生添加成功，ID: $result", DEBUGGER::DEBUG_LEVEL_INFO);
                return (int)$result;
            }
            
            DEBUGGER::LOG("学生添加失败", DEBUGGER::DEBUG_LEVEL_WARNING);
            return false;
            
        } catch (PDOException $e) {
            DEBUGGER::LOG("添加学生失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        } catch (Exception $e) {
            DEBUGGER::LOG("添加学生异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 根据ID获取学生信息
     * 
     * @param int $id 学生ID
     * @return array|null 学生信息数组，不存在返回null
     * @throws InvalidArgumentException ID参数无效
     * @throws PDOException 数据库操作异常
     */
    public static function get(int $id): array|null
    {
        try {
            if ($id <= 0) {
                DEBUGGER::LOG('[FATAL ERROR] 无效的学生 ID', DEBUGGER::DEBUG_LEVEL_ERROR);
                throw new InvalidArgumentException('无效的学生ID');
            }
            
            $db = self::DBInstance();
            $result = $db->select(
                self::$tableName, 
                [['field' => 'id', 'operator' => '=', 'value' => $id]]
            );
            
            if (!empty($result)) {
                //DEBUGGER::LOG("获取学生信息成功，ID: $id", DEBUGGER::DEBUG_LEVEL_INFO);
                return $result[0];
            }
            
            //DEBUGGER::LOG("学生ID不存在: $id", DEBUGGER::DEBUG_LEVEL_INFO);
            return null;
            
        } catch (PDOException $e) {
            DEBUGGER::LOG("获取学生信息失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        } catch (Exception $e) {
            DEBUGGER::LOG("获取学生信息异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 根据学号获取学生信息（用于兼容）
     * 
     * @param string $xh 学号
     * @return array|null 学生信息数组，不存在返回null
     * @throws PDOException 数据库操作异常
     */
    public static function getByXh(string $xh): array|null
    {
        try {
            if (empty($xh)) {
                //DEBUGGER::LOG("学号为空，返回null", DEBUGGER::DEBUG_LEVEL_INFO);
                return null;
            }
            
            $db = self::DBInstance();
            $result = $db->select(
                self::$tableName,
                [['field' => 'xh', 'operator' => '=', 'value' => $xh]]
            );
            
            if (!empty($result)) {
                //DEBUGGER::LOG("根据学号获取学生信息成功，学号: $xh", DEBUGGER::DEBUG_LEVEL_INFO);
                return $result[0];
            }
            
            //DEBUGGER::LOG("学号不存在: $xh", DEBUGGER::DEBUG_LEVEL_INFO);
            return null;
            
        } catch (PDOException $e) {
            DEBUGGER::LOG("根据学号获取学生信息失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        } catch (Exception $e) {
            DEBUGGER::LOG("根据学号获取学生信息异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 根据ID更新学生信息（支持修改学号）
     * 
     * @param int $id 学生ID
     * @param string|null $name 姓名（可选）
     * @param string|null $xh 学号（可选）
     * @param string|null $class 班级（可选）
     * @param string|null $grade 年级（可选）
     * @param mixed $others 其他信息（可选）
     * @param bool $forceCover 当新学号冲突时，是否强制覆盖
     * @return bool 更新成功返回true，失败返回false
     * @throws InvalidArgumentException 参数验证失败
     * @throws PDOException 数据库操作异常
     */
    public static function update(int $id, ?string $name = null, ?string $xh = null, ?string $class = null, ?string $grade = null, $others = null, bool $forceCover = false): bool
    {
        try {
            if ($id <= 0) {
                DEBUGGER::LOG('[FATAL ERROR] 无效的学生 ID', DEBUGGER::DEBUG_LEVEL_ERROR);
                throw new InvalidArgumentException('无效的学生ID');
            }

            $current = self::get($id);
            if ($current === null) {
                DEBUGGER::LOG("[FATAL ERROR] 学生 ID 不存在: $id", DEBUGGER::DEBUG_LEVEL_ERROR);
                throw new InvalidArgumentException("学生ID不存在: $id");
            }

            $updateData = [];

            if ($name !== null) $updateData['name'] = $name;
            if ($class !== null) $updateData['class'] = $class;
            if ($grade !== null) $updateData['grade'] = $grade;

            // 处理 others
            if ($others !== null) {
                if (is_array($others)) {
                    $updateData['others'] = json_encode($others, JSON_UNESCAPED_UNICODE);
                } elseif (is_string($others)) {
                    $updateData['others'] = $others;
                } else {
                    DEBUGGER::LOG('[FATAL ERROR] others 参数类型错误', DEBUGGER::DEBUG_LEVEL_ERROR);
                    throw new InvalidArgumentException('others 参数类型错误');
                }
            }

            // 处理 xh：允许修改，但需处理冲突
            if ($xh !== null) {
                if (empty($xh)) {
                    DEBUGGER::LOG('[FATAL ERROR] 学号不能为空', DEBUGGER::DEBUG_LEVEL_ERROR);
                    throw new InvalidArgumentException('学号不能为空');
                }

                // 检查是否已有其他学生使用该 xh
                $existing = self::getByXh($xh);
                if ($existing && $existing['id'] != $id) {
                    if (!$forceCover) {
                        DEBUGGER::LOG("[FATAL ERROR] 学号已存在: $xh 且未启用 forceCover", DEBUGGER::DEBUG_LEVEL_ERROR);
                        return false;
                    }

                    // ✅ 启用 forceCover：将原占用者的 xh 改为其 id，并备份原 xh
                    $conflictId = (int)$existing['id'];
                    $oldXh = $existing['xh'];

                    // 获取原占用者的 others，合并 oldxhs[]
                    $oldOthers = json_decode($existing['others'] ?? 'null', true) ?: [];
                    if (!isset($oldOthers['oldxhs']) || !is_array($oldOthers['oldxhs'])) {
                        $oldOthers['oldxhs'] = [];
                    }
                    // 避免重复备份
                    if (!in_array($oldXh, $oldOthers['oldxhs'])) {
                        $oldOthers['oldxhs'][] = $oldXh;
                    }

                    // 将其 xh 改为自身 ID（字符串形式）
                    $newXhForConfict = (string)$conflictId;
                    $db = self::DBInstance();
                    $coverSuccess = $db->update(
                        self::$tableName,
                        [['field' => 'id', 'operator' => '=', 'value' => $conflictId]],
                        [
                            'xh' => $newXhForConfict,
                            'others' => json_encode($oldOthers, JSON_UNESCAPED_UNICODE)
                        ]
                    );

                    if (!$coverSuccess) {
                        DEBUGGER::LOG("[ERROR] forceCover 失败：无法更新冲突学生 ID $conflictId 的学号", DEBUGGER::DEBUG_LEVEL_ERROR);
                        return false;
                    }

                    DEBUGGER::LOG("[INFO] forceCover 生效：原学号 $oldXh 的学生（ID $conflictId ）已重置学号为 '$newXhForConfict'，并备份原学号", DEBUGGER::DEBUG_LEVEL_INFO);
                }

                $updateData['xh'] = $xh;
            }

            if (empty($updateData)) {
                DEBUGGER::LOG("无更新数据，返回成功", DEBUGGER::DEBUG_LEVEL_INFO);
                return true;
            }

            $db = self::DBInstance();
            $result = $db->update(
                self::$tableName,
                [['field' => 'id', 'operator' => '=', 'value' => $id]],
                $updateData
            );
            
            if ($result) {
                DEBUGGER::LOG("学生信息更新成功，ID: $id", DEBUGGER::DEBUG_LEVEL_INFO);
            } else {
                DEBUGGER::LOG("学生信息更新失败，ID: $id", DEBUGGER::DEBUG_LEVEL_WARNING);
            }
            
            return $result;
            
        } catch (PDOException $e) {
            DEBUGGER::LOG("更新学生信息失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        } catch (Exception $e) {
            DEBUGGER::LOG("更新学生信息异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 根据ID删除学生
     * 
     * @param int $id 学生ID
     * @return bool 删除成功返回true，失败返回false
     * @throws InvalidArgumentException ID参数无效
     * @throws PDOException 数据库操作异常
     */
    public static function delete(int $id): bool
    {
        try {
            if ($id <= 0) {
                DEBUGGER::LOG('[FATAL ERROR] 无效的学生 ID', DEBUGGER::DEBUG_LEVEL_ERROR);
                throw new InvalidArgumentException('无效的学生ID');
            }
            
            $db = self::DBInstance();
            $result = $db->delete(
                self::$tableName,
                [['field' => 'id', 'operator' => '=', 'value' => $id]]
            );
            
            if ($result) {
                DEBUGGER::LOG("学生删除成功，ID: $id", DEBUGGER::DEBUG_LEVEL_INFO);
            } else {
                DEBUGGER::LOG("学生删除失败，ID: $id", DEBUGGER::DEBUG_LEVEL_WARNING);
            }
            
            return $result;
            
        } catch (PDOException $e) {
            DEBUGGER::LOG("删除学生失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        } catch (Exception $e) {
            DEBUGGER::LOG("删除学生异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 批量删除学生（通过ID列表）
     * 
     * @param array $ids 学生ID数组
     * @return bool 删除成功返回true，失败返回false
     * @throws PDOException 数据库操作异常
     */
    public static function batchDeleteByIds(array $ids): bool
    {
        try {
            if (empty($ids)) {
                DEBUGGER::LOG("ID列表为空，无需删除", DEBUGGER::DEBUG_LEVEL_INFO);
                return true;
            }

            // 过滤有效 id
            $ids = array_filter($ids, fn($id) => is_numeric($id) && $id > 0);
            if (empty($ids)) {
                DEBUGGER::LOG("无有效ID，批量删除失败", DEBUGGER::DEBUG_LEVEL_WARNING);
                return false;
            }

            // 构建删除条件
            $conditions = [];
            foreach ($ids as $id) {
                $conditions[] = ['field' => 'id', 'operator' => '=', 'value' => $id];
            }

            $db = self::DBInstance();
            $result = $db->delete(self::$tableName, $conditions, DB::SELECT_MODE_OR);
            
            if ($result) {
                DEBUGGER::LOG("批量删除学生成功，数量: " . count($ids), DEBUGGER::DEBUG_LEVEL_INFO);
            } else {
                DEBUGGER::LOG("批量删除学生失败", DEBUGGER::DEBUG_LEVEL_WARNING);
            }
            
            return $result;
            
        } catch (PDOException $e) {
            DEBUGGER::LOG("批量删除学生失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        } catch (Exception $e) {
            DEBUGGER::LOG("批量删除学生异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }
    
    /**
     * 批量获取学生（通过ID列表）
     * 
     * @param array $ids 学生ID数组
     * @return array 学生信息数组
     * @throws PDOException 数据库操作异常
     */
    public static function batchGetByIds(array $ids): array
    {
        try {
            if (empty($ids)) {
                DEBUGGER::LOG("ID列表为空，返回空数组", DEBUGGER::DEBUG_LEVEL_INFO);
                return [];
            }

            // 去重并过滤空值
            $ids = array_values(array_filter(array_unique($ids)));
            if (empty($ids)) {
                DEBUGGER::LOG("无有效ID，返回空数组", DEBUGGER::DEBUG_LEVEL_INFO);
                return [];
            }

            // 构建查询条件
            $conditions = [];
            foreach ($ids as $id) {
                $conditions[] = ['field' => 'id', 'operator' => '=', 'value' => $id];
            }

            $db = self::DBInstance();
            $students = $db->select(self::$tableName, $conditions, DB::SELECT_MODE_OR);
            
            //DEBUGGER::LOG("批量获取学生成功，数量: " . count($students), DEBUGGER::DEBUG_LEVEL_INFO);
            return $students;
            
        } catch (PDOException $e) {
            DEBUGGER::LOG("批量获取学生失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        } catch (Exception $e) {
            DEBUGGER::LOG("批量获取学生异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 批量获取学生（通过学号列表）
     * 
     * @param array $xhs 学号数组
     * @return array 以学号为键的学生信息映射数组
     * @throws PDOException 数据库操作异常
     */
    public static function batchGetByXhs(array $xhs): array
    {
        try {
            if (empty($xhs)) {
                DEBUGGER::LOG("学号列表为空，返回空数组", DEBUGGER::DEBUG_LEVEL_INFO);
                return [];
            }

            // 去重并过滤空值
            $xhs = array_values(array_filter(array_unique($xhs)));
            if (empty($xhs)) {
                DEBUGGER::LOG("无有效学号，返回空数组", DEBUGGER::DEBUG_LEVEL_INFO);
                return [];
            }

            // 构建查询条件
            $conditions = [];
            foreach ($xhs as $xh) {
                $conditions[] = ['field' => 'xh', 'operator' => '=', 'value' => $xh];
            }

            $db = self::DBInstance();
            $students = $db->select(self::$tableName, $conditions, DB::SELECT_MODE_OR);

            // 转为xh索引的映射
            $result = [];
            foreach ($students as $student) {
                $result[$student['xh']] = $student;
            }
            
            //DEBUGGER::LOG("批量获取学生（学号）成功，数量: " . count($result), DEBUGGER::DEBUG_LEVEL_INFO);
            return $result;
            
        } catch (PDOException $e) {
            DEBUGGER::LOG("批量获取学生（学号）失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        } catch (Exception $e) {
            DEBUGGER::LOG("批量获取学生（学号）异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 查询所有学生（支持多条件过滤）
     * 
     * @param array $filters 过滤条件数组
     * @param int $limit 限制数量，-1表示不限制
     * @param int $offset 偏移量
     * @return array 学生信息数组
     * @throws PDOException 数据库操作异常
     */
    public static function getAll(array $filters = [], int $limit = -1, int $offset = 0): array
    {
        try {
            $conditions = [];
            
            if (!empty($filters['id']) && is_numeric($filters['id']) && $filters['id'] > 0) {
                $conditions[] = ['field' => 'id', 'operator' => '=', 'value' => $filters['id']];
            }
            if (!empty($filters['grade'])) {
                $conditions[] = ['field' => 'grade', 'operator' => '=', 'value' => $filters['grade']];
            }
            if (!empty($filters['class'])) {
                $conditions[] = ['field' => 'class', 'operator' => '=', 'value' => $filters['class']];
            }
            if (!empty($filters['name'])) {
                $conditions[] = ['field' => 'name', 'operator' => 'LIKE', 'value' => '%' . $filters['name'] . '%'];
            }

            // 如果没有条件，使用1=1作为基础条件
            if (empty($conditions)) {
                $conditions = "1=1";
            }

            $db = self::DBInstance();
            $students = $db->select(self::$tableName, $conditions, DB::SELECT_MODE_AND, "*", PDO::FETCH_ASSOC, $limit, $offset);
            
            //DEBUGGER::LOG("获取所有学生成功，数量: " . count($students), DEBUGGER::DEBUG_LEVEL_INFO);
            return $students;
            
        } catch (PDOException $e) {
            DEBUGGER::LOG("获取所有学生失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        } catch (Exception $e) {
            DEBUGGER::LOG("获取所有学生异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 获取学生总数
     * 
     * @param string $grade 年级筛选条件
     * @param string $class 班级筛选条件
     * @param string $name 姓名模糊搜索条件
     * @return int 学生总数
     * @throws PDOException 数据库操作异常
     */
    public static function getCount(string $grade = "", string $class = "", string $name = ""): int
    {
        try {
            $conditions = [];
            
            if ($grade !== "") {
                $conditions[] = ['field' => 'grade', 'operator' => '=', 'value' => $grade];
            }
            if ($class !== "") {
                $conditions[] = ['field' => 'class', 'operator' => '=', 'value' => $class];
            }
            if ($name !== "") {
                $conditions[] = ['field' => 'name', 'operator' => 'LIKE', 'value' => '%' . $name . '%'];
            }

            // 如果没有条件，使用1=1作为基础条件
            if (empty($conditions)) {
                $conditions = "1=1";
            }

            $db = self::DBInstance();
            // 使用select方法获取所有记录然后计算数量
            $students = $db->select(self::$tableName, $conditions);
            $count = count($students);
            
            //DEBUGGER::LOG("获取学生总数成功: $count", DEBUGGER::DEBUG_LEVEL_INFO);
            return $count;
            
        } catch (PDOException $e) {
            DEBUGGER::LOG("获取学生总数失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        } catch (Exception $e) {
            DEBUGGER::LOG("获取学生总数异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 搜索学生（支持多个字段）
     * 
     * @param string $keyword 搜索关键词
     * @param array $fields 搜索字段数组，默认['name', 'xh', 'class', 'grade']
     * @param int $limit 限制数量，-1表示不限制
     * @param int $offset 偏移量
     * @return array 搜索结果数组
     * @throws PDOException 数据库操作异常
     */
    public static function search(string $keyword, array $fields = ['name', 'xh', 'class', 'grade'], int $limit = -1, int $offset = 0): array
    {
        try {
            if (empty($keyword)) {
                //DEBUGGER::LOG("搜索关键词为空，返回空数组", DEBUGGER::DEBUG_LEVEL_INFO);
                return [];
            }

            $allowedFields = ['name', 'xh', 'class', 'grade'];
            $conditions = [];
            
            foreach ($fields as $field) {
                if (in_array($field, $allowedFields)) {
                    $conditions[] = ['field' => $field, 'operator' => 'LIKE', 'value' => '%' . $keyword . '%'];
                }
            }

            if (empty($conditions)) {
                //DEBUGGER::LOG("无有效搜索字段，返回空数组", DEBUGGER::DEBUG_LEVEL_INFO);
                return [];
            }

            $db = self::DBInstance();
            $students = $db->select(self::$tableName, $conditions, DB::SELECT_MODE_OR, "*", PDO::FETCH_ASSOC, $limit, $offset);
            
            //DEBUGGER::LOG("学生搜索成功，关键词: $keyword, 结果数量: " . count($students), DEBUGGER::DEBUG_LEVEL_INFO);
            return $students;
            
        } catch (PDOException $e) {
            DEBUGGER::LOG("学生搜索失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        } catch (Exception $e) {
            DEBUGGER::LOG("学生搜索异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 批量添加学生（用于导入）
     * 
     * @param array $students 学生数据数组
     * @param bool $forceAdd 是否强制添加
     * @return array 包含成功数、失败数和已存在学号的统计信息
     * @throws PDOException 数据库操作异常
     */
    public static function batchAdd(array $students, bool $forceAdd = false): array
    {
        try {
            if (empty($students)) {
                DEBUGGER::LOG("学生数据为空", DEBUGGER::DEBUG_LEVEL_INFO);
                return ['success' => 0, 'failed' => 0, 'existing' => []];
            }

            $toInsert = [];
            $existingXhs = [];

            foreach ($students as $stu) {
                if (!isset($stu['name'], $stu['xh'], $stu['class'], $stu['grade'])) {
                    continue;
                }

                $name = trim($stu['name']);
                $xh = trim($stu['xh']);
                $class = trim($stu['class']);
                $grade = trim($stu['grade']);
                $others = $stu['others'] ?? null;

                if (empty($name) || empty($xh) || empty($class) || empty($grade)) {
                    continue;
                }

                if (is_array($others)) {
                    $others = json_encode($others, JSON_UNESCAPED_UNICODE);
                }
                
                // 检查 xh 是否已存在
                $extid = self::getByXh($xh);
                if ($extid !== null) {
                    $existingXhs[] = $xh;
                    if ($forceAdd == false) {
                        continue;
                    } else {
                        $extid['xh'] = $extid['id'];
                        if (!self::update($extid['id'], xh: $extid['xh'])) {
                            continue;
                        }
                    }
                } else {
                    $toInsert[] = [
                        'id' => uuidGenerator("stu"),
                        'name' => $name,
                        'xh' => $xh,
                        'class' => $class,
                        'grade' => $grade,
                        'others' => $others
                    ];
                }
            }

            $success = 0;
            if (!empty($toInsert)) {
                $db = self::DBInstance();
                // 批量插入实现需要在PgSQLDB中添加对应方法
                foreach ($toInsert as $student) {
                    $result = $db->insert(self::$tableName, $student,'id');
                    if ($result !== null) {
                        $success++;
                    }
                }
            }

            $result = [
                'success' => $success,
                'failed' => count($toInsert) - $success,
                'existing' => $existingXhs
            ];
            
            DEBUGGER::LOG("批量添加学生完成 - 成功: {$result['success']}, 失败: {$result['failed']}, 已存在: " . count($result['existing']), DEBUGGER::DEBUG_LEVEL_INFO);
            return $result;
            
        } catch (PDOException $e) {
            DEBUGGER::LOG("批量添加学生失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        } catch (Exception $e) {
            DEBUGGER::LOG("批量添加学生异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }
    
    /**
     * 批量更新学生信息（支持通过id或xh定位）
     * 
     * @param array $updates 更新数据数组，每项必须包含'id'或'xh'，以及要更新的字段
     * @param bool $forceCover 是否强制更新
     * @return array 包含成功数、失败数和错误信息的统计数组
     * @throws PDOException 数据库操作异常
     */
    public static function batchUpdate(array $updates, bool $forceCover = false): array
    {
        try {
            if (empty($updates)) {
                DEBUGGER::LOG("更新数据为空", DEBUGGER::DEBUG_LEVEL_INFO);
                return ['success' => 0, 'failed' => 0, 'errors' => []];
            }

            $success = 0;
            $failed = 0;
            $errors = [];

            foreach ($updates as $i => $update) {
                // 必须提供 id 或 xh 之一用于定位
                $id = $update['id'] ?? null;
                $xh = $update['xh'] ?? null;

                if ($id === null && $xh === null) {
                    $errors[] = "第 " . ($i + 1) . " 条：缺少 id 或 xh 用于定位学生";
                    $failed++;
                    continue;
                }

                // 先根据条件获取当前学生 id
                $targetId = null;
                if ($id !== null) {
                    if (!is_numeric($id) || $id <= 0) {
                        $errors[] = "第 " . ($i + 1) . " 条：无效的 id";
                        $failed++;
                        continue;
                    }
                    $stu = self::get((int)$id);
                    if ($stu === null) {
                        $errors[] = "第 " . ($i + 1) . " 条：ID $id 的学生不存在";
                        $failed++;
                        continue;
                    }
                    $targetId = (int)$id;
                } else {
                    // 通过 xh 查询
                    if (empty($xh) || !is_string($xh)) {
                        $errors[] = "第 " . ($i + 1) . " 条：xh 无效";
                        $failed++;
                        continue;
                    }
                    $stu = self::getByXh($xh);
                    if ($stu === null) {
                        $errors[] = "第 " . ($i + 1) . " 条：学号 $xh 不存在";
                        $failed++;
                        continue;
                    }
                    $targetId = (int)$stu['id'];
                }

                // 提取更新字段
                $name = $update['name'] ?? null;
                $newXh = $update['newxh'] ?? null; // 注意：此处的 xh 是要修改成的新学号
                $class = $update['class'] ?? null;
                $grade = $update['grade'] ?? null;
                $others = $update['others'] ?? null;
                $thisForceCover = $update['forceCover'] ?? $forceCover;
                
                // 执行更新
                $result = self::update($targetId, $name, $newXh, $class, $grade, $others, $thisForceCover);
                if ($result) {
                    $success++;
                } else {
                    $failed++;
                    $errors[] = "第 " . ($i + 1) . " 条：更新失败（可能因新学号冲突等）";
                }
            }

            $result = [
                'success' => $success,
                'failed' => $failed,
                'errors' => $errors
            ];
            
            DEBUGGER::LOG("批量更新学生完成 - 成功: {$result['success']}, 失败: {$result['failed']}", DEBUGGER::DEBUG_LEVEL_INFO);
            return $result;
            
        } catch (PDOException $e) {
            DEBUGGER::LOG("批量更新学生失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        } catch (Exception $e) {
            DEBUGGER::LOG("批量更新学生异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 根据学号计算年级
     * 
     * @param string $xh 学号
     * @return string 年级
     */
    public static function calcGrade(string $xh): string
    {
        return substr($xh, 0, 4);
    }
    
    /**
     * 根据学号计算班级
     * 
     * @param string $xh 学号
     * @return string 班级
     */
    public static function calcClass(string $xh): string
    {
        return substr($xh, 4, 2);
    }
    /**
     * 校验学号是否合法
     * @param string $xh 学号
     * @return bool 是否合法
     */
    public static function validateXh(string $xh): bool
    {
        return preg_match('/^[GJ]\d{6}$/', $xh) || preg_match('/^\d{8}$/', $xh); 
    }

}