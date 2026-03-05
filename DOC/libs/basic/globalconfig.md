# basic\globalconfig.php

**文件路径**: `\script\lib\basic\globalconfig.php`

## 类定义

### 类: `GLOBAL_CONFIG`

系统配置管理类
代理全局变量操作，提供统一的配置管理接口

## 函数定义

### 方法: `get`

获取配置项
@param int|string $key 配置项键名
@param mixed $default 默认值
@return mixed 配置项值

### 方法: `set`

设置配置项
! 系统配置不应该被更改
@param int|string $key 配置项键名
@param mixed $value 配置项值
