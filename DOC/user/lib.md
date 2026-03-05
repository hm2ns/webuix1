# 用户模块开发文档

## 概述

用户模块提供了完整的用户管理功能，包括用户认证、部门管理、权限组管理等核心功能。模块采用面向对象设计，支持动态类和静态类两种调用方式，以满足不同的使用场景。

## 目录结构

```
script/lib/user/
├── user.php          # 用户管理核心类
├── department.php    # 部门管理类
└── authorities.php   # 权限组管理类
```

## 核心概念

### 设计模式

用户模块采用两种设计模式：

1. **单例模式（动态类）**：用于管理当前用户状态，通过 `USER::getInstance()` 获取唯一实例
2. **静态方法（无状态）**：用于通用的用户操作，无需实例化即可调用

### 数据库表

#### users 表

用户信息表，存储用户基本信息和认证信息。

| 字段 | 类型 | 说明 |
|------|------|------|
| id | VARCHAR(255) | 用户ID（主键）|
| username | VARCHAR(255) | 用户名（唯一）|
| password | VARCHAR(255) | 密码（加密）|
| email | VARCHAR(255) | 邮箱（唯一）|
| phone | VARCHAR(32) | 手机号（唯一）|
| nickname | VARCHAR(64) | 昵称 |
| department | VARCHAR(64) | 所属部门 |
| role | VARCHAR(64) | 角色 |
| authority | JSONB | 权限列表 |
| status | VARCHAR(16) | 状态 |
| profile | JSONB | 个人简介 |
| create_time | TIMESTAMP | 创建时间 |
| update_time | TIMESTAMP | 更新时间 |
| binding_stuid | BIGINT | 绑定学号 |
| binding_grade | VARCHAR(5) | 绑定年级 |
| binding_class | VARCHAR(5) | 绑定班级 |

#### departments 表

部门信息表，支持树形结构。

| 字段 | 类型 | 说明 |
|------|------|------|
| id | VARCHAR(255) | 部门ID（主键）|
| name | VARCHAR(255) | 部门名称 |
| code | VARCHAR(64) | 部门编码（唯一）|
| parent_id | VARCHAR(255) | 父部门ID |
| level | INT | 部门层级 |
| path | VARCHAR(1024) | 部门路径 |
| sort | INT | 排序 |
| leader_id | VARCHAR(255) | 部门负责人ID |
| description | TEXT | 部门描述 |
| status | VARCHAR(16) | 状态 |
| create_time | TIMESTAMP | 创建时间 |
| update_time | TIMESTAMP | 更新时间 |

#### authority_groups 表

权限组表，基于 RBAC 框架。

| 字段 | 类型 | 说明 |
|------|------|------|
| id | VARCHAR(255) | 权限组ID（主键）|
| name | VARCHAR(255) | 权限组名称 |
| code | VARCHAR(64) | 权限组编码（唯一）|
| description | TEXT | 描述 |
| permissions | JSONB | 权限列表 |
| base_group | VARCHAR(255) | 基础权限组（继承）|
| sort | INT | 排序 |
| status | VARCHAR(16) | 状态 |
| create_time | TIMESTAMP | 创建时间 |
| update_time | TIMESTAMP | 更新时间 |

## USER 类

### 常量定义

```php
// 用户状态
const STATUS_NORMAL = "normal";     // 正常
const STATUS_DISABLED = "disabled"; // 禁用
const STATUS_DELETED = "deleted";   // 删除
const STATUS_BANNED = "banned";     // 封禁

// 用户角色
const array ROLES = [
    "ROOT" => "ROOT",
    "ADMIN" => "系统管理员",
    "MANAGER" => "管理员",
    "TEACHER" => "教师",
    "STUDENT" => "学生",
];

// 数据行定义
const string BASIC_ROWS = "id,username,nickname,department,role,authority,status,profile";
const string PASS_ROWS = "id,username,password,status";
const string ALL_ROWS = "*";
```

### 静态方法（通用操作）

#### 用户查询

```php
// 根据ID获取用户信息
$userInfo = USER::getUserInfoById('usr_xxx123', USER::BASIC_ROWS);

// 根据用户名获取用户信息
$userInfo = USER::getUserInfoByUsername('admin', USER::BASIC_ROWS);

// 根据邮箱获取用户信息
$userInfo = USER::getUserInfoByEmail('admin@example.com', USER::BASIC_ROWS);

// 根据手机号获取用户信息
$userInfo = USER::getUserInfoByPhone('13800138000', USER::BASIC_ROWS);
```

#### 用户管理

```php
// 创建用户
$userId = USER::createUser([
    'username' => 'newuser',
    'password' => 'password123',
    'nickname' => '新用户',
    'email' => 'newuser@example.com',
    'role' => 'STUDENT'
]);

// 更新用户信息
USER::updateUser([
    'id' => 'usr_xxx123',
    'nickname' => '新昵称',
    'email' => 'newemail@example.com'
]);

// 删除用户（软删除）
USER::deleteUser('usr_xxx123');
```

#### 用户认证

```php
// 用户登录
$userInfo = USER::login('username', 'password', [
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'device_type' => 'desktop'
], false);

// 验证密码
$isValid = USER::verifyPassword('username', 'password');
```

#### 批量操作

```php
// 根据角色查询用户
$users = USER::getUsersByRole(['ADMIN', 'MANAGER'], 100, 0);

// 根据部门查询用户
$users = USER::getUsersByDepartment('IT部门', 100, 0);

// 搜索用户
$users = USER::searchUsers('关键词', ['username', 'nickname', 'email'], 100, 0);

// 批量更新用户状态
USER::batchUpdateStatus(['usr_001', 'usr_002'], USER::STATUS_DISABLED);
```

#### 唯一性检查

```php
// 检查用户名是否存在
$exists = USER::usernameExists('username', null);

// 检查邮箱是否存在
$exists = USER::emailExists('email@example.com', 'usr_xxx123');

// 检查手机号是否存在
$exists = USER::phoneExists('13800138000', null);
```

### 动态方法（当前用户状态管理）

#### 获取实例

```php
// 获取当前用户实例
$user = USER::getInstance();
```

#### 会话管理

```php
// 检查是否已登录
if ($user->isLoggedIn()) {
    // 用户已登录
}

// 获取当前用户ID
$userId = $user->getCurrentUserId();

// 获取当前用户信息（使用缓存）
$userInfo = $user->getCurrentUserInfo(USER::BASIC_ROWS);
// 第二次查询将从缓存获取，避免重复查询

// 获取当前用户信息（不使用缓存）
$userInfo = $user->getCurrentUserInfo(USER::BASIC_ROWS, false);

// 获取会话信息
$sessionInfo = $user->getSessionInfo();

// 刷新会话
$user->refreshSession(SECONDS_PER_DAY * 7);

// 登出
$user->logout();
```

#### 权限验证

```php
// 检查权限
if ($user->hasPermission('user.manage')) {
    // 有管理用户的权限
}

// 检查角色
if ($user->hasRole(['ADMIN', 'MANAGER'])) {
    // 是管理员
}
```

#### 用户操作

```php
// 更新当前用户信息（会自动清除缓存）
$user->updateCurrentUser([
    'nickname' => '新昵称',
    'profile' => ['age' => 25]
]);

// 修改密码
$user->changePassword('oldPassword', 'newPassword');

// 手动清除用户信息缓存
$user->clearUserInfoCache();
$user->clearUserInfoCache(USER::ALL_ROWS); // 只清除指定类型的缓存
```

#### 缓存管理

```php
// getCurrentUserInfo 默认使用缓存，避免重复查询
$userInfo1 = $user->getCurrentUserInfo(USER::BASIC_ROWS); // 从数据库查询
$userInfo2 = $user->getCurrentUserInfo(USER::BASIC_ROWS); // 从缓存获取

// 需要获取最新数据时，禁用缓存
$userInfo = $user->getCurrentUserInfo(USER::BASIC_ROWS, false);

// 更新数据后自动清除缓存
$user->updateCurrentUser(['nickname' => '新昵称']); // 自动清除缓存
// 下一次调用 getCurrentUserInfo 将从数据库重新查询
```

#### 设备管理

```php
// 获取所有登录设备
$devices = $user->getLoginDevices();

// 踢出指定设备
$user->logoutDevice('device_token_xxx');

// 踢出其他设备
$count = $user->logoutOtherDevices();
```

## DEPARTMENT 类

### 常量定义

```php
// 部门状态
const STATUS_NORMAL = "normal";   // 正常
const STATUS_DISABLED = "disabled"; // 禁用
const STATUS_DELETED = "deleted";   // 删除

// 数据行定义
const string BASIC_ROWS = "id,name,code,parent_id,level,path,sort,leader_id,description,status";
const string ALL_ROWS = "*";
```

### 基本操作

```php
// 创建部门表
DEPARTMENT::createTable();

// 创建部门
$deptId = DEPARTMENT::create([
    'name' => '技术部',
    'code' => 'TECH',
    'description' => '技术研发部门'
]);

// 获取部门信息
$dept = DEPARTMENT::getById('dept_xxx123');
$dept = DEPARTMENT::getByCode('TECH');

// 更新部门
DEPARTMENT::update('dept_xxx123', [
    'name' => '新部门名称',
    'description' => '新描述'
]);

// 删除部门
DEPARTMENT::delete('dept_xxx123', false);
DEPARTMENT::delete('dept_xxx123', true); // 强制删除（包括子部门）
```

### 树形结构操作

```php
// 获取所有部门
$departments = DEPARTMENT::getAll();

// 获取树形结构
$tree = DEPARTMENT::getTree();

// 获取指定父部门的子部门
$tree = DEPARTMENT::getTree('dept_parent_123');

// 获取子部门
$children = DEPARTMENT::getChildren('dept_parent_123');

// 获取所有子孙部门
$descendants = DEPARTMENT::getDescendants('dept_parent_123');

// 获取父部门
$parent = DEPARTMENT::getParent('dept_child_123');

// 获取部门路径
$path = DEPARTMENT::getPath('dept_child_123');
```

### 部门移动

```php
// 移动部门
DEPARTMENT::move('dept_xxx123', 'dept_new_parent_123');
DEPARTMENT::move('dept_xxx123', null); // 移到根目录
```

### 搜索和统计

```php
// 搜索部门
$departments = DEPARTMENT::search('技术', ['name', 'code', 'description']);

// 获取统计信息
$stats = DEPARTMENT::getStats();

// 批量更新状态
DEPARTMENT::batchUpdateStatus(['dept_001', 'dept_002'], DEPARTMENT::STATUS_DISABLED);
```

### 其他操作

```php
// 检查编码是否存在
$exists = DEPARTMENT::codeExists('TECH', null);

// 设置部门负责人
DEPARTMENT::setLeader('dept_xxx123', 'usr_leader_123');
```

## AUTHORITIES 类

### 常量定义

```php
// 权限组状态
const STATUS_NORMAL = "normal";   // 正常
const STATUS_DISABLED = "disabled"; // 禁用
const STATUS_DELETED = "deleted";   // 删除

// 数据行定义
const string BASIC_ROWS = "id,name,code,description,base_group,sort,status";
const string ALL_ROWS = "*";
```

### 基本操作

```php
// 创建权限组表
AUTHORITIES::createTable();

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

// 获取权限组
$group = AUTHORITIES::getById('auth_xxx123');
$group = AUTHORITIES::getByCode('ADMIN_GROUP');

// 更新权限组
AUTHORITIES::update('auth_xxx123', [
    'name' => '新名称',
    'permissions' => ['user.view', 'user.edit']
]);

// 删除权限组
AUTHORITIES::delete('auth_xxx123', false);
AUTHORITIES::delete('auth_xxx123', true); // 强制删除
```

### 权限管理

```php
// 获取完整权限（包含继承）
$permissions = AUTHORITIES::getFullPermissions('auth_xxx123');

// 为用户分配权限组
AUTHORITIES::assignToUser('usr_xxx123', 'auth_group_001');
AUTHORITIES::assignToUser('usr_xxx123', ['auth_group_001', 'auth_group_002']);
```

### 搜索和统计

```php
// 搜索权限组
$groups = AUTHORITIES::search('管理', ['name', 'code', 'description']);

// 获取所有权限组
$groups = AUTHORITIES::getAll();

// 获取统计信息
$stats = AUTHORITIES::getStats();

// 批量更新状态
AUTHORITIES::batchUpdateStatus(['auth_001', 'auth_002'], AUTHORITIES::STATUS_DISABLED);
```

### 导入导出

```php
// 复制权限组
$newGroupId = AUTHORITIES::copy('auth_source_123', '副本名称');

// 导出权限组
$exportData = AUTHORITIES::export(['auth_001', 'auth_002']);

// 导入权限组
$result = AUTHORITIES::import($exportData);
// $result = ['success' => [...], 'failed' => [...]]
```

### 唯一性检查

```php
// 检查编码是否存在
$exists = AUTHORITIES::codeExists('ADMIN_GROUP', null);
```

## 最佳实践

### 1. 用户认证流程

```php
// 登录
if (USER::login($username, $password, $deviceInfo, $rememberMe)) {
    // 登录成功，重定向到首页
    header('Location: /');
} else {
    // 登录失败
    echo '用户名或密码错误';
}

// 页面中检查登录状态
$user = USER::getInstance();
if (!$user->isLoggedIn()) {
    // 未登录，跳转到登录页
    header('Location: /login');
    exit;
}
```

### 2. 权限验证

```php
// 使用 RBAC 框架检查权限
RBAC::init();

// 获取用户权限组
$userInfo = $user->getCurrentUserInfo();
$group = RBAC::defaultGroup($userInfo['role']);

// 检查权限
if (RBAC::check('user.manage', $group)) {
    // 有权限
} else {
    // 无权限
    header('HTTP/1.1 403 Forbidden');
    echo '无权限访问';
}
```

### 3. 部门管理

```php
// 创建部门树
$tree = DEPARTMENT::getTree();

// 显示部门树
function displayTree($nodes, $level = 0) {
    foreach ($nodes as $node) {
        echo str_repeat('  ', $level) . $node['name'] . "\n";
        if (!empty($node['children'])) {
            displayTree($node['children'], $level + 1);
        }
    }
}
displayTree($tree);
```

### 4. 错误处理

```php
try {
    $userId = USER::createUser($userData);
} catch (Exception $e) {
    // 处理错误
    DEBUGGER::LOG("创建用户失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
    echo "创建用户失败，请重试";
}
```

### 5. 数据验证

```php
// 创建用户前验证
if (USER::usernameExists($username)) {
    echo '用户名已存在';
    return false;
}

if (USER::emailExists($email)) {
    echo '邮箱已存在';
    return false;
}

// 创建用户
USER::createUser($userData);
```

## 安全注意事项

1. **密码加密**：使用 Argon2id 算法加密密码，不要使用明文存储
2. **会话管理**：使用 Redis 存储会话，设置合理的过期时间
3. **权限检查**：在执行敏感操作前，始终检查用户权限
4. **输入验证**：对用户输入进行严格验证，防止 SQL 注入
5. **日志记录**：记录关键操作日志，便于审计和问题排查
6. **缓存使用**：合理使用 `getCurrentUserInfo` 的缓存机制，避免重复查询

## 扩展建议

1. **多因素认证**：可以扩展支持短信验证码、邮箱验证码等
2. **第三方登录**：支持微信、QQ 等第三方账号登录
3. **审计日志**：记录用户的所有操作，用于安全审计
4. **数据备份**：定期备份用户数据，防止数据丢失
5. **性能优化**：对于大量用户的场景，考虑使用缓存机制（已内置用户信息缓存）
6. **缓存扩展**：可以扩展 Redis 缓存层，实现更复杂的缓存策略

## 相关文档

- [RBAC 权限框架](/DOC/libs/access/RBAC.md)
- [数据库操作](/DOC/libs/db/pgsql.md)
- [Redis 缓存](/DOC/libs/db/redis.md)
