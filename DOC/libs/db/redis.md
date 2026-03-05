# db\redis.php

**文件路径**: `\script\lib\db\redis.php`

## 类定义

### 类: `RC`

Redis数据库类

## 函数定义

### 方法: `select`

选择Redis表，表范围由配置文件中的offset和baserange参数决定
@param int $table 表编号，必须在配置的范围内
@return bool 成功返回true，失败抛出异常

### 方法: `get`

获取指定键的值，如果键不存在则返回null

### 方法: `set`

设置指定键的值，如果键已经存在则覆盖原值

### 方法: `del`

删除指定键，如果键不存在则返回false，成功删除返回true

### 方法: `exists`

检查指定键是否存在，存在返回true，不存在返回false

### 方法: `save`

保存当前数据库状态到磁盘，成功返回true，失败返回false

### 方法: `list`

列出当前数据库中所有键，支持通配符规则，默认返回所有键
