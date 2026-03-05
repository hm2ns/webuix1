# WebUI-X 页面开发指南

## 1. 快速开始

### 1.1 创建第一个页面

**步骤 1：添加路由**

在 `config/route.php` 中添加路由配置：

```php
["/hello", "hello"],  // 访问 /hello 将加载 script/page/hello.php
```

**步骤 2：创建页面文件**

创建 `script/page/hello.php`：

```php
<?php
// 1. 输出页面头部
UI_STRUCTURE::header('你好，世界', 0);

// 2. 添加提示消息
UI_NOTICE::alert('欢迎来到新页面！', BS_SUCCESS);
?>
<!-- 3. 页面内容 -->
<div class="row">
    <div class="col-md-12">
        <h1>你好，世界！</h1>
        <p>这是你的第一个页面。</p>
    </div>
</div>
<?php
// 4. 输出页面底部
UI_STRUCTURE::footer();
?>
```

**步骤 3：访问页面**

在浏览器中打开 `http://yourdomain.com/hello`

## 2. 页面基本结构

### 2.1 标准页面模板

```php
<?php
/**
 * 页面描述
 * @author 作者
 * @date 日期
 */

// 引入必要的类库（loader.php 已自动加载）
// REQUEST、Router、UI_STRUCTURE 等全局可用

// 输出页面头部
UI_STRUCTURE::header(
    '页面标题',           // $title: 页面标题
    0,                     // $nav: 导航栏类型 (0=正常, 1=仅图标, 2=仅顶部, 3=无导航)
    'page_name',          // $pagename: 页面名称标识
    ''                     // $oh: 额外的HTML头部内容
);

// 可选：添加通知
UI_NOTICE::alert('提示信息', BS_INFO);
?>
<!-- 页面主要内容 -->
<div class="row">
    <div class="col-md-12">
        <!-- 内容区域 -->
    </div>
</div>
<?php
// 输出页面底部
UI_STRUCTURE::footer();
?>
```

### 2.2 导航栏类型

```php
// 导航栏常量（UI_STRUCTURE 中定义）
const NAV_TYPE_NORMAL = 0;     // 正常显示（默认）
const NAV_TYPE_ONLYICONS = 1;   // 仅显示图标
const NAV_TYPE_ONLYTOP = 2;     // 仅顶部导航
const NAV_TYPE_NONE = 3;        // 无导航

// 使用示例
UI_STRUCTURE::header('页面标题', UI_STRUCTURE::NAV_TYPE_ONLYTOP);
```

## 3. UI 组件使用

### 3.1 按钮（[UI_BUTTONS](../script/lib/view/buttons.php)）

```php
// 生成基础按钮
UI_BUTTONS::common('按钮文字', 'btn-primary');

// 生成带图标的按钮
UI_BUTTONS::common('删除', 'btn-danger', 'trash');

// 生成链接式按钮
UI_BUTTONS::link('返回', '/home', 'btn-secondary');
```

### 3.2 表格（[UI_TABLE](../script/lib/view/tables.php)）

#### 基础表格

```php
$tableData = [
    ['ID', '姓名', '邮箱', '操作'],
    ['1', '张三', 'zhang@example.com', '<button>编辑</button>'],
    ['2', '李四', 'li@example.com', '<button>编辑</button>'],
];

UI_TABLE::common(
    $tableData,              // 表格数据
    TABLE_DEFAULT_CFG,       // 表格样式配置
    [],                      // 行样式配置
    []                       // 列样式配置
);
```

#### 增强表格（带搜索、分页）

```php
UI_TABLE::super(
    $tableData,              // 表格数据
    TABLE_DEFAULT_CFG,       // 表格样式配置
    [],                      // 行样式配置
    [],                      // 列样式配置
    true,                    // 显示表格信息
    true,                    // 启用分页
    true                     // 启用搜索
);
```

#### 可导出表格

```php
UI_TABLE::super_downloadable(
    $tableData,              // 表格数据
    TABLE_DEFAULT_CFG,       // 表格样式配置
    [],                      // 行样式配置
    [],                      // 列样式配置
    true,                    // 显示表格信息
    true,                    // 启用分页
    true,                    // 启用搜索
    '用户列表',              // 导出文件名
    0                        // 导出控件类型 (0=输出, 1=返回HTML, 3=不生成)
);
```

#### 行/列样式配置

```php
$rowcfg = [
    0 => '',                              // 表头行
    1 => 'table-danger',                  // 第一行数据（红色）
    2 => 'table-success',                 // 第二行数据（绿色）
];

$colcfg = [
    0 => ['', '', '', ''],                // 表头列
    1 => ['', 'text-danger', '', ''],     // 第一行第二列（红色文字）
];

UI_TABLE::common($data, TABLE_DEFAULT_CFG, $rowcfg, $colcfg);
```

### 3.3 通知系统（[UI_NOTICE](../script/lib/view/notice.php)）

#### Alert 提示框

```php
// 基础用法
UI_NOTICE::alert('操作成功！', BS_SUCCESS);
UI_NOTICE::alert('请注意', BS_WARNING, 3000);  // 3秒后消失

// 预置类型常量
const BS_SUCCESS = "success";
const BS_INFO = "info";
const BS_WARNING = "warning";
const BS_DANGER = "danger";
const BS_PRIMARY = "primary";
```

#### Toast 消息通知

```php
// 基础通知
UI_NOTICE::notice(
    '您有一条新消息',    // 消息内容
    '系统通知',          // 标题
    'bell',              // 图标
    '刚刚'               // 时间
);

// 激活通知图标
UI_NOTICE::activeNoticeICON();
```

#### 可点击通知

```php
UI_NOTICE::addNotice(
    '点击查看详情',      // 消息内容
    '新订单',            // 标题
    'cart',              // 图标
    '/order/123',        // 点击跳转URL
    0,                   // 是否立即显示 (0=否, 1=是)
    '2分钟前'            // 时间
);

// 立即显示
UI_NOTICE::addNotice('系统维护', '通知', 'wrench', '#', 1, '1小时前');
```

### 3.4 图标（[UI_ICON](../script/lib/view/icon.php)）

```php
// 输出图标HTML
echo UI_ICON::icon('home');          // 首页图标
echo UI_ICON::icon('user');          // 用户图标
echo UI_ICON::icon('settings');      // 设置图标
```

常用图标：
- `home` - 首页
- `user` - 用户
- `settings` - 设置
- `bell` - 通知铃铛
- `cart` - 购物车
- `trash` - 删除
- `pencil` - 编辑
- `eye` - 查看

### 3.5 分页导航（[UI_STRUCTURE::pageCutNav()](../script/lib/view/UIStructure.php)）

```php
// 基础分页
UI_STRUCTURE::pageCutNav(
    2,            // 当前页码
    15,           // 总页数
    '/user/list'  // 基础URL
);

// 输出：上一页 1 [2] 3 4 5 ... 15 下一页
```

### 3.6 Markdown 渲染（[UI_MARKDOWN](../script/lib/view/markdown.php)）

```php
// 在HTML中放置容器
<div id="markdown-content">
# Hello World
This is **markdown** content.
</div>

<?php
// 渲染Markdown
UI_MARKDOWN::Parser("markdown-content");
?>
```

### 3.7 代码编辑器（[UI_ACEEDITOR](../script/lib/view/aceEditor.php)）

```php
// 生成代码编辑器
UI_ACEEDITOR::show(
    'code-editor',           // 编辑器ID
    'javascript',             // 语言类型
    'function hello() {\n  console.log("Hello");\n}',  // 初始代码
    400,                     // 高度
    true                     // 是否只读
);
```

## 4. 数据处理

### 4.1 获取请求参数

```php
// GET 参数
$page = REQUEST::get('page', '1');  // 默认值 '1'

// POST 参数
$username = REQUEST::post('username', '');

// 任意参数（GET/POST）
$search = REQUEST::request('search', '');

// 获取所有参数
$allParams = REQUEST::requests();
```

### 4.2 获取路由参数

```php
// 获取单个参数
$articleId = Router::getParam('id');

// 获取所有参数
$params = Router::getParams();
```

### 4.3 数据库操作

```php
// PostgreSQL 操作
$result = PGSQL::query("SELECT * FROM users WHERE id = ?", [$userId]);
$row = PGSQL::fetch($result);

// Redis 操作
RedisClient::set('key', 'value', 3600);
$value = RedisClient::get('key');
```

### 4.4 表单处理

```php
if (REQUEST::method() === 'POST') {
    $username = REQUEST::post('username');
    $password = REQUEST::post('password');

    // 验证数据
    if (empty($username) || empty($password)) {
        UI_NOTICE::alert('用户名和密码不能为空', BS_DANGER);
    } else {
        // 处理登录逻辑
        // ...
    }
}
?>
<form method="POST" action="">
    <div class="form-group">
        <label>用户名</label>
        <input type="text" name="username" class="form-control">
    </div>
    <div class="form-group">
        <label>密码</label>
        <input type="password" name="password" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary">登录</button>
</form>
<?php
```

## 5. 权限控制

### 5.1 路由级别权限

在 `config/route.php` 中配置：

```php
["/admin", "admin/index", "admin"],              // 需要 admin 权限
["/user/profile", "user/profile", "#profile"],    // 需要登录且有 profile 权限
["/user/login", "user/login", "none_LOGINED"],    // 需要登录
```

### 5.2 页面内权限检查

```php
// 获取用户权限组
$userPermissions = RBAC::defaultGroup("user");

// 检查权限
if (!RBAC::check("admin_panel", $userPermissions)) {
    UI_ERRORPAGES::show(403, "您没有权限访问此页面");
    return;
}

// 有权限的代码继续执行
?>
<h1>管理员面板</h1>
<?php
```

### 5.3 条件显示内容

```php
$userPermissions = RBAC::defaultGroup("user");

if (RBAC::check("edit_user", $userPermissions)) {
    echo '<button>编辑用户</button>';
}

if (RBAC::check("delete_user", $userPermissions)) {
    echo '<button>删除用户</button>';
}
```

## 6. 错误处理

### 6.1 显示错误页面

```php
// 显示 404 错误
UI_ERRORPAGES::show(404, '页面未找到');

// 显示 403 错误
UI_ERRORPAGES::show(403, '权限不足');

// 显示自定义错误
UI_ERRORPAGES::show("CRSF", "检测到跨站请求伪造");
```

### 6.2 记录日志

```php
// 记录信息日志
DEBUGGER::LOG("用户登录: " . $username, DEBUGGER::DEBUG_LEVEL_INFO);

// 记录警告
DEBUGGER::LOG("数据库连接慢", DEBUGGER::DEBUG_LEVEL_WARNING);

// 记录错误（会打断执行）
DEBUGGER::LOG("数据库连接失败", DEBUGGER::DEBUG_LEVEL_ERROR);

// 记录致命错误（会打断执行）
DEBUGGER::LOG("系统初始化失败", DEBUGGER::DEBUG_LEVEL_FATAL);
```

### 6.3 开发模式调试

```php
if (GLOBAL_CONFIG::is_dev()) {
    // 仅在开发模式显示调试信息
    var_dump($data);
    echo "<pre>" . print_r($data, true) . "</pre>";
}
```

## 7. AJAX 请求处理

### 7.1 创建 API 接口

**步骤 1：添加路由**

```php
// config/route.php
["/api/data", "api/data", "none"],
```

**步骤 2：创建 API 页面**

```php
// script/page/api/data.php
<?php
header('Content-Type: application/json');

if (REQUEST::method() !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 获取参数
$keyword = REQUEST::get('keyword', '');

// 查询数据
$data = [
    ['id' => 1, 'name' => 'Item 1'],
    ['id' => 2, 'name' => 'Item 2'],
];

// 返回 JSON
echo json_encode([
    'success' => true,
    'data' => $data
]);
?>
```

### 7.2 前端 AJAX 调用

```javascript
fetch('/api/data?keyword=test')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log(data.data);
        }
    })
    .catch(error => console.error('Error:', error));
```

## 8. 完整示例

### 8.1 用户列表页面

```php
<?php
// script/page/users/list.php

// 获取分页参数
$page = (int)REQUEST::get('page', '1');
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

// 权限检查
$permissions = RBAC::defaultGroup("user");
if (!RBAC::check("view_users", $permissions)) {
    UI_ERRORPAGES::show(403, "您没有权限查看用户列表");
    return;
}

// 查询用户数据（示例）
$users = [
    ['id' => 1, 'name' => '张三', 'email' => 'zhang@example.com', 'status' => 'active'],
    ['id' => 2, 'name' => '李四', 'email' => 'li@example.com', 'status' => 'inactive'],
    // ...
];

// 输出页面头部
UI_STRUCTURE::header('用户列表', 0);
?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">用户列表</h4>

                <!-- 操作按钮 -->
                <?php if (RBAC::check("add_user", $permissions)) { ?>
                    <a href="/users/create" class="btn btn-primary mb-3">
                        新增用户
                    </a>
                <?php } ?>

                <!-- 用户表格 -->
                <?php
                // 构建表格数据
                $tableData = [['ID', '姓名', '邮箱', '状态', '操作']];

                foreach ($users as $user) {
                    $actions = '';
                    if (RBAC::check("edit_user", $permissions)) {
                        $actions .= '<a href="/users/edit/' . $user['id'] . '" class="btn btn-sm btn-info">编辑</a> ';
                    }
                    if (RBAC::check("delete_user", $permissions)) {
                        $actions .= '<button class="btn btn-sm btn-danger" onclick="deleteUser(' . $user['id'] . ')">删除</button>';
                    }

                    $statusClass = $user['status'] === 'active' ? 'table-success' : 'table-danger';
                    $tableData[] = [
                        $user['id'],
                        $user['name'],
                        $user['email'],
                        $user['status'],
                        $actions
                    ];
                }

                UI_TABLE::super_downloadable(
                    $tableData,
                    TABLE_DEFAULT_CFG,
                    [],
                    [],
                    true,
                    true,
                    true,
                    '用户列表_' . date('Ymd')
                );
                ?>

                <!-- 分页 -->
                <?php
                $totalPages = 10;  // 总页数
                UI_STRUCTURE::pageCutNav($page, $totalPages, '/users/list');
                ?>
            </div>
        </div>
    </div>
</div>

<!-- 删除确认对话框 -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">确认删除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                确定要删除此用户吗？
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">确定</button>
            </div>
        </div>
    </div>
</div>

<script>
let deleteUserId = null;

function deleteUser(id) {
    deleteUserId = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('confirmDelete').addEventListener('click', function() {
    fetch('/api/users/' + deleteUserId, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('删除成功', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('删除失败: ' + data.message, 'danger');
        }
    })
    .catch(error => showAlert('请求失败', 'danger'));

    bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
});
</script>
<?php
UI_STRUCTURE::footer();
?>
```

### 8.2 用户登录页面

```php
<?php
// script/page/user/login.php

// 检查是否已登录
// （根据你的认证系统实现）

// 处理登录请求
if (REQUEST::method() === 'POST') {
    $username = REQUEST::post('username', '');
    $password = REQUEST::post('password', '');

    // 验证输入
    if (empty($username) || empty($password)) {
        UI_NOTICE::alert('用户名和密码不能为空', BS_WARNING);
    } else {
        // 验证用户凭据
        // $user = verifyUser($username, $password);

        // 示例逻辑
        if ($username === 'admin' && $password === 'password') {
            // 登录成功
            // $_SESSION['user_id'] = $user['id'];
            // $_SESSION['username'] = $user['name'];

            UI_NOTICE::alert('登录成功，正在跳转...', BS_SUCCESS);
            ?>
            <script>
                setTimeout(() => location.href = '/', 1500);
            </script>
            <?php
        } else {
            UI_NOTICE::alert('用户名或密码错误', BS_DANGER);
            DEBUGGER::LOG("登录失败: 用户 {$username}", DEBUGGER::DEBUG_LEVEL_WARNING);
        }
    }
}

// 输出页面
UI_STRUCTURE::header('用户登录', UI_STRUCTURE::NAV_TYPE_ONLYTOP);
?>
<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title text-center mb-4">用户登录</h4>

                <form method="POST" action="">
                    <div class="form-group mb-3">
                        <label for="username">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="password">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">记住我</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">登录</button>
                </form>

                <div class="text-center mt-3">
                    <a href="/user/register">注册账号</a> |
                    <a href="/user/forget">忘记密码</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
UI_STRUCTURE::footer();
?>
```

## 9. 最佳实践

### 9.1 代码组织

```php
<?php
/**
 * 用户列表页面
 * @author Your Name
 * @date 2024-01-01
 */

// 1. 初始化和检查
if (!RBAC::check("view_users", RBAC::defaultGroup("user"))) {
    UI_ERRORPAGES::show(403);
    return;
}

// 2. 处理表单提交
if (REQUEST::method() === 'POST') {
    // 处理逻辑
}

// 3. 获取数据
$data = fetchUserData();

// 4. 输出页面
UI_STRUCTURE::header('用户列表');
?>

<!-- 页面内容 -->
<?php
UI_STRUCTURE::footer();
?>
```

### 9.2 安全性

```php
// 永远不要直接输出用户输入
echo htmlspecialchars($_GET['user_input']);

// 使用框架提供的方法
echo htmlspecialchars(REQUEST::get('user_input'));

// SQL 查询使用参数化
PGSQL::query("SELECT * FROM users WHERE name = ?", [$name]);
```

### 9.3 性能优化

```php
// 分页加载数据
$page = (int)REQUEST::get('page', '1');
$offset = ($page - 1) * $pageSize;
$data = fetchPageData($offset, $pageSize);

// 缓存频繁访问的数据
$cacheKey = 'user_stats_' . date('Ymd');
$stats = RedisClient::get($cacheKey);

if (!$stats) {
    $stats = calculateUserStats();
    RedisClient::set($cacheKey, json_encode($stats), 3600);
} else {
    $stats = json_decode($stats, true);
}
```

### 9.4 用户体验

```php
// 添加友好的提示信息
UI_NOTICE::alert('数据已保存', BS_SUCCESS);

// 添加加载状态
UI_STRUCTURE::appendImport('js', '
    $(document).on("submit", "form", function() {
        $(this).find("button[type=submit]").prop("disabled", true);
    });
');

// 操作成功后跳转
UI_STRUCTURE::appendImport('js', '
    setTimeout(() => location.href = "/users/list", 2000);
');
```

## 10. 常见问题

### Q1: 如何自定义页面样式？

```php
UI_STRUCTURE::header('标题', 0, '', '<link rel="stylesheet" href="/static/css/custom.css">');
// 或
UI_STRUCTURE::appendImport('css', '
    .custom-class { color: red; }
');
```

### Q2: 如何添加自定义 JavaScript？

```php
UI_STRUCTURE::appendImport('js', '
    console.log("Hello from custom JS");
');
```

### Q3: 如何处理文件上传？

```php
if (REQUEST::method() === 'POST') {
    $file = REQUEST::file('avatar');
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        // 处理文件上传
        move_uploaded_file($file['tmp_name'], '/uploads/' . $file['name']);
    }
}
?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="avatar">
    <button type="submit">上传</button>
</form>
<?php
```

### Q4: 如何实现搜索功能？

```php
$keyword = REQUEST::get('keyword', '');
$data = searchUsers($keyword);

// 在页面中显示搜索结果
?>
<form method="GET" class="mb-3">
    <div class="input-group">
        <input type="text" name="keyword" class="form-control" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="搜索...">
        <button type="submit" class="btn btn-primary">搜索</button>
    </div>
</form>
<?php
```
