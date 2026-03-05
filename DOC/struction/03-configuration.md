# WebUI-X 配置说明

## 1. 配置文件概述

所有配置文件位于 `config/` 目录下：

| 文件名 | 用途 | 必需 |
|--------|------|------|
| [system.php](../config/system.php) | 系统基础配置 | 是 |
| [route.php](../config/route.php) | 路由配置 | 是 |
| [RBAC.php](../config/RBAC.php) | 权限组配置 | 是 |
| [security.php](../config/security.php) | 安全配置 | 是 |

## 2. 系统配置 ([config/system.php](../config/system.php))

### 2.1 完整配置示例

```php
<?php
return [
    // 环境标识
    'ENV' => 'SA',

    // 页面标题后缀
    'titleSuffix' => '-WebUI-X',

    // Logo 路径
    'logo' => '/static/image/tool/logo.png',
    'logo_long' => '/static/image/tool/logo_long.png',

    // 数据库配置
    'db' => [
        'db_host' => 'localhost',
        'db_port' => '5432',
        'db_name' => 'DEMO',
        'db_user' => 'DEMO',
        'db_pass' => 'DEMO',
        'db_driver' => 'pgsql',
    ],

    // Redis 配置
    'redis' => [
        'host' => 'localhost',
        'port' => 6379,
        'password' => '',
        'timeout' => 0,
        'baserange' => 1,   // 可用的表数量
        'offset' => 0,      // 表示可以使用0~(baserange-1)号表
    ],

    // 开发模式开关
    "dev_mode" => true,
];
```

### 2.2 配置项详解

#### ENV - 环境标识
```php
'ENV' => 'SA'
```
- 用于标识当前部署环境
- 常用值：`DEV`（开发）、`TEST`（测试）、`PROD`（生产）、`SA`（预发布）
- 可通过 `GLOBAL_CONFIG::get('ENV')` 获取

#### titleSuffix - 标题后缀
```php
'titleSuffix' => '-WebUI-X'
```
- 自动追加到所有页面标题后
- 例如：页面标题为"首页"，最终输出为"首页-WebUI-X"

#### logo / logo_long - Logo 路径
```php
'logo' => '/static/image/tool/logo.png',
'logo_long' => '/static/image/tool/logo_long.png'
```
- `logo`: 紧凑版 Logo（导航栏收起时）
- `logo_long`: 长版 Logo（导航栏展开时）

#### db - 数据库配置
```php
'db' => [
    'db_host' => 'localhost',    // 数据库主机
    'db_port' => '5432',          // 端口
    'db_name' => 'DEMO',          // 数据库名
    'db_user' => 'DEMO',          // 用户名
    'db_pass' => 'DEMO',          // 密码
    'db_driver' => 'pgsql',       // 驱动类型（目前仅支持 pgsql）
]
```
注意：框架目前仅支持 PostgreSQL 数据库

#### redis - Redis 配置
```php
'redis' => [
    'host' => 'localhost',        // Redis 主机
    'port' => 6379,               // 端口
    'password' => '',              // 密码
    'timeout' => 0,                // 超时时间（秒）
    'baserange' => 1,              // 可用数据库数量
    'offset' => 0,                 // 起始数据库编号
]
```
- `baserange`: 可用数据库总数
- `offset`: 起始数据库偏移量
- 示例：`baserange=4, offset=0` 可使用数据库 0、1、2、3

#### dev_mode - 开发模式
```php
"dev_mode" => true
```
- `true`: 开发模式，显示详细错误信息和调试日志
- `false`: 生产模式，隐藏敏感信息

⚠️ **警告**：生产环境务必设置为 `false`

## 3. 路由配置 ([config/route.php](../config/route.php))

### 3.1 配置格式

```php
return [
    ["/", "index"],
    ["/<cid>", "index"],
    ["pdo", "pdo"],
    ["redis", "redis"],
];
```

每条路由是一个数组，格式为：
```
[URI路径, 页面脚本名, 权限标识]
```

### 3.2 参数说明

#### 第一个元素：URI 路径
- **静态路由**：`"/"`, `"user/profile"`, `"admin/settings"`
- **参数化路由**：`"/user/<id>"`, `"/article/<category>/<id>"`

#### 第二个元素：页面脚本名
- 对应 `script/page/` 目录下的 PHP 文件名
- 不包含 `.php` 扩展名
- 例如：`"index"` → `script/page/index.php`

#### 第三个元素：权限标识（可选）
- 默认值为 `none`（无需权限）
- 特殊值：
  - `none` - 无需权限，所有人可访问
  - `none_LOGINED` - 需要登录
  - `#permission` - 需要登录且有该权限
  - `permission_name` - 需要该权限

### 3.3 路由示例

```php
return [
    // 首页
    ["/", "index", "none"],

    // 用户相关
    ["user/login", "user/login", "none_LOGINED"],     // 需要登录
    ["user/profile", "user/profile", "#user_profile"], // 需要登录且有权限

    // 参数化路由
    ["/article/<id>", "article/detail", "none"],       // 提取参数 id
    ["/category/<cat>/<page>", "category/list", "none"], // 提取参数 cat, page

    // 管理后台
    ["admin", "admin/index", "admin"],
    ["admin/users", "admin/users", "admin_users"],
];
```

### 3.4 参数化路由使用

在页面中获取参数：

```php
// script/page/article/detail.php
<?php
UI_STRUCTURE::header('文章详情');

$articleId = Router::getParam('id');
$article = getArticleById($articleId);

echo "<h1>{$article['title']}</h1>";
echo "<div>{$article['content']}</div>";

UI_STRUCTURE::footer();
?>
```

## 4. 权限组配置 ([config/RBAC.php](../config/RBAC.php))

### 4.1 配置格式

```php
return [
    "groups" => [
        "default" => [  // 必须定义，其他权限组可继承
            "homepage" => true,
            "user_profile" => false,
        ],
        "guest" => "default",  // 引用默认组
        "user" => [  // 普通用户组
            "user_profile" => true,
            "user_logout" => true,
        ],
        "admin" => [  // 管理员组
            "admin_panel" => true,
            "admin_users" => true,
        ],
    ]
];
```

### 4.2 权限组继承

使用字符串引用其他权限组：

```php
"guest" => "default",  // guest 继承 default 的所有权限
```

### 4.3 权限检查

在路由中使用权限：

```php
// config/route.php
["user/profile", "user/profile", "user_profile"],
["admin/users", "admin/users", "admin_users"],
```

在代码中检查权限：

```php
$userPermissions = RBAC::defaultGroup("user");

if (RBAC::check("user_profile", $userPermissions)) {
    // 用户有权限
}
```

### 4.4 完整权限配置示例

```php
<?php
return [
    "groups" => [
        "default" => [
            // 基础权限
            "homepage" => true,
            "about" => true,
            "contact" => true,

            // 用户相关（默认未登录）
            "user_profile" => false,
            "user_logout" => false,
            "user_login" => true,
            "user_register" => true,
            "user_forget" => true,
        ],

        // 访客（继承 default）
        "guest" => "default",

        // 普通用户
        "user" => [
            "user_profile" => true,
            "user_logout" => true,
            "user_login" => false,
            "user_register" => false,
            "user_forget" => false,

            // 用户专属功能
            "user_dashboard" => true,
            "user_settings" => true,
        ],

        // VIP 用户（继承 user）
        "vip" => "user",

        // 管理员
        "admin" => [
            "admin_panel" => true,
            "admin_users" => true,
            "admin_settings" => true,
            "admin_logs" => true,
        ],

        // 超级管理员
        "super_admin" => "admin",
    ]
];
```

## 5. 安全配置 ([config/security.php](../config/security.php))

### 5.1 完整配置示例

```php
<?php
return [
    // IP 黑名单
    'banIP' => [
        //'127.0.0.1',
        //'192.168.1.100',
    ],

    // 允许的来源（CSRF 保护）
    'allowedReferers' => ["*"],

    // API TOKEN 配置
    'apiTokens' => [
        [
            "appname" => "appkey",
            "publicKey" => "yS2mN6qS9zF3xB0qI6pX4kQ8",
            "privateKey" => "uT3xU5jZ2sF8lB2uA5cI0lK8...",
            "eol" => time() + 3600,
        ]
    ]
];
```

### 5.2 banIP - IP 黑名单

```php
'banIP' => [
    '192.168.1.100',
    '10.0.0.50',
]
```
- 配置被禁止访问的 IP 地址
- IP 必须是合法的 IPv4/IPv6 格式
- 会检查代理链中的所有 IP

### 5.3 allowedReferers - 允许的来源（CSRF 保护）

```php
'allowedReferers' => ["*"]  // 允许所有来源
```

严格模式：
```php
'allowedReferers' => [
    "https://example.com",
    "https://www.example.com",
]
```

⚠️ **注意**：`*` 允许所有来源，生产环境建议限制具体域名

### 5.4 apiTokens - API TOKEN 配置

用于 API 接口的安全验证：

```php
'apiTokens' => [
    [
        "appname" => "appname",           // 应用名称
        "publicKey" => "public_key",      // 公钥（用于识别）
        "privateKey" => "private_key",   // 私钥（用于签名）
        "eol" => time() + 3600,           // 过期时间（Unix 时间戳）
    ],
]
```

#### API TOKEN 验证流程

1. 客户端发送请求时携带：
   - `appname`: 应用名称
   - `publicKey`: 公钥
   - `hover`: HMAC 签名
   - `reqtime`: 请求时间戳

2. 服务器验证：
   ```php
   SECURITY::apiTokenCheck($appname, $publicKey, $hover, $reqtime);
   ```

3. 签名计算（客户端）：
   ```javascript
   // 伪代码
   hover = HMAC_SHA256(publicKey + privateKey, reqtime);
   ```

## 6. 配置访问方法

### 6.1 读取配置

```php
// 读取配置项
$value = GLOBAL_CONFIG::get('key', 'default_value');

// 检查开发模式
if (GLOBAL_CONFIG::is_dev()) {
    // 开发模式代码
}

// 读取数据库配置
$dbHost = GLOBAL_CONFIG::get('db')['db_host'];
```

### 6.2 配置初始化

```php
// 在 index.php 中已自动调用
if (!GLOBAL_CONFIG::init()) {
    header("HTTP/1.1 500");
    echo "500 SERVER ERROR";
    exit;
}
```

### 6.3 RBAC 初始化

```php
// 在 index.php 中已自动调用
RBAC::init();

// 获取默认权限组
$permissions = RBAC::defaultGroup("user");

// 合并权限组
$merged = RBAC::mergeGroups(
    ['default', 'custom'],
    RBAC::MERGER_MODE_OVERWRITE
);

// 检查权限
if (RBAC::check("permission_name", $permissions)) {
    // 有权限
}
```

### 6.4 Router 初始化

```php
// 在 index.php 中已自动调用
Router::init();

// 获取当前路由参数
$param = Router::getParam('param_name');
$params = Router::getParams();
```

## 7. 环境配置最佳实践

### 7.1 开发环境

```php
// config/system.php
return [
    'ENV' => 'DEV',
    'titleSuffix' => '-Dev',
    "dev_mode" => true,  // 开启调试模式
];
```

```php
// config/security.php
return [
    'banIP' => [],
    'allowedReferers' => ["*"],  // 允许所有来源便于调试
    'apiTokens' => [],
];
```

### 7.2 生产环境

```php
// config/system.php
return [
    'ENV' => 'PROD',
    'titleSuffix' => '',
    "dev_mode" => false,  // 关闭调试模式
];
```

```php
// config/security.php
return [
    'banIP' => [
        // 已知的恶意 IP
    ],
    'allowedReferers' => [
        "https://yourdomain.com",
        "https://www.yourdomain.com",
    ],
    'apiTokens' => [
        [
            "appname" => "mobile_app",
            "publicKey" => "生产环境公钥",
            "privateKey" => "生产环境私钥",
            "eol" => time() + 86400 * 30,  // 30天有效期
        ]
    ],
];
```

### 7.3 测试环境

```php
// config/system.php
return [
    'ENV' => 'TEST',
    'titleSuffix' => '-Test',
    "dev_mode" => true,  // 开启调试便于测试
];
```

## 8. 常见问题

### Q1: 如何动态切换配置环境？

```php
$env = getenv('APP_ENV') ?: 'DEV';
$configFile = "config/system.{$env}.php";

if (file_exists($configFile)) {
    $config = require $configFile;
    // 合并到默认配置
}
```

### Q2: 配置修改后如何生效？

修改配置文件后直接生效，无需重启服务器。框架会在每次请求时重新加载配置。

### Q3: 如何验证配置是否正确？

```php
// 在开发模式下查看日志
DEBUGGER::LOG("Config loaded: " . json_encode(GLOBAL_CONFIG::get('db')));

// 或使用 var_dump（仅限开发环境）
if (GLOBAL_CONFIG::is_dev()) {
    var_dump(GLOBAL_CONFIG::get());
}
```

### Q4: 数据库密码可以明文存储吗？

⚠️ 不建议。生产环境应使用环境变量：

```php
'db_pass' => getenv('DB_PASSWORD') ?: 'default_password',
```

在服务器环境变量中设置：
```bash
export DB_PASSWORD=your_secure_password
```

### Q5: 如何配置多个数据库？

当前框架仅支持单一数据库配置。如需多数据库，可手动扩展：

```php
// 在页面中使用
$dbConfig1 = GLOBAL_CONFIG::get('db');
$dbConfig2 = [
    'db_host' => 'localhost2',
    'db_port' => '5432',
    // ...
];
```
