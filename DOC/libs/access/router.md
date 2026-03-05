# access\router.php

**文件路径**: `\script\lib\access\router.php`

## 类定义

### 类: `Router`

一个基本路由系统

## 函数定义

### 方法: `loadRouteMap`

加载路由映射

### 方法: `registerRoute`

注册 RBAC 路由（支持 <param> 形式的路径参数）

### 方法: `convertToRegex`

将路由路径（如 'exams/<exid>/info'）转换为正则表达式

### 方法: `matchUri`

匹配当前 URI 到注册的路由，并提取参数
@return string 路由标识（原始注册的 URI，如 'exams/<exid>/info'），或空字符串表示未匹配

### 方法: `getParams`

获取当前请求的所有路径参数

### 方法: `getParam`

获取指定名称的路径参数

### 方法: `getScriptPath`

获取脚本路径（支持参数化路由）
