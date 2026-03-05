# access\request.php

**文件路径**: `\script\lib\access\request.php`

## 类定义

### 类: `REQUEST`

请求数据处理类

## 函数定义

### 方法: `method`

获取请求方法
@return string 请求方法

### 方法: `reqTree`

获取请求树
@return array 请求树

### 方法: `uri`

获取请求uri
@return string 请求uri

### 方法: `IP`

获取IP

### 方法: `cookies`

获取全部cookie

### 方法: `cookie`

获取指定cookie
@param string $name cookie名称
@param string $default 默认值
@return string cookie值

### 方法: `requests`

获取全部参数
@return array 全部参数

### 方法: `request`

获取请求参数
@param string $name 参数名称
@param string $default 默认值
@return string 参数值

### 方法: `get`

获取GET参数
@param string $name 参数名称
@param string $default 默认值
@return string 参数值

### 方法: `post`

获取POST参数
@param string $name 参数名称
@param string $default 默认值
@return string 参数值

### 方法: `file`

获取文件参数
@param string $name 文件名称
@return array 文件参数

### 方法: `session`

获取SESSION参数
@param string $name SESSION名称
@param string $default 默认值
@return string SESSION值

### 方法: `from`

获取请求来源
@return string 请求来源
