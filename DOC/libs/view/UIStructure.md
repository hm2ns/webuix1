# view\UIStructure.php

**文件路径**: `\script\lib\view\UIStructure.php`

## 类定义

### 类: `UI_STRUCTURE`

一个页面最基本的UI构成

## 函数定义

### 方法: `setImport`


设置导入栈中的值（内部使用）

### 方法: `appendImport`

累加导入栈中的值（用于消息、JS 等）

### 方法: `getImport`

获取导入栈值

### 方法: `header`

输出页面头部HTML结构
@param string $title 页面标题，默认为"首页"
@param int $nav 导航栏类型：0-正常显示, 1-仅显示图标, 2-仅顶部导航, 3-无导航
@param string $pagename 页面名称，用于标识当前页面，默认为空则使用$title
@param string $oh 额外的HTML头部内容，如自定义CSS、meta标签等
@return void
@example
// 基本用法
UI_STRUCTURE::header("用户管理", 0, "user_manage");
// 自定义头部内容
UI_STRUCTURE::header(
"仪表板",
0,
"dashboard",
'<meta name="description" content="系统仪表板页面">'
);
@note 此方法会自动防止重复调用，多次调用只有第一次生效
@see VIEWER_STATE::HEADER_SHOWED() 检查头部是否已显示

### 方法: `footer`

输出页面底部HTML结构并结束页面
@return void
@example
// 标准用法，在页面内容输出完成后调用
UI_STRUCTURE::footer();
@note 此方法会自动防止重复调用，多次调用只有第一次生效
@note 会自动调用import()方法输出累积的消息框、警告框、JS和CSS
@see UI_STRUCTURE::import() 输出累积的资源
@see VIEWER_STATE::FOOTER_SHOWED() 检查底部是否已显示

### 方法: `import`

输出累积的页面资源（消息框、警告框、JS、CSS）并关闭HTML标签
@return void
@example
// 通常在footer()中自动调用，也可手动调用
UI_STRUCTURE::import();
@note 此方法会清空所有资源栈，避免重复输出
@note 输出顺序：MessageBox -> Alert -> JS -> CSS -> HTML结束标签
@see UI_STRUCTURE::appendImport() 向资源栈添加内容
@see UI_STRUCTURE::setImport() 设置资源栈内容

### 方法: `pageCutNav`

生成分页导航HTML
@param int $now 当前页码
@param int $total 总页数
@param string $url 基础URL，会在后面追加?page=参数
@return void 直接输出HTML到浏览器
@example
// 基本用法
UI_STRUCTURE::pageCutNav(3, 15, "/user/list");
// 输出：上一页 1 2 [3] 4 5 ... 15 下一页
// 带查询参数的URL
UI_STRUCTURE::pageCutNav(2, 8, "/search?keyword=admin");
// 输出：上一页 [1] 2 3 4 5 6 7 8 下一页
@note 当总页数<=1时不会输出任何内容
@note 当总页数<=10时显示所有页码
@note 当总页数>10时采用智能省略策略，保持当前页前后各2个页码可见
@note 会自动处理URL中已存在的查询参数
