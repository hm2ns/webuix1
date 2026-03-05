# WebUI-X 执行流程说明

## 1. 请求生命周期

### 1.1 整体流程图

```
用户请求 → index.php (入口) → 系统初始化 → 路由匹配 → 安全检查 → 权限验证 → 页面渲染 → 响应输出
```

### 1.2 详细执行步骤

#### 第一步：入口文件 ([index.php](../index.php))

1. **引入加载器**
   ```php
   include "script/lib/loader.php";
   ```

2. **定义常量**
   ```php
   const ROOTDIR = __DIR__ . "/";
   ```

3. **系统配置初始化**
   ```php
   if (!GLOBAL_CONFIG::init()) {
       header("HTTP/1.1 500");
       echo "500 SERVER ERROR";
       exit;
   }
   ```
   - 读取 `config/system.php` 配置文件
   - 初始化失败则返回 500 错误

4. **调试器初始化**
   ```php
   DEBUGGER::setDebug(GLOBAL_CONFIG::is_dev());
   DEBUGGER::setShowLevel(DEBUGGER::DEBUG_LEVEL_INFO);
   ```
   - 根据配置设置调试模式
   - 设置日志显示级别

5. **请求处理初始化**
   ```php
   if (!REQUEST::init()) {
       DEBUGGER::log("[FATAL] 请求处理模块初始化失败", DEBUGGER::DEBUG_LEVEL_FATAL);
       exit;
   }
   ```
   - 解析 HTTP 请求方法
   - 解析 URI 和参数
   - 提取 GET/POST/COOKIE/SESSION 数据
   - 解析请求树结构

6. **安全与权限初始化**
   ```php
   SECURITY::init();
   RBAC::init();
   session_start();
   ```

7. **安全检查与路由**
   ```php
   if (!SECURITY::refererCheck()) {
       UI_ERRORPAGES::show("CRSF", "检测到疑似跨站请求伪造...");
   } elseif (SECURITY::isIPBanned()) {
       UI_ERRORPAGES::show("IPBANNED", "您当前的IP已被加入黑名单");
   } else {
       Router::init();
       $path = Router::getScriptPath(REQUEST::uri());
       // ... 加载页面
   }
   ```

## 2. 类库加载器 ([script/lib/loader.php](../script/lib/loader.php))

### 2.1 加载顺序

```php
// 基础模块
require_once includeLib("stateStorage");  // 状态存储
require_once includeLib("basic/time");     // 时间工具
require_once includeLib("basic/tools");    // 通用工具
require_once includeLib("basic/globalconfig");  // 全局配置
require_once includeLib("debugger");       // 调试器

// 访问控制模块
require_once includeLib("access/request");    // 请求数据处理
require_once includeLib("access/security");   // 安全控制
require_once includeLib("access/RBAC");       // RBAC权限
require_once includeLib("access/router");     // 路由系统

// UI组件
require_once includeLib("view/glo");          // UI加载器

// 数据库模块
require_once includeLib("db/pgsql");          // PostgreSQL
require_once includeLib("db/redis");         // Redis
```

### 2.2 路径辅助函数

```php
function getstatic($name)          // 获取静态资源
function includePage($pagename)    // 包含页面文件
function includeViewer($name)      // 包含视图文件
function includeC($name)           // 包含脚本文件
function includeLib($libname)      // 包含类库文件
```

## 3. 路由系统详解 ([script/lib/access/router.php](../script/lib/access/router.php))

### 3.1 路由配置格式

文件位置：`config/route.php`

```php
return [
    ["/", "index"],                    // 静态路由
    ["/<cid>", "index"],               // 参数化路由
    ["pdo", "pdo"],                    // 静态路由
    ["redis", "redis"],                // 静态路由
];
```

格式：`[URI路径, 页面脚本, 权限标识]`
- 第一个元素：URI 路径（支持 `<param>` 参数占位符）
- 第二个元素：对应 `script/page/` 下的 PHP 文件名（不含扩展名）
- 第三个元素（可选）：权限标识，默认为 `none`

### 3.2 路由初始化

```php
Router::init($usrgroups, $logined)
```

1. 加载 RBAC 默认权限组
2. 读取路由配置文件
3. 注册所有路由到路由表
4. 生成参数化路由的正则表达式

### 3.3 路由匹配流程

1. **精确匹配**
   - 首先尝试完全匹配静态路由
   - 例如：`/` 匹配到 `index`

2. **参数化匹配**
   - 使用正则表达式匹配带参数的路由
   - 例如：`/123` 匹配到 `/<cid>`，提取参数 `cid=123`

3. **参数提取**
   - 使用命名捕获组提取参数
   - 通过 `Router::getParams()` 或 `Router::getParam($name)` 获取

### 3.4 权限验证

```php
$permission = $route['moudleID'];

// 权限检查逻辑
if (
    $permission === self::MODULE_NONE ||
    ($permission === self::MODULE_LOGINED && self::$usrlogined) ||
    RBAC::check($permission, self::$RBACPermissions)
) {
    return $route['script'];  // 通过验证，返回脚本路径
}

// 特殊处理：需要登录但未登录
if ($permission === self::MODULE_LOGINED && !self::$usrlogined) {
    return "user/login";
}

// 带有 # 前缀的权限需要登录
if ($permission[0]=="#" && !self::$usrlogined) {
    return "user/login";
}

// 权限不足
UI_ERRORPAGES::show(403);
```

## 4. 安全检查流程 ([script/lib/access/security.php](../script/lib/access/security.php))

### 4.1 CSRF 检查

```php
SECURITY::refererCheck($referer)
```

1. 获取请求来源（HTTP_REFERER）
2. 与配置的允许来源列表比对
3. `*` 表示允许所有来源

### 4.2 IP 黑名单检查

```php
SECURITY::isIPBanned($ips)
```

1. 获取客户端 IP（支持代理链）
2. 检查 IP 是否在黑名单中
3. 支持多个 IP（代理链）

### 4.3 API TOKEN 验证

```php
SECURITY::apiTokenCheck($appname, $publicKey, $hover, $reqtime)
```

验证步骤：
1. 验证应用名称和公钥
2. 验证请求时间戳（防止重放攻击）
3. 验证 TOKEN 过期时间
4. 验证 HMAC 签名

## 5. 页面渲染流程

### 5.1 页面结构

标准页面结构：

```php
<?php
// 1. 输出页面头部
UI_STRUCTURE::header('页面标题', 导航类型);

// 2. 页面主要内容
?>
<!-- HTML 内容 -->
<?php

// 3. 输出页面底部
UI_STRUCTURE::footer();
?>
```

### 5.2 UI_STRUCTURE::header()

```php
UI_STRUCTURE::header(string $title, int $nav, string $pagename, string $oh)
```

执行步骤：
1. 检查头部是否已显示（防止重复）
2. 输出 `<!DOCTYPE html>` 和 `<head>`
3. 引入 `script/view/header.php`（CSS、JS 等）
4. 设置页面标题（包含后缀）
5. 输出额外的头部内容（`$oh` 参数）
6. 根据导航类型输出导航栏
7. 开始内容容器 `<div class="main-panel full-scroll"><div class="content-wrapper">`
8. 标记头部已显示

### 5.3 UI_STRUCTURE::footer()

```php
UI_STRUCTURE::footer()
```

执行步骤：
1. 检查底部是否已显示（防止重复）
2. 结束内容容器 `</div>`
3. 引入 `script/view/foot.php`（底部脚本）
4. 调用 `import()` 输出累积的资源
5. 标记底部已显示

### 5.4 资源导入栈

`UI_STRUCTURE` 维护一个资源导入栈：

```php
private static array $importStack = [
    'messagebox' => '',  // Toast 消息框
    'alert' => '',       // Alert 提示框
    'js' => '',          // JavaScript 代码
    'css' => '',         // CSS 样式
    'temp' => ['acecnt' => 0],  // 临时数据
];
```

操作方法：
- `setImport($key, $value, $prepend)` - 设置资源（可 prepend）
- `appendImport($key, $value)` - 累加资源

输出顺序：
1. MessageBox
2. Alert
3. JavaScript
4. CSS
5. `</body></html>`

## 6. 请求响应流程图

```
┌─────────────────────────────────────────────────────────────┐
│ 1. 用户发起 HTTP 请求                                          │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. index.php 入口                                              │
│    ├─ loader.php 加载所有类库                                  │
│    ├─ GLOBAL_CONFIG::init() 初始化配置                        │
│    ├─ DEBUGGER 初始化调试器                                    │
│    └─ REQUEST::init() 解析请求                                │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. 安全检查                                                    │
│    ├─ SECURITY::refererCheck() CSRF 检查                      │
│    └─ SECURITY::isIPBanned() IP 黑名单检查                     │
└─────────────────────────┬───────────────────────────────────┘
                          │
           ┌──────────────┴──────────────┐
           │                             │
      失败 ▼                      成功   ▼
┌─────────────────┐      ┌─────────────────────────────────┐
│ UI_ERRORPAGES  │      │ 4. 路由匹配                       │
│ ::show()        │      │    Router::getScriptPath($uri)  │
│ 返回错误页面     │      │    ├─ 精确匹配静态路由            │
└─────────────────┘      │    ├─ 正则匹配参数化路由          │
                          │    └─ 提取路径参数                │
                          └─────────────┬───────────────────┘
                                        │
                                        ▼
                          ┌─────────────────────────────────┐
                          │ 5. 权限验证                       │
                          │    ├─ 检查权限标识                │
                          │    ├─ RBAC::check()              │
                          │    └─ 登录状态检查                │
                          └─────────────┬───────────────────┘
                                        │
                    ┌───────────────────┴──────────────────┐
                    │                                      │
               失败 ▼                              成功   ▼
          ┌─────────────────┐               ┌─────────────────────┐
          │ UI_ERRORPAGES  │               │ 6. 加载页面          │
          │ ::show(403)     │               │ include $path        │
          └─────────────────┘               └──────────┬──────────┘
                                                        │
                                                        ▼
                                          ┌─────────────────────┐
                                          │ 7. 页面渲染           │
                                          │    ├─ header()       │
                                          │    ├─ 页面内容       │
                                          │    └─ footer()      │
                                          └──────────┬──────────┘
                                                        │
                                                        ▼
                                          ┌─────────────────────┐
                                          │ 8. 响应输出           │
                                          │    HTML + 资源       │
                                          └─────────────────────┘
```

## 7. 错误处理流程

### 7.1 错误页面展示 ([script/lib/view/errorPages.php](../script/lib/view/errorPages.php))

```php
UI_ERRORPAGES::show($code, $message)
```

处理流程：
1. 设置 HTTP 响应码
2. 如果头部未显示，调用 `UI_STRUCTURE::header()`
3. 尝试加载 `script/view/error/$code.php`
4. 如果不存在，加载通用错误页面 `script/view/error/common.php`
5. 如果底部未显示，调用 `UI_STRUCTURE::footer()`

### 7.2 调试日志 ([script/lib/debugger.php](../script/lib/debugger.php))

```php
DEBUGGER::LOG($msg, $level)
```

日志级别：
- `DEBUG_LEVEL_NONE` (0) - 无日志
- `DEBUG_LEVEL_INFO` (1) - 信息
- `DEBUG_LEVEL_WARNING` (2) - 警告
- `DEBUG_LEVEL_ERROR` (3) - 错误，打断运行
- `DEBUG_LEVEL_FATAL` (4) - 致命错误，打断运行

输出位置：
- 文件：`./logs/error_YYYYMMDD.log`
- 开发模式：根据 `showLevel` 输出到页面

## 8. 页面执行示例

### 示例：首页 ([script/page/index.php](../script/page/index.php))

```php
<?php
// 1. 输出页面头部
UI_STRUCTURE::header('首页', 1);

// 2. 添加提示消息
UI_NOTICE::alert('欢迎来到主页！', BS_SUCCESS);

// 3. 输出页面内容
?>
<div class="row">
    <div class="col-md-12">
        <h1>你好，世界！</h1>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <?php
        // 4. 生成数据表格
        $tableData = [
            ['ID', '名称', '日期'],
            ['1', '记录一', '2024-06-01'],
            ['2', '记录二', '2024-06-02'],
        ];
        UI_TABLE::super_downloadable($tableData, TABLE_DEFAULT_CFG, [], [], true, true, true, '最近记录');
        ?>
    </div>
</div>
<?php
// 5. 输出页面底部
UI_STRUCTURE::footer();
?>
```

## 9. 状态管理

### 9.1 VIEWER_STATE ([script/lib/view/glo.php](../script/lib/view/glo.php))

用于跟踪页面渲染状态：

```php
VIEWER_STATE::HEADER_SHOWED()   // 检查头部是否已显示
VIEWER_STATE::FOOTER_SHOWED()   // 检查底部是否已显示
```

### 9.2 StateStorage ([script/lib/stateStorage.php](../script/lib/stateStorage.php))

临时状态存储（内存）：

```php
StateStorage::set($key, $value, $domain)  // 设置状态
StateStorage::get($key, $domain)           // 获取状态
```

## 10. 常见问题

### Q1: 如何添加新的路由？

在 `config/route.php` 中添加配置：
```php
["/new-route", "newpage", "permission_name"]
```
然后创建 `script/page/newpage.php`

### Q2: 如何获取 URL 参数？

```php
$param = Router::getParam('param_name');
// 或
$params = Router::getParams();
```

### Q3: 如何调试路由匹配？

在开发模式下，日志会输出路由匹配过程：
```php
DEBUGGER::LOG("Route matched: " . $matchedRoute);
```

### Q4: 如何自定义错误页面？

在 `script/view/error/` 目录下创建对应的错误页面文件：
- `404.php` - 404 错误
- `403.php` - 权限错误
- `500.php` - 服务器错误
