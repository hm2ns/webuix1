# access\RBAC.php

**文件路径**: `\script\lib\access\RBAC.php`

## 类定义

### 类: `RBAC`

提供一个基础的RBAC权限控制框架
* 需要通过用户系统获取用户权限组或自定义权限信息
一个完整调用过程如下：
RBAC::init();
....
RBAC::check("<permission>", RBAC::defaultGroup("<groupid>"));  // 检查权限

## 函数定义

### 方法: `check`

检查权限
@param string $permission 权限名称
@param array $group 用户权限组
@return bool 是否有权限
### 方法: `defaultGroup`

获取默认权限组
@param string $groupid 权限组ID
@return array 权限组

### 方法: `mergeGroups`

合并权限组
@param array $customGroups 自定义权限组
@return array 合并后的权限组
