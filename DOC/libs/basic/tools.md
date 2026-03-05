# basic\tools.php

**文件路径**: `\script\lib\basic\tools.php`

## 函数定义

### 方法: `uuidGenerator`

生成安全可靠的唯一ID
@param string $prefix ID前缀（可选）
@param string $suffix ID后缀（可选）
@return string 生成的唯一ID

### 方法: `parseUserAgent`

解析用户代理字符串，返回设备类型、浏览器和操作系统信息
@param string|null $userAgent 用户代理字符串，如果为null则使用当前请求的UA
@return array 包含设备类型、浏览器和操作系统信息的关联数组
@example
$userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36";
$result = parseUserAgent($userAgent);
print_r($result);
输出：
Array
(
[device_type] => 计算机
[browser] => Google Chrome
[os] => Windows 10
)

### 方法: `safeContent`

将用户输入的内容进行安全处理，并替换换行
@param string $content 用户输入的内容
@return string 处理后的安全内容

### 方法: `log_caller`

记录调用者信息，返回调用者的文件和行号等信息
@param int $floor 调用层级，默认为0，表示返回所有调用者信息，1表示返回直接调用者信息，以此类推
@return array 包含调用者信息的数组，如果floor为0，则返回所有调用者信息的数组，否则返回指定层级调用者的信息数组
@example
function test() {
$callerInfo = log_caller(1);
print_r($callerInfo);
}
test();
输出：
Array
(
[file] => /path/to/caller.php
[line] => 10
[function] => test
[args] => Array
(
)
)

### 函数: `uuidGenerator`

生成安全可靠的唯一ID
@param string $prefix ID前缀（可选）
@param string $suffix ID后缀（可选）
@return string 生成的唯一ID

### 函数: `parseUserAgent`

解析用户代理字符串，返回设备类型、浏览器和操作系统信息
@param string|null $userAgent 用户代理字符串，如果为null则使用当前请求的UA
@return array 包含设备类型、浏览器和操作系统信息的关联数组
@example
$userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36";
$result = parseUserAgent($userAgent);
print_r($result);
输出：
Array
(
[device_type] => 计算机
[browser] => Google Chrome
[os] => Windows 10
)

### 函数: `safeContent`

将用户输入的内容进行安全处理，并替换换行
@param string $content 用户输入的内容
@return string 处理后的安全内容

### 函数: `log_caller`

记录调用者信息，返回调用者的文件和行号等信息
@param int $floor 调用层级，默认为0，表示返回所有调用者信息，1表示返回直接调用者信息，以此类推
@return array 包含调用者信息的数组，如果floor为0，则返回所有调用者信息的数组，否则返回指定层级调用者的信息数组
@example
function test() {
$callerInfo = log_caller(1);
print_r($callerInfo);
}
test();
输出：
Array
(
[file] => /path/to/caller.php
[line] => 10
[function] => test
[args] => Array
(
)
)
