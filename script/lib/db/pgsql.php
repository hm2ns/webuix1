<?php
class DB
{
    const defaultDB = "default$";
    const DRIVER_PGSQL = "pgsql";

    private $connection;

    public function __construct(string $dbname = self::defaultDB, string $host = "", string $port = "", string $user = "", string $pass = "")
    {
        try {
            $cfg = GLOBAL_CONFIG::get("db", []);
            if (empty($cfg)) {
                throw new configException("数据库配置错误");
            }
            if (empty($cfg['db_driver'])) {
                throw new configException("数据库驱动未指定");
            }
            if ($cfg['db_driver'] != self::DRIVER_PGSQL) {
                throw new configException("不支持的数据库驱动: " . $cfg['db_driver']);
            }
            $requiredKeys = ['db_host', 'db_port', 'db_name', 'db_user', 'db_pass'];
            foreach ($requiredKeys as $key) {
                if (empty($cfg[$key])) {
                    throw new configException("数据库配置项缺失: " . $key);
                }
            }
        } catch (configException $e) {
            DEBUGGER::LOG("数据库连接失败: 配置错误:" . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
            return null;
        }

        if (empty($dbname) || $dbname == self::defaultDB) {
            $dbname = $cfg['db_name'];
        }
        if (empty($host)) {
            $host = $cfg['db_host'];
        }
        if (empty($port)) {
            $port = $cfg['db_port'];
        }
        if (empty($user)) {
            $user = $cfg['db_user'];
        }
        if (empty($pass)) {
            $pass = $cfg['db_pass'];
        }

        try {
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};";
            $this->connection = new PDO($dsn, $user, $pass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            DEBUGGER::LOG("数据库连接失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }

        return $this;
    }

    /**
     * 解析表名，支持 schema.table 格式
     * @param string $table 表名，支持格式：table、schema.table、"schema"."table"
     * @return array ['schema' => schema名或空字符串, 'table' => 表名, 'full' => 完整的带schema表名（带引号）]
     * @throws InvalidArgumentException
     */
    private function parseTableName(string $table): array
    {
        if (empty($table)) {
            throw new InvalidArgumentException("表名不能为空");
        }

        // 移除可能存在的双引号
        $table = trim($table);

        // 匹配 "schema"."table" 格式
        if (preg_match('/^"([^"]+)"\."([^"]+)"$/', $table, $matches)) {
            $schema = $matches[1];
            $tableName = $matches[2];
        }
        // 匹配 schema.table 格式（无引号）
        elseif (strpos($table, '.') !== false) {
            $parts = explode('.', $table, 2);
            $schema = trim($parts[0], '"');
            $tableName = trim($parts[1], '"');
        }
        // 只有表名，无 schema
        else {
            $schema = "";
            $tableName = trim($table, '"');
        }

        // 验证 schema 和表名
        if (!empty($schema) && !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $schema)) {
            throw new InvalidArgumentException("非法 Schema 名称: {$schema}");
        }
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            throw new InvalidArgumentException("非法表名: {$tableName}");
        }

        // 构建完整的带引号的表名
        $fullTableName = '"' . $tableName . '"';
        if (!empty($schema)) {
            $fullTableName = '"' . $schema . '".' . $fullTableName;
        }

        return [
            'schema' => $schema,
            'table' => $tableName,
            'full' => $fullTableName
        ];
    }

    /**
     * 验证标识符（字段名、索引名等）
     */
    private function validateIdentifier(string $name, string $type = '字段'): void
    {
        if (empty($name)) {
            throw new InvalidArgumentException("{$type}名称不能为空");
        }
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("非法{$type}名称: {$name}");
        }
    }

    /**
     * 最基本的查询方法
     */
    public function query(string $sql, array $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            DEBUGGER::LOG("数据库查询失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 执行查询并返回所有结果
     */
    public function fetchAll(string $sql, array $params = [], int $fetchStyle = PDO::FETCH_ASSOC)
    {
        if (empty($sql)) {
            throw new InvalidArgumentException("SQL 查询语句不能为空");
        }
        if (empty($fetchStyle)) {
            $fetchStyle = PDO::FETCH_ASSOC;
        }
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll($fetchStyle);
        } catch (PDOException $e) {
            DEBUGGER::LOG("数据库查询失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 执行查询并返回第一行结果
     */
    public function fetchOne(string $sql, array $params = [], int $fetchStyle = PDO::FETCH_ASSOC)
    {
        if (empty($sql)) {
            throw new InvalidArgumentException("SQL 查询语句不能为空");
        }
        if (empty($fetchStyle)) {
            $fetchStyle = PDO::FETCH_ASSOC;
        }
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetch($fetchStyle);
        } catch (PDOException $e) {
            DEBUGGER::LOG("数据库查询失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /** 
     * 生成查询条件
     */
    public static function whereEncoder(string $field, string $operator, mixed $value): array
    {
        return [
            "field" => $field,
            "operator" => $operator,
            "value" => $value
        ];
    }

    private function whereComposer(array|string $where, int $mode = self::SELECT_MODE_AND)
    {
        if (empty($where)) {
            throw new InvalidArgumentException("查询条件必须是非空数组或字符串");
        }
        if ($mode != self::SELECT_MODE_OR && $mode != self::SELECT_MODE_AND) {
            throw new InvalidArgumentException("查询条件模式错误，必须是 OR 或 AND");
        }
        $sql = " WHERE ";
        $params = [];
        $conditions = [];
        if (is_string($where)) {
            $conditions[] = $where;
            $where = [];
        } else {
            if (isset($where['field']) && isset($where['operator']) && isset($where['value'])) {
                $where = [$where];
            }

            foreach ($where as $index => $condition) {
                if (is_string($condition)) {
                    $conditions[] = $condition;
                    continue;
                }
                if (empty($condition['field']) || empty($condition['operator']) || !isset($condition['value'])) {
                    throw new InvalidArgumentException("查询条件格式错误，必须包含 field operator value");
                }
                $paramKey = ":param{$index}";
                $conditions[] = "{$condition['field']} {$condition['operator']} {$paramKey}";
                $params[$paramKey] = $condition['value'];
            }
        }
        $sql .= implode($mode == self::SELECT_MODE_OR ? " OR " : " AND ", $conditions);
        return [$sql, $params];
    }

    const SELECT_MODE_OR = 1;
    const SELECT_MODE_AND = 2;

    /**
     * SELECT 查询
     * @param string $table 表名，支持 schema.table 格式
     * @param array $where 查询条件
     * @param int $mode 条件模式
     * @param string $rows 查询字段
     * @param int $fetchStyle 返回格式
     * @param int $limit 限制行数
     * @param int $offset 偏移量
     * @param string $orderBy 排序字段
     * @param bool $asc 是否升序
     * @return array 查询结果
     */
    public function select(
        string $table,
        array|string $where,
        int $mode = self::SELECT_MODE_AND,
        string $rows = "*",
        int $fetchStyle = PDO::FETCH_ASSOC,
        int $limit = 0,
        int $offset = 0,
        string $orderBy = "",
        bool $asc = true
    ): array {
        $tableInfo = $this->parseTableName($table);

        if (empty($rows)) {
            $rows = "*";
        }

        $sql = "SELECT {$rows} FROM {$tableInfo['full']}";
        $res = $this->whereComposer($where, $mode);
        $params = $res[1];
        $sql .= $res[0];
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy} " . ($asc ? "ASC" : "DESC");
        }
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }
        return $this->fetchAll($sql, $params, $fetchStyle);
    }

    /**
     * INSERT 插入
     * @param string $table 表名，支持 schema.table 格式
     * @param array $data 插入数据
     * @param string $returnField 返回字段
     * @return mixed 插入结果
     */
    public function insert(string $table, array $data, string $returnField = ""): mixed
    {
        if (empty($data)) {
            throw new InvalidArgumentException("插入数据不能为空");
        }

        $tableInfo = $this->parseTableName($table);

        // 验证字段名
        foreach (array_keys($data) as $field) {
            $this->validateIdentifier($field, '字段');
        }

        // 构建 SQL
        $quotedFields = array_map(fn($f) => "\"{$f}\"", array_keys($data));
        $placeholders = array_map(fn($f) => ":{$f}", array_keys($data));
        $sql = 'INSERT INTO ' . $tableInfo['full'] . ' (' . implode(', ', $quotedFields) .
            ') VALUES (' . implode(', ', $placeholders) . ')';

        // 处理 RETURNING
        $useReturning = false;
        if (!empty($returnField)) {
            $this->validateIdentifier($returnField, '返回字段');
            $sql .= ' RETURNING "' . $returnField . '"';
            $useReturning = true;
        }

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($data);

            if ($useReturning) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row[$returnField] ?? null;
            }

            return $this->connection->lastInsertId() ?: null;
        } catch (PDOException $e) {
            DEBUGGER::LOG("Insert failed: {$e->getMessage()}", DEBUGGER::DEBUG_LEVEL_WARNING);
            throw new Exception("数据库插入失败", 0, $e);
        }
    }

    /**
     * DELETE 删除
     * @param string $table 表名，支持 schema.table 格式
     * @param array $where 删除条件
     * @param int $mode 条件模式
     * @return bool 删除结果
     */
    public function delete(string $table, array|string $where, int $mode = self::SELECT_MODE_AND): bool
    {
        $tableInfo = $this->parseTableName($table);
        $res = $this->whereComposer($where, $mode);
        $sql = "DELETE FROM {$tableInfo['full']}" . $res[0];
        $params = $res[1];
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            DEBUGGER::LOG("数据库删除失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * UPDATE 更新
     * @param string $table 表名，支持 schema.table 格式
     * @param array $where 更新条件
     * @param array $data 更新数据
     * @param int $mode 条件模式
     * @return bool 更新结果
     */
    public function update(
        string $table,
        array|string $where,
        array $data,
        int $mode = self::SELECT_MODE_AND
    ): bool {
        if (empty($data) || !is_array($data)) {
            throw new InvalidArgumentException("更新数据必须是非空数组");
        }

        $tableInfo = $this->parseTableName($table);

        $setClauses = [];
        $params = [];
        foreach ($data as $field => $value) {
            $this->validateIdentifier($field, '字段');
            $paramKey = ":set_$field";
            $setClauses[] = "\"{$field}\" = $paramKey";
            $params[$paramKey] = $value;
        }
        $sql = "UPDATE {$tableInfo['full']} SET " . implode(', ', $setClauses);

        $res = $this->whereComposer($where, $mode);
        $sql .= $res[0];
        $params = array_merge($params, $res[1]);

        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            DEBUGGER::LOG("数据库更新失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * COUNT 计数
     * @param string $table 表名，支持 schema.table 格式
     * @param array $where 查询条件
     * @param int $mode 条件模式
     * @return int 行数
     */
    public function count(string $table, array|string $where, int $mode = self::SELECT_MODE_AND): int
    {
        $tableInfo = $this->parseTableName($table);
        $res = $this->whereComposer($where, $mode);
        $sql = "SELECT COUNT(*) FROM {$tableInfo['full']}" . $res[0];
        $params = $res[1];
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['count'] ?? 0);
        } catch (PDOException $e) {
            DEBUGGER::LOG("数据库查询行数失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 创建表
     * @param string $table 表名，支持 schema.table 格式
     * @param array $columns 列定义
     * @return bool 创建结果
     */
    public function createTable(string $table, array $columns): bool
    {
        if (empty($columns) || !is_array($columns)) {
            throw new InvalidArgumentException("列定义必须是非空数组");
        }

        $tableInfo = $this->parseTableName($table);

        // 构建列定义
        $columnDefs = [];
        foreach ($columns as $name => $def) {
            $this->validateIdentifier($name, '列');
            $columnDefs[] = "\"{$name}\" {$def}";
        }

        // 如果指定了 schema，先创建 schema
        if (!empty($tableInfo['schema'])) {
            $sql = 'CREATE SCHEMA IF NOT EXISTS "' . $tableInfo['schema'] . '"';
            $this->connection->exec($sql);
        }

        // 构建 CREATE TABLE SQL
        $sql = 'CREATE TABLE IF NOT EXISTS ' . $tableInfo['full'] . ' (' . implode(', ', $columnDefs) . ')';

        try {
            $this->connection->exec($sql);
            return true;
        } catch (PDOException $e) {
            DEBUGGER::LOG("数据库创建表失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 删除表
     * @param string $table 表名，支持 schema.table 格式
     * @return bool 删除结果
     */
    public function dropTable(string $table): bool
    {
        $tableInfo = $this->parseTableName($table);
        $sql = "DROP TABLE IF EXISTS {$tableInfo['full']}";
        try {
            return $this->connection->exec($sql) !== false;
        } catch (PDOException $e) {
            DEBUGGER::LOG("数据库删除表失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 检查表是否存在
     * @param string $table 表名，支持 schema.table 格式
     * @return bool 表是否存在
     */
    public function tableExists(string $table): bool
    {
        $tableInfo = $this->parseTableName($table);

        if (!empty($tableInfo['schema'])) {
            $sql = "SELECT to_regclass(:schema || '.' || :table) IS NOT NULL AS exists";
            $params = [':schema' => $tableInfo['schema'], ':table' => $tableInfo['table']];
        } else {
            $sql = "SELECT to_regclass(:table) IS NOT NULL AS exists";
            $params = [':table' => $tableInfo['table']];
        }

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && isset($result['exists']) && $result['exists'];
        } catch (PDOException $e) {
            DEBUGGER::LOG("数据库检查表存在失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 创建索引
     * @param string $table 表名，支持 schema.table 格式
     * @param string $column 列名
     * @param string $indexName 索引名称
     * @param bool $unique 是否唯一索引
     * @return bool 创建结果
     */
    public function createIndex(string $table, string $column, string $indexName = "", bool $unique = false): bool
    {
        $tableInfo = $this->parseTableName($table);
        $this->validateIdentifier($column, '列');

        if (empty($indexName)) {
            $indexName = "{$tableInfo['table']}_{$column}_" . ($unique ? "uniq" : "idx");
        }
        $this->validateIdentifier($indexName, '索引');

        $sql = "CREATE " . ($unique ? "UNIQUE " : "") . "INDEX IF NOT EXISTS \"{$indexName}\" ON {$tableInfo['full']} (\"{$column}\")";
        try {
            return $this->connection->exec($sql) !== false;
        } catch (PDOException $e) {
            DEBUGGER::LOG("数据库创建索引失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 删除索引
     * @param string $indexName 索引名称
     * @param string $schema schema 名（可选，如果索引在特定 schema 下）
     * @return bool 删除结果
     */
    public function dropIndex(string $indexName, string $schema = ""): bool
    {
        $this->validateIdentifier($indexName, '索引');

        if (!empty($schema)) {
            $this->validateIdentifier($schema, 'Schema');
            $sql = "DROP INDEX IF EXISTS \"{$schema}\".\"{$indexName}\"";
        } else {
            $sql = "DROP INDEX IF EXISTS \"{$indexName}\"";
        }

        try {
            return $this->connection->exec($sql) !== false;
        } catch (PDOException $e) {
            DEBUGGER::LOG("数据库删除索引失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
            throw $e;
        }
    }

    /**
     * 销毁连接
     */
    public function __destruct()
    {
        $this->connection = null;
    }
}

/**
 * 数据库连接管理器
 */
class DB_Connections
{
    private static $connections = [];

    public static function add(string $name, DB $connection): bool
    {
        if (empty($name) || empty($connection)) {
            throw new InvalidArgumentException("连接名称和对象不能为空");
        }
        if (isset(self::$connections[$name]) && self::$connections[$name] != null) {
            throw new InvalidArgumentException("连接名称已存在: " . $name);
        }
        self::$connections[$name] = $connection;
        return true;
    }

    public static function default(): DB
    {
        if (!isset(self::$connections['default'])) {
            self::$connections['default'] = new DB();
        }
        return self::$connections['default'];
    }

    public static function get(string $name = "default"): DB
    {
        if (empty($name)) {
            throw new InvalidArgumentException("连接名称不能为空");
        }
        if (!isset(self::$connections[$name]) || self::$connections[$name] == null) {
            throw new InvalidArgumentException("连接名称不存在: " . $name);
        }
        return self::$connections[$name];
    }
}
