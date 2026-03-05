# 用户模块

用户模块提供了完整的用户管理功能，包括用户认证、部门管理、权限组管理等核心功能。

## 文档索引

- [库函数文档](./lib.md) - 详细的 API 文档和使用示例
- [API 使用示例](./examples.md) - 实际代码示例和业务场景

## 功能特性

### 用户管理 (USER 类)
- ✅ 用户注册、登录、登出
- ✅ 用户信息查询和更新
- ✅ 密码加密和验证（Argon2id）
- ✅ 会话管理（基于 Redis）
- ✅ 多设备登录管理
- ✅ 角色和权限验证
- ✅ 批量操作
- ✅ 唯一性检查
- ✅ 内置缓存机制（避免重复查询）

### 部门管理 (DEPARTMENT 类)
- ✅ 部门的增删改查
- ✅ 树形结构支持
- ✅ 部门层级管理
- ✅ 部门移动
- ✅ 部门搜索
- ✅ 部门负责人设置

### 权限组管理 (AUTHORITIES 类)
- ✅ 权限组的增删改查
- ✅ 权限继承机制
- ✅ 权限组分配
- ✅ 权限组导入导出
- ✅ 权限组复制

## 设计原则

### 单例模式（动态类）
用于管理当前用户状态，通过 `USER::getInstance()` 获取唯一实例。

**适用场景**：
- 当前用户信息获取
- 登录状态校验
- 已登录设备管理
- 权限和角色验证

### 静态方法（无状态）
用于通用的用户操作，无需实例化即可调用。

**适用场景**：
- 用户查询和搜索
- 用户创建和更新
- 批量操作
- 唯一性检查
- 统计信息获取

## 快速开始

### 1. 初始化数据库表

```php
// 创建用户表
USER::createTable();

// 创建部门表
DEPARTMENT::createTable();

// 创建权限组表
AUTHORITIES::createTable();
```

### 2. 用户登录

```php
// 登录
$userInfo = USER::login('username', 'password', [
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'device_type' => 'desktop'
], false);

if ($userInfo) {
    echo "登录成功，欢迎 " . $userInfo['nickname'];
} else {
    echo "登录失败";
}
```

### 3. 获取当前用户信息

```php
// 获取当前用户实例
$user = USER::getInstance();

// 检查登录状态
if ($user->isLoggedIn()) {
    // 获取用户信息
    $userInfo = $user->getCurrentUserInfo();
    echo "当前用户: " . $userInfo['nickname'];
}
```

### 4. 创建部门

```php
// 创建根部门
$rootDeptId = DEPARTMENT::create([
    'name' => '总公司',
    'code' => 'ROOT'
]);

// 创建子部门
$childDeptId = DEPARTMENT::create([
    'name' => '技术部',
    'code' => 'TECH',
    'parent_id' => $rootDeptId
]);
```

### 5. 创建权限组

```php
// 创建权限组
$groupId = AUTHORITIES::create([
    'name' => '管理员权限组',
    'code' => 'ADMIN_GROUP',
    'description' => '系统管理员权限',
    'permissions' => [
        'user.manage',
        'department.manage',
        'system.config'
    ]
]);

// 为用户分配权限组
AUTHORITIES::assignToUser('user_id_here', $groupId);
```

## 安全特性

- 🔐 **密码加密**：使用 Argon2id 算法
- 🍪 **安全 Cookie**：HttpOnly + Secure
- 💾 **会话管理**：基于 Redis，支持过期控制
- 🔑 **权限控制**：基于 RBAC 框架
- 📝 **操作审计**：支持日志记录

## 技术栈

- **数据库**：PostgreSQL
- **缓存**：Redis
- **加密**：Argon2id
- **框架**：自研 PHP 框架
- **权限系统**：RBAC

## 相关模块

- [RBAC 权限框架](/DOC/libs/access/RBAC.md)
- [数据库操作](/DOC/libs/db/pgsql.md)
- [Redis 缓存](/DOC/libs/db/redis.md)

## 开发计划

- [ ] 多因素认证支持
- [ ] 第三方登录（微信、QQ 等）
- [ ] 审计日志系统
- [ ] 用户头像上传
- [ ] 批量导入导出用户
- [ ] 用户行为分析