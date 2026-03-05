# debugger.php

**文件路径**: `\script\lib\debugger.php`

## 函数定义

### 方法: `LOG`

日志记录函数
@param string $msg 日志信息
@param int $level 日志级别，默认为INFO级别
级别说明：
DEBUG_LEVEL_NONE: 无日志输出
DEBUG_LEVEL_INFO: 输出信息日志
DEBUG_LEVEL_WARNING: 输出警告日志
DEBUG_LEVEL_ERROR: 输出错误日志，并打断运行
DEBUG_LEVEL_FATAL: 输出致命错误日志，并打断运行
日志会记录调用栈信息，方便调试和定位问题
日志会保存到logs目录下的error_YYYYMMDD.log文件中，并且在调试模式下根据设置的显示级别输出到控制台
如果日志级别为ERROR或FATAL，函数会尝试发送HTTP 500错误响应，并终止脚本执行，以防止继续运行可能导致更严重问题的代码
注意：在某些环境下可能无法修改HTTP头部，此时函数会直接输出"500 SERVER ERROR"并终止执行，以确保错误信息能够被正确传达给用户或调用者
@return void

### 方法: `REQUIRE_CLASS`

检测类是否存在，不存在就截断运行
@param string $classname 类名
@return void

### 函数: `REQUIRE_CLASS`

日志记录函数
@param string $msg 日志信息
@param int $level 日志级别，默认为INFO级别
级别说明：
DEBUG_LEVEL_NONE: 无日志输出
DEBUG_LEVEL_INFO: 输出信息日志
DEBUG_LEVEL_WARNING: 输出警告日志
DEBUG_LEVEL_ERROR: 输出错误日志，并打断运行
DEBUG_LEVEL_FATAL: 输出致命错误日志，并打断运行
日志会记录调用栈信息，方便调试和定位问题
日志会保存到logs目录下的error_YYYYMMDD.log文件中，并且在调试模式下根据设置的显示级别输出到控制台
如果日志级别为ERROR或FATAL，函数会尝试发送HTTP 500错误响应，并终止脚本执行，以防止继续运行可能导致更严重问题的代码
注意：在某些环境下可能无法修改HTTP头部，此时函数会直接输出"500 SERVER ERROR"并终止执行，以确保错误信息能够被正确传达给用户或调用者
@return void
/
public static function LOG(string $msg, int $level = self::DEBUG_LEVEL_INFO)
{
$callTree = log_caller(-1);
foreach ($callTree as $k => $caller) {
$msg .= "\n    \\" . $caller['file'] . ":" . $caller['line'] . "";
}
$logfile = fopen("./logs/error_" . date("Ymd") . ".log", "a");
fwrite($logfile, date("Y-m-d H:i:s") . " " . REQUEST::IP() . $msg . "\n");
fclose($logfile);
error_log($msg);
if (self::$debug && $level >= self::$showlevel) {
echo $msg . "\n";
}
if ($level >= self::DEBUG_LEVEL_ERROR) {
// 错误打断运行
if (!headers_sent()) {
header("HTTP/1.1 500");
} else {
// 在某些环境下可能无法修改HTTP头部，此时直接输出错误信息
echo "500 SERVER ERROR";
}
exit;
}
}
}
/**
检测类是否存在，不存在就截断运行
@param string $classname 类名
@return void
