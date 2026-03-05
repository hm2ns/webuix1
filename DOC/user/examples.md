# 用户模块 API 使用示例

本文档提供了用户模块各功能的实际使用示例。

## 目录

- [用户管理示例](#用户管理示例)
- [部门管理示例](#部门管理示例)
- [权限组管理示例](#权限组管理示例)
- [完整业务场景](#完整业务场景)

## 用户管理示例

### 1. 用户注册

```php
<?php
// 检查用户名是否已存在
if (USER::usernameExists($username)) {
    throw new Exception('用户名已存在');
}

// 检查邮箱是否已存在
if (USER::emailExists($email)) {
    throw new Exception('邮箱已被使用');
}

// 创建用户
$userId = USER::createUser([
    'username' => $username,
    'password' => 'user_password_123',
    'nickname' => '新用户',
    'email' => $email,
    'phone' => '13800138000',
    'role' => 'STUDENT'
]);

echo "用户注册成功，ID: " . $userId;
```

### 2. 用户登录

```php
<?php
// 基础登录
$userInfo = USER::login('admin', 'admin123', [], false);

if ($userInfo) {
    echo "登录成功，欢迎 " . $userInfo['nickname'];
} else {
    echo "登录失败，用户名或密码错误";
}

// 记住我登录（延长有效期）
$userInfo = USER::login('admin', 'admin123', [
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'device_type' => 'desktop',
    'browser' => 'Chrome'
], true); // true 表示记住我
```

### 3. 当前用户操作

```php
<?php
// 获取当前用户实例
$user = USER::getInstance();

// 检查登录状态
if (!$user->isLoggedIn()) {
    header('Location: /login');
    exit;
}

// 获取当前用户信息
$userId = $user->getCurrentUserId();

// 第一次调用：从数据库查询
$userInfo1 = $user->getCurrentUserInfo();
echo "用户ID: " . $userId;
echo "昵称: " . $userInfo1['nickname'];

// 第二次调用：从缓存获取，避免重复查询
$userInfo2 = $user->getCurrentUserInfo();

// 获取最新数据，不使用缓存
$userInfo3 = $user->getCurrentUserInfo(USER::BASIC_ROWS, false);

// 更新当前用户信息
$user->updateCurrentUser([
    'nickname' => '新昵称',
    'profile' => [
        'age' => 25,
        'address' => '北京市'
    ]
]);

// 修改密码
$result = $user->changePassword('old_password', 'new_password');
if ($result) {
    echo "密码修改成功";
} else {
    echo "旧密码错误";
}

// 登出
$user->logout();
echo "已安全退出";
```

### 4. 缓存管理

```php
<?php
$user = USER::getInstance();

// getCurrentUserInfo 默认使用缓存
// 第一次调用：从数据库查询
$userInfo1 = $user->getCurrentUserInfo(USER::BASIC_ROWS);

// 第二次调用：从缓存获取，避免重复查询
$userInfo2 = $user->getCurrentUserInfo(USER::BASIC_ROWS);

// 更新用户信息（会自动清除缓存）
$user->updateCurrentUser(['nickname' => '新昵称']);

// 更新后再次调用：从数据库重新查询
$userInfo3 = $user->getCurrentUserInfo(USER::BASIC_ROWS);

// 手动清除缓存
$user->clearUserInfoCache(); // 清除所有缓存
$user->clearUserInfoCache(USER::ALL_ROWS); // 只清除 ALL_ROWS 类型的缓存
```

### 5. 权限验证

```php
<?php
$user = USER::getInstance();

// 检查是否有特定权限
if ($user->hasPermission('user.manage')) {
    echo "你有管理用户的权限";
} else {
    echo "你没有管理用户的权限";
}

// 检查角色
if ($user->hasRole('ADMIN')) {
    echo "你是管理员";
}

if ($user->hasRole(['ADMIN', 'MANAGER'])) {
    echo "你是管理员或经理";
}
```

### 5. 设备管理

```php
<?php
$user = USER::getInstance();

// 获取所有登录设备
$devices = $user->getLoginDevices();
foreach ($devices as $device) {
    echo "设备: " . ($device['device_info']['device_type'] ?? 'Unknown') . "\n";
    echo "IP: " . $device['ip'] . "\n";
    echo "登录时间: " . date('Y-m-d H:i:s', $device['login_time']) . "\n";
    echo "当前设备: " . ($device['is_current'] ? '是' : '否') . "\n";
    echo "---\n";
}

// 踢出指定设备
$user->logoutDevice('device_token_xxx');

// 踢出其他设备
$count = $user->logoutOtherDevices();
echo "已踢出 {$count} 个设备";
```

### 6. 批量操作

```php
<?php
// 根据角色查询用户
$admins = USER::getUsersByRole(['ADMIN', 'MANAGER']);
echo "管理员数量: " . count($admins);

// 根据部门查询用户
$techUsers = USER::getUsersByDepartment('技术部');

// 搜索用户
$searchResults = USER::searchUsers('张三', ['username', 'nickname', 'email']);

// 批量更新状态
$result = USER::batchUpdateStatus(['usr_001', 'usr_002'], USER::STATUS_DISABLED);
if ($result) {
    echo "批量禁用成功";
}
```

## 部门管理示例

### 1. 创建部门树

```php
<?php
// 创建根部门
$rootId = DEPARTMENT::create([
    'name' => 'XX公司',
    'code' => 'ROOT',
    'description' => '公司总部'
]);

// 创建一级部门
$techId = DEPARTMENT::create([
    'name' => '技术部',
    'code' => 'TECH',
    'parent_id' => $rootId,
    'leader_id' => 'usr_leader_001',
    'sort' => 1
]);

$salesId = DEPARTMENT::create([
    'name' => '销售部',
    'code' => 'SALES',
    'parent_id' => $rootId,
    'sort' => 2
]);

// 创建二级部门
$frontendId = DEPARTMENT::create([
    'name' => '前端组',
    'code' => 'FRONTEND',
    'parent_id' => $techId,
    'sort' => 1
]);

$backendId = DEPARTMENT::create([
    'name' => '后端组',
    'code' => 'BACKEND',
    'parent_id' => $techId,
    'sort' => 2
]);
```

### 2. 获取部门树

```php
<?php
// 获取完整部门树
$tree = DEPARTMENT::getTree();

// 递归显示部门树
function displayTree($nodes, $level = 0) {
    foreach ($nodes as $node) {
        $indent = str_repeat('  ', $level);
        echo $indent . "- " . $node['name'] . " (" . $node['code'] . ")\n";

        if (!empty($node['children'])) {
            displayTree($node['children'], $level + 1);
        }
    }
}

displayTree($tree);
```

### 3. 部门查询

```php
<?php
// 根据 ID 获取部门
$dept = DEPARTMENT::getById('dept_xxx123');

// 根据编码获取部门
$dept = DEPARTMENT::getByCode('TECH');

// 获取子部门
$children = DEPARTMENT::getChildren('dept_parent_123');
foreach ($children as $child) {
    echo $child['name'] . "\n";
}

// 获取所有子孙部门
$descendants = DEPARTMENT::getDescendants('dept_parent_123');

// 获取部门路径
$path = DEPARTMENT::getPath('dept_child_123');
foreach ($path as $dept) {
    echo " / " . $dept['name'];
}
```

### 4. 部门移动

```php
<?php
// 将部门移动到新的父部门
$success = DEPARTMENT::move('dept_xxx123', 'dept_new_parent_456');

if ($success) {
    echo "部门移动成功";
} else {
    echo "部门移动失败";
}

// 将部门移动到根目录
DEPARTMENT::move('dept_xxx123', null);
```

### 5. 部门搜索和统计

```php
<?php
// 搜索部门
$departments = DEPARTMENT::search('技术', ['name', 'code', 'description']);
foreach ($departments as $dept) {
    echo $dept['name'] . " - " . $dept['code'] . "\n";
}

// 获取统计信息
$stats = DEPARTMENT::getStats();
echo "总部门数: " . $stats['total'] . "\n";
echo "正常部门数: " . $stats['normal'] . "\n";
echo "禁用部门数: " . $stats['disabled'] . "\n";
echo "一级部门数: " . $stats['level_1'] . "\n";
echo "二级部门数: " . $stats['level_2'] . "\n";
```

## 权限组管理示例

### 1. 创建权限组

```php
<?php
// 创建管理员权限组
$adminGroupId = AUTHORITIES::create([
    'name' => '超级管理员',
    'code' => 'SUPER_ADMIN',
    'description' => '拥有所有权限的管理员',
    'permissions' => [
        'user.*',
        'department.*',
        'system.*',
        'config.*'
    ],
    'sort' => 1
]);

// 创建普通管理员权限组
$managerGroupId = AUTHORITIES::create([
    'name' => '部门管理员',
    'code' => 'DEPT_MANAGER',
    'description' => '可以管理部门内的用户',
    'permissions' => [
        'user.view',
        'user.edit',
        'user.create',
        'department.view'
    ],
    'base_group' => $adminGroupId, // 继承管理员权限
    'sort' => 2
]);

// 创建普通用户权限组
$userGroupId = AUTHORITIES::create([
    'name' => '普通用户',
    'code' => 'NORMAL_USER',
    'description' => '普通用户权限',
    'permissions' => [
        'user.view.self',
        'profile.edit'
    ],
    'sort' => 3
]);
```

### 2. 权限组查询

```php
<?php
// 根据 ID 获取权限组
$group = AUTHORITIES::getById('auth_xxx123');

// 根据编码获取权限组
$group = AUTHORITIES::getByCode('SUPER_ADMIN');

// 获取所有权限组
$groups = AUTHORITIES::getAll();
foreach ($groups as $group) {
    echo $group['name'] . " (" . $group['code'] . ")\n";
}

// 搜索权限组
$groups = AUTHORITIES::search('管理', ['name', 'code', 'description']);
```

### 3. 权限管理

```php
<?php
// 获取完整权限（包含继承）
$permissions = AUTHORITIES::getFullPermissions('auth_xxx123');
echo "权限列表:\n";
foreach ($permissions as $permission) {
    echo "- " . $permission . "\n";
}

// 更新权限组
AUTHORITIES::update('auth_xxx123', [
    'name' => '新名称',
    'permissions' => [
        'user.view',
        'user.edit',
        'user.delete'
    ]
]);

// 为用户分配权限组
AUTHORITIES::assignToUser('usr_xxx123', 'auth_group_001');
AUTHORITIES::assignToUser('usr_xxx123', ['auth_group_001', 'auth_group_002']);
```

### 4. 权限组导入导出

```php
<?php
// 导出权限组
$exportData = AUTHORITIES::export(['auth_001', 'auth_002']);
file_put_contents('permissions.json', json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// 导入权限组
$importData = json_decode(file_get_contents('permissions.json'), true);
$result = AUTHORITIES::import($importData);

echo "成功导入 " . count($result['success']) . " 个权限组\n";
if (!empty($result['failed'])) {
    echo "失败 " . count($result['failed']) . " 个权限组\n";
}

// 复制权限组
$newGroupId = AUTHORITIES::copy('auth_source_123', '副本权限组');
echo "新权限组ID: " . $newGroupId;
```

## 完整业务场景

### 场景 1：用户注册流程

```php
<?php
// 接收表单数据
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';

try {
    // 验证数据
    if (empty($username) || empty($password) || empty($email)) {
        throw new Exception('请填写完整信息');
    }

    // 检查用户名是否存在
    if (USER::usernameExists($username)) {
        throw new Exception('用户名已存在');
    }

    // 检查邮箱是否存在
    if (USER::emailExists($email)) {
        throw new Exception('邮箱已被使用');
    }

    // 检查手机号是否存在
    if (!empty($phone) && USER::phoneExists($phone)) {
        throw new Exception('手机号已被使用');
    }

    // 创建用户（初始状态为禁用，需要激活）
    $userId = USER::createUser([
        'username' => $username,
        'password' => $password,
        'nickname' => $username,
        'email' => $email,
        'phone' => $phone,
        'status' => USER::STATUS_DISABLED
    ]);

    // TODO: 发送激活邮件

    echo json_encode([
        'success' => true,
        'message' => '注册成功，请查收激活邮件',
        'data' => ['user_id' => $userId]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
```

### 场景 2：登录认证流程

```php
<?php
// 接收登录数据
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$rememberMe = isset($_POST['remember_me']);

try {
    // 验证数据
    if (empty($username) || empty($password)) {
        throw new Exception('请输入用户名和密码');
    }

    // 收集设备信息
    $deviceInfo = [
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'device_type' => detectDeviceType($_SERVER['HTTP_USER_AGENT'] ?? '')
    ];

    // 尝试登录
    $userInfo = USER::login($username, $password, $deviceInfo, $rememberMe);

    if ($userInfo) {
        echo json_encode([
            'success' => true,
            'message' => '登录成功',
            'data' => [
                'user_id' => $userInfo['id'],
                'username' => $userInfo['username'],
                'nickname' => $userInfo['nickname'],
                'role' => $userInfo['role']
            ]
        ]);
    } else {
        throw new Exception('用户名或密码错误');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function detectDeviceType($userAgent) {
    if (preg_match('/Mobile|Android|iPhone/i', $userAgent)) {
        return 'mobile';
    }
    if (preg_match('/Tablet|iPad/i', $userAgent)) {
        return 'tablet';
    }
    return 'desktop';
}
```

### 场景 3：部门管理界面

```php
<?php
// 获取所有部门树
$tree = DEPARTMENT::getTree();
?>

<!DOCTYPE html>
<html>
<head>
    <title>部门管理</title>
</head>
<body>
    <h1>部门管理</h1>

    <div id="department-tree">
        <?php function renderTree($nodes, $level = 0) { ?>
            <ul class="level-<?php echo $level; ?>">
                <?php foreach ($nodes as $node): ?>
                    <li>
                        <div class="department-item">
                            <span class="name"><?php echo htmlspecialchars($node['name']); ?></span>
                            <span class="code">(<?php echo htmlspecialchars($node['code']); ?>)</span>
                            <span class="actions">
                                <a href="/department/edit?id=<?php echo $node['id']; ?>">编辑</a>
                                <a href="/department/delete?id=<?php echo $node['id']; ?>">删除</a>
                            </span>
                        </div>
                        <?php if (!empty($node['children'])): ?>
                            <?php renderTree($node['children'], $level + 1); ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php } ?>

        <?php renderTree($tree); ?>
    </div>

    <a href="/department/create">新建部门</a>
</body>
</html>
```

### 场景 4：权限验证中间件

```php
<?php
// 检查登录状态
function requireLogin() {
    $user = USER::getInstance();
    if (!$user->isLoggedIn()) {
        header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// 检查权限
function requirePermission($permission) {
    requireLogin();

    $user = USER::getInstance();
    if (!$user->hasPermission($permission)) {
        header('HTTP/1.1 403 Forbidden');
        echo '无权限访问';
        exit;
    }
}

// 检查角色
function requireRole($roles) {
    requireLogin();

    $user = USER::getInstance();
    if (!$user->hasRole($roles)) {
        header('HTTP/1.1 403 Forbidden');
        echo '无权限访问';
        exit;
    }
}

// 使用示例
// 在需要登录的页面
requireLogin();

// 在需要特定权限的页面
requirePermission('user.manage');

// 在需要特定角色的页面
requireRole(['ADMIN', 'MANAGER']);
```

### 场景 5：用户管理后台

```php
<?php
// 初始化 RBAC
RBAC::init();

// 获取当前用户
$user = USER::getInstance();

// 检查是否有管理权限
if (!$user->hasPermission('user.manage')) {
    header('HTTP/1.1 403 Forbidden');
    echo '无权限访问';
    exit;
}

// 获取查询参数
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$keyword = $_GET['keyword'] ?? '';
$role = $_GET['role'] ?? '';
$department = $_GET['department'] ?? '';

$limit = 20;
$offset = ($page - 1) * $limit;

// 查询用户列表
if (!empty($keyword)) {
    $users = USER::searchUsers($keyword, ['username', 'nickname', 'email'], $limit, $offset);
} elseif (!empty($role)) {
    $users = USER::getUsersByRole($role, $limit, $offset);
} elseif (!empty($department)) {
    $users = USER::getUsersByDepartment($department, $limit, $offset);
} else {
    $users = USER::getUsersByRole(array_keys(USER::ROLES), $limit, $offset);
}

// 获取统计数据
$stats = USER::getUserStats();
?>

<!DOCTYPE html>
<html>
<head>
    <title>用户管理</title>
</head>
<body>
    <h1>用户管理</h1>

    <!-- 统计信息 -->
    <div class="stats">
        <div>总用户: <?php echo $stats['total']; ?></div>
        <div>正常: <?php echo $stats['normal']; ?></div>
        <div>禁用: <?php echo $stats['disabled']; ?></div>
    </div>

    <!-- 搜索表单 -->
    <form method="GET">
        <input type="text" name="keyword" placeholder="搜索用户..." value="<?php echo htmlspecialchars($keyword); ?>">
        <select name="role">
            <option value="">所有角色</option>
            <?php foreach (USER::ROLES as $key => $name): ?>
                <option value="<?php echo $key; ?>" <?php echo $role == $key ? 'selected' : ''; ?>>
                    <?php echo $name; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">搜索</button>
    </form>

    <!-- 用户列表 -->
    <table>
        <thead>
            <tr>
                <th>用户名</th>
                <th>昵称</th>
                <th>邮箱</th>
                <th>部门</th>
                <th>角色</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['nickname']); ?></td>
                    <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($user['department'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars(USER::ROLES[$user['role']] ?? $user['role']); ?></td>
                    <td><?php echo htmlspecialchars($user['status']); ?></td>
                    <td>
                        <a href="/user/edit?id=<?php echo $user['id']; ?>">编辑</a>
                        <a href="/user/delete?id=<?php echo $user['id']; ?>">删除</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="/user/create">新建用户</a>
</body>
</html>
```

## 错误处理示例

```php
<?php
try {
    // 创建用户
    $userId = USER::createUser($userData);

} catch (PDOException $e) {
    // 数据库错误
    DEBUGGER::LOG("数据库错误: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
    echo "系统错误，请稍后重试";

} catch (Exception $e) {
    // 其他错误
    DEBUGGER::LOG("创建用户失败: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_WARNING);
    echo "创建用户失败: " . $e->getMessage();
}

// 使用更安全的错误处理方式
function safeCreateUser($userData) {
    try {
        // 验证数据
        if (empty($userData['username']) || empty($userData['password'])) {
            return ['success' => false, 'message' => '用户名和密码不能为空'];
        }

        // 检查唯一性
        if (USER::usernameExists($userData['username'])) {
            return ['success' => false, 'message' => '用户名已存在'];
        }

        if (!empty($userData['email']) && USER::emailExists($userData['email'])) {
            return ['success' => false, 'message' => '邮箱已被使用'];
        }

        // 创建用户
        $userId = USER::createUser($userData);

        return [
            'success' => true,
            'message' => '创建成功',
            'data' => ['user_id' => $userId]
        ];

    } catch (Exception $e) {
        DEBUGGER::LOG("创建用户异常: " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
        return ['success' => false, 'message' => '系统错误，请稍后重试'];
    }
}
```

## 注意事项

1. **始终验证用户输入**：在创建或更新用户前，验证所有输入数据
2. **处理异常**：使用 try-catch 块处理可能的异常
3. **合理使用缓存**：
   - 默认情况下 `getCurrentUserInfo` 会使用缓存，避免重复查询
   - 在数据更新后缓存会自动清除，保证数据一致性
   - 如需获取最新数据，可使用 `$user->getCurrentUserInfo($rows, false)` 禁用缓存
   - 缓存基于内存，单次请求内有效，适合多次查询相同用户信息的场景
3. **检查权限**：在执行敏感操作前，检查用户权限
4. **记录日志**：记录重要操作，便于审计和问题排查
5. **使用事务**：对于复杂的操作，考虑使用数据库事务保证数据一致性
