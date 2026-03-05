# db\pgsql.php

**文件路径**: `\script\lib\db\pgsql.php`

## 类定义

### 类: `DB`

动态类，需要先new

### 类:`DB_Connections`

*用于保存到数据库的连接对象，支持多连接管理，可以根据需要扩展功能，如连接池等

## 函数定义($DB->)

### 方法: `query`

最基本的查询方法，返回PDOStatement对象，调用者可以根据需要进行fetch等操作

### 方法: `fetchAll`

执行查询并返回所有结果，默认以关联数组形式返回

### 方法: `fetchOne`

执行查询并返回第一行结果，默认以关联数组形式返回，如果没有结果则返回false

### 方法: `select`

一个简单的select方法，接受表名和条件，返回查询结果，实际使用时可以根据需要扩展参数，如字段列表、排序等
@param string $table 表名
@param array $where 查询条件，二维数组，每个条件包含字段、操作符和值，如[['field'=>'id','operator'=>'=','value'=>1],['field'=>'name','operator'=>'LIKE','value'=>'%test%']]
@param int $mode 条件模式，OR AND
@param string $rows 查询字段，默认为*表示所有字段，可以是逗号分隔的字段列表
@param int $fetchStyle 查询结果的返回格式，默认为PDO::FETCH_ASSOC，可以是PDO的其他常量，如PDO::FETCH_NUM等
@return array 查询结果，默认以关联数组形式返回

### 方法: `insert`

一个简单的insert方法，接受表名和数据，返回插入结果，实际使用时可以根据需要扩展参数，如返回字段等
@param string $table 表名
@param array $data 插入数据，关联数组，键为字段名，值为字段值，如['id'=>1,'name'=>'test']
@param string $returnField 返回字段，默认为空表示不返回，可以指定一个字段名，如主键id，插入成功后会返回该字段的值
@return mixed 插入结果，如果指定了返回字段，则返回该字段的值，否则返回true表示插入成功，false表示插入失败

### 方法: `delete`

一个简单的delete方法，接受表名和条件，返回删除结果，实际使用时可以根据需要扩展参数，如删除限制等
@param string $table 表名
@param array $where 删除条件，二维数组，每个条件包含字段、操作符和值，如[['field'=>'id','operator'=>'=','value'=>1],['field'=>'name','operator'=>'LIKE','value'=>'%test%']]
@param int $mode 条件模式，OR AND
@return bool 删除结果，true表示删除成功，false表示删除失败

### 方法: `update`

一个简单的update方法，接受表名、条件和数据，返回更新结果，实际使用时可以根据需要扩展参数，如更新限制等
@param string $table 表名
@param array $where 更新条件，二维数组，每个条件包含字段、操作符和值，如[['field'=>'id','operator'=>'=','value'=>1],['field'=>'name','operator'=>'LIKE','value'=>'%test%']]
@param array $data 更新数据，关联数组，键为字段名，值为字段值，如['name'=>'newname']
@param int $mode 条件模式，OR AND
@return bool 更新结果，true表示更新成功，false表示更新失败

### 方法: `createTable`

创建表
@param string $table 表名
@param array $columns 列定义，关联数组，键为列名，值为数据类型和约束，如['id'=>'SERIAL PRIMARY KEY','name'=>'VARCHAR(255) NOT NULL']
@return bool 创建结果，true表示创建成功（含表已存在情况），异常表示失败
@throws InvalidArgumentException 表名或列定义非法
@throws PDOException SQL执行失败

### 方法: `dropTable`

删除表（Drop）
@param string $table 表名
@return bool 删除结果，true表示删除成功，false表示删除失败
注意：删除表会丢失所有数据，请谨慎使用

### 方法: `tableExists`

检查表是否存在

### 方法: `createIndex`

创建索引
@param string $table 表名
@param string $column 列名
@param string $indexName 索引名称，默认为空表示自动生成，建议指定以便管理
@param bool $unique 是否唯一索引，默认为false表示普通索引，true表示唯一索引
@return bool 创建结果，true表示创建成功，false表示创建失败

### 方法: `dropIndex`

删除索引
@param string $indexName 索引名称
@return bool 删除结果，true表示删除成功，false表示删除失败

### 方法: `__destruct`

销毁连接
在PHP中，PDO对象会在脚本结束时自动销毁并关闭连接，但你也可以显式地销毁它来释放资源



