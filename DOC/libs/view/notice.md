# view\notice.php

**文件路径**: `\script\lib\view\notice.php`

## 类定义

### 类: `UI_NOTICE`

消息与通知系统
提供页面_alert提示、消息通知、桌面通知等功能
支持多种通知类型和自定义样式

## 函数定义

### 方法: `alert`

显示alert提示框
@param string $text 提示文本内容
@param string $type 提示类型：success/info/warning/danger，默认为"info"
@param int $currenttime 自动消失时间（毫秒），默认5000ms
@return string 返回原始文本内容
@example
// 基本用法
UI_NOTICE::alert("操作成功！", "success");
// 自定义消失时间
UI_NOTICE::alert("请注意！", "warning", 3000);
@note 提示框会自动添加关闭按钮和定时消失功能
@note 文本内容会自动进行HTML转义防止XSS攻击
@see UI_STRUCTURE::appendImport() 将内容添加到alert资源栈

### 方法: `notice`

显示消息通知框（Toast样式）
@param string $text 消息正文内容
@param string $title 消息标题，默认为"消息"
@param string $icon 图标名称，支持UI_ICON图标库，默认为"bell"
@param string $time 时间显示文本，默认为"刚刚"
@return void
@example
// 基本消息通知
UI_NOTICE::notice("您有一条新消息", "系统通知", "envelope");
// 自定义时间和图标
UI_NOTICE::notice("任务已完成", "完成提醒", "check-circle", "2分钟前");
@note 消息框会prepend到消息容器开头位置
@note 支持Bootstrap Toast组件的所有特性
@see UI_ICON::icon() 获取图标HTML
@see UI_STRUCTURE::setImport() 将内容设置到messagebox资源栈

### 方法: `addNotice`

添加可点击的消息通知
@param string $text 消息正文内容
@param string $title 消息标题，默认为"消息"
@param string $icon 图标名称，默认为"bell"
@param string $url 点击后跳转的URL地址，默认为空不跳转
@param int $show 是否立即显示：0-不显示，1-立即显示，默认为0
@param string $time 时间显示文本，默认为"刚刚"
@return void
@example
// 添加可点击通知但不立即显示
UI_NOTICE::addNotice("点击查看详细信息", "新订单通知", "cart", "/order/detail/123", 0);
// 立即显示通知
UI_NOTICE::addNotice("系统维护通知", "维护提醒", "wrench", "#", 1, "1小时前");
@note 当$show=1时会同时调用notice()方法立即显示
@note 通过JavaScript的addMessage函数处理点击事件
@note 所有参数都会进行字符串转义处理防止脚本注入
@see UI_NOTICE::notice() 立即显示消息通知
@see UI_STRUCTURE::appendImport() 添加JavaScript代码

### 方法: `activeNoticeICON`

激活消息通知图标
@return void
@example
// 激活通知图标显示未读消息数量
UI_NOTICE::activeNoticeICON();
@note 调用JavaScript的activeMB()函数
@note 通常在有新消息时调用此方法
@see UI_NOTICE::deactiveNoticeICON() 取消激活状态
@see UI_STRUCTURE::appendImport() 添加激活脚本

### 方法: `deactiveNoticeICON`

取消激活消息通知图标
@return void
@example
// 清除通知图标上的未读标记
UI_NOTICE::deactiveNoticeICON();
@note 调用JavaScript的deactiveMB()函数
@note 通常在用户查看完所有消息后调用
@see UI_NOTICE::activeNoticeICON() 激活通知图标
@see UI_STRUCTURE::appendImport() 添加取消激活脚本
