# WebUI-X 框架总览

## 1. 框架简介

WebUI-X 是一个基于 PHP 开发的轻量级 Web 框架，旨在快速构建功能完善的 Web 应用程序。框架采用模块化设计，提供了完整的 MVC 架构支持、路由系统、权限控制、UI 组件库等功能。

## 2. 目录结构

```
webUI_X/
├── config/              # 配置文件目录
│   ├── system.php      # 系统配置
│   ├── route.php       # 路由配置
│   ├── RBAC.php        # 权限组配置
│   └── security.php    # 安全配置
├── script/             # 核心代码目录
│   ├── lib/            # 类库目录
│   │   ├── access/     # 访问控制模块
│   │   │   ├── request.php    # 请求数据处理
│   │   │   ├── security.php   # 安全控制
│   │   │   ├── router.php     # 路由系统
│   │   │   └── RBAC.php       # RBAC权限控制
│   │   ├── basic/       # 基础工具类
│   │   │   ├── globalconfig.php # 系统配置管理
│   │   │   ├── time.php        # 时间工具
│   │   │   └── tools.php       # 通用工具
│   │   ├── db/          # 数据库模块
│   │   │   ├── pgsql.php      # PostgreSQL数据库
│   │   │   └── redis.php      # Redis缓存
│   │   ├── view/        # 视图/UI组件
│   │   │   ├── UIStructure.php # 页面结构
│   │   │   ├── tables.php      # 表格组件
│   │   │   ├── buttons.php    # 按钮组件
│   │   │   ├── icon.php        # 图标组件
│   │   │   ├── notice.php      # 通知系统
│   │   │   ├── errorPages.php  # 错误页面
│   │   │   ├── aceEditor.php   # 代码编辑器
│   │   │   ├── markdown.php    # Markdown解析
│   │   │   └── glo.php         # UI组件加载器
│   │   ├── debugger.php   # 调试日志
│   │   ├── stateStorage.php # 状态存储
│   │   └── loader.php     # 类库加载器
│   ├── page/         # 页面文件目录
│   │   ├── index.php  # 示例首页
│   │   ├── pdo.php    # PostgreSQL示例
│   │   └── redis.php  # Redis示例
│   └── view/         # 视图模板目录
│       ├── header.php # 页面头部
│       ├── foot.php   # 页面底部
│       ├── nav.php    # 导航栏
│       └── error/     # 错误页面模板
│           ├── 404.php
│           ├── 403.php
│           ├── 500.php
│           └── common.php
├── static/            # 静态资源目录
│   ├── css/          # 样式文件
│   ├── js/           # JavaScript文件
│   └── image/        # 图片资源
├── logs/             # 日志文件目录
└── index.php         # 应用入口文件
```

## 3. 核心模块说明

### 3.1 访问控制模块 (script/lib/access/)

- **REQUEST** ([request.php](../script/lib/access/request.php))
  - 统一处理 HTTP 请求数据
  - 提供 GET、POST、COOKIE、SESSION、FILES 等数据的访问接口
  - 自动解析 URI 和请求树结构

- **SECURITY** ([security.php](../script/lib/access/security.php))
  - IP 黑名单控制
  - CSRF 保护（来源检查）
  - API TOKEN 验证

- **Router** ([router.php](../script/lib/access/router.php))
  - URL 路由系统
  - 支持参数化路由（如 `/user/<id>`）
  - 集成 RBAC 权限检查

- **RBAC** ([RBAC.php](../script/lib/access/RBAC.php))
  - 基于角色的访问控制
  - 权限组继承机制
  - 支持权限组合并

### 3.2 基础工具类 (script/lib/basic/)

- **GLOBAL_CONFIG** ([globalconfig.php](../script/lib/basic/globalconfig.php))
  - 系统配置统一管理
  - 配置项读取接口
  - 开发模式检测

- **TIME** ([time.php](../script/lib/basic/time.php))
  - 时间处理工具函数

- **TOOLS** ([tools.php](../script/lib/basic/tools.php))
  - 通用工具函数集合

### 3.3 数据库模块 (script/lib/db/)

- **PostgreSQL** ([pgsql.php](../script/lib/db/pgsql.php))
  - PostgreSQL 数据库连接和操作
  - 提供统一的查询接口

- **Redis** ([redis.php](../script/lib/db/redis.php))
  - Redis 缓存操作
  - 支持多数据库选择

### 3.4 视图/UI组件 (script/lib/view/)

- **UI_STRUCTURE** ([UIStructure.php](../script/lib/view/UIStructure.php))
  - 页面结构管理（header/footer）
  - 资源导入栈管理（JS、CSS、消息框）
  - 分页导航生成

- **UI_TABLE** ([tables.php](../script/lib/view/tables.php))
  - 表格组件生成
  - 集成 jQuery DataTables
  - 支持导出功能（Excel、CSV）

- **UI_BUTTONS** ([buttons.php](../script/lib/view/buttons.php))
  - 按钮组件生成

- **UI_ICON** ([icon.php](../script/lib/view/icon.php))
  - 图标组件（基于 MDI）

- **UI_NOTICE** ([notice.php](../script/lib/view/notice.php))
  - 消息通知系统
  - Alert 提示框
  - Toast 消息通知

- **UI_ERRORPAGES** ([errorPages.php](../script/lib/view/errorPages.php))
  - 错误页面展示
  - 支持 403、404、500 等错误码

- **UI_ACEEDITOR** ([aceEditor.php](../script/lib/view/aceEditor.php))
  - Ace 代码编辑器集成

- **UI_MARKDOWN** ([markdown.php](../script/lib/view/markdown.php))
  - Markdown 渲染支持

### 3.5 辅助模块

- **DEBUGGER** ([debugger.php](../script/lib/debugger.php))
  - 日志记录系统
  - 多级别日志（INFO、WARNING、ERROR、FATAL）
  - 调试模式控制

- **StateStorage** ([stateStorage.php](../script/lib/stateStorage.php))
  - 会话状态存储
  - 域隔离机制

- **loader** ([loader.php](../script/lib/loader.php))
  - 类库自动加载器

## 4. 框架特性

### 4.1 路由系统
- 支持静态路由和参数化路由
- 自动参数提取（命名捕获组）
- 集成 RBAC 权限控制

### 4.2 权限控制
- 基于 RBAC 的权限管理
- 权限组继承机制
- 支持多种权限合并策略

### 4.3 安全防护
- IP 黑名单过滤
- CSRF 保护
- API TOKEN 验证
- 自动 SQL 注入防护（通过 PDO）

### 4.4 UI 组件
- 基于 Bootstrap 的响应式布局
- 丰富的预置组件（表格、按钮、通知等）
- 模块化资源管理

### 4.5 开发支持
- 完善的日志系统
- 开发/生产模式切换
- 状态存储机制

## 5. 技术栈

- **后端**: PHP 7.0+
- **前端**: Bootstrap 5、jQuery
- **数据库**: PostgreSQL、Redis
- **编辑器**: Ace Editor
- **Markdown**: marked.js

## 6. 快速开始

详细的使用指南请参考后续文档：
- [执行流程说明](02-execution-flow.md)
- [配置说明](03-configuration.md)
- [页面开发指南](04-page-development.md)
