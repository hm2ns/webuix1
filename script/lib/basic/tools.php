<?php

/**
 * 生成安全可靠的唯一ID
 * 
 * @param string $prefix ID前缀（可选）
 * @param string $suffix ID后缀（可选）
 * @return string 生成的唯一ID
 */
function uuidGenerator($prefix = "", $suffix = "")
{
    // 生成16字节(128位)加密安全随机数据
    $bytes = random_bytes(16);

    // 设置UUID版本(4)和变体(RFC 4122)
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40); // 版本4
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80); // 变体10xx

    // 转换为标准UUID格式(8-4-4-4-12)
    $uuid = sprintf(
        '%s%s-%s-%s-%s-%s%s%s',
        bin2hex(substr($bytes, 0, 4)),
        bin2hex(substr($bytes, 4, 2)),
        bin2hex(substr($bytes, 6, 2)),
        bin2hex(substr($bytes, 8, 2)),
        bin2hex(substr($bytes, 10, 2)),
        bin2hex(substr($bytes, 12, 2)),
        bin2hex(substr($bytes, 14, 2)),
        bin2hex(substr($bytes, 16, 2))
    );

    return $prefix . $uuid . $suffix;
}
/**
 * 解析用户代理字符串，返回设备类型、浏览器和操作系统信息
 * @param string|null $userAgent 用户代理字符串，如果为null则使用当前请求的UA
 * @return array 包含设备类型、浏览器和操作系统信息的关联数组
 * @example
 * $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36";
 * $result = parseUserAgent($userAgent);
 * print_r($result);
 * 输出：
 * Array
 * (
 *   [device_type] => 计算机
 *   [browser] => Google Chrome
 *   [os] => Windows 10
 * )
 */
function parseUserAgent($userAgent = null)
{
    // 如果没有提供UA，则使用当前请求的UA
    if ($userAgent === null) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    // 初始化结果
    $result = [
        'device_type' => 'unknown',
        'browser' => 'unknown',
        'os' => 'unknown'
    ];

    // 检测设备类型
    if (preg_match('/Mobile|Android|iPhone|iPad|iPod|Windows Phone|BlackBerry|Opera Mini|IEMobile/i', $userAgent)) {
        if (preg_match('/iPad/i', $userAgent)) {
            $result['device_type'] = '便携设备/平板';
        } elseif (preg_match('/Mobile|Android|iPhone|iPod|Windows Phone|BlackBerry|Opera Mini|IEMobile/i', $userAgent)) {
            $result['device_type'] = '移动设备';
        } else {
            $result['device_type'] = '移动设备';
        }
    } else {
        $result['device_type'] = '计算机';
    }

    // 检测操作系统
    if (preg_match('/Windows NT 10\.0/i', $userAgent)) {
        $result['os'] = 'Windows 10';
    } elseif (preg_match('/Windows NT 6\.3/i', $userAgent)) {
        $result['os'] = 'Windows 8.1';
    } elseif (preg_match('/Windows NT 6\.2/i', $userAgent)) {
        $result['os'] = 'Windows 8';
    } elseif (preg_match('/Windows NT 6\.1/i', $userAgent)) {
        $result['os'] = 'Windows 7';
    } elseif (preg_match('/Windows NT 6\.0/i', $userAgent)) {
        $result['os'] = 'Windows Vista';
    } elseif (preg_match('/Windows NT 5\.1|Windows XP/i', $userAgent)) {
        $result['os'] = 'Windows XP';
    } elseif (preg_match('/Mac OS X/i', $userAgent)) {
        $result['os'] = 'macOS';
    } elseif (preg_match('/Android/i', $userAgent)) {
        $result['os'] = 'Android';
    } elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
        $result['os'] = 'iOS';
    } elseif (preg_match('/Linux/i', $userAgent)) {
        $result['os'] = 'Linux';
    }

    // 检测浏览器
    if (preg_match('/Edge\/|Edg\//i', $userAgent)) {
        $result['browser'] = 'Microsoft Edge';
    } elseif (preg_match('/Chrome\/(.*?)\s/i', $userAgent, $matches) && !preg_match('/OPR\//i', $userAgent)) {
        $result['browser'] = 'Google Chrome';
    } elseif (preg_match('/Firefox\/(.*?)\s/i', $userAgent, $matches)) {
        $result['browser'] = 'Mozilla Firefox';
    } elseif (preg_match('/OPR\//i', $userAgent)) {
        $result['browser'] = 'Opera';
    } elseif (preg_match('/Safari\/(.*?)\s/i', $userAgent, $matches) && !preg_match('/Chrome/i', $userAgent)) {
        $result['browser'] = 'Safari';
    } elseif (preg_match('/MSIE\s(.*?)\;/i', $userAgent, $matches) || preg_match('/Trident\/.*?rv:(.*?)\;/i', $userAgent, $matches)) {
        $result['browser'] = 'Internet Explorer';
    } else {
        // 尝试提取其他浏览器
        if (preg_match('/(Opera|Netscape|Konqueror|SeaMonkey)/i', $userAgent, $matches)) {
            $result['browser'] = $matches[1];
        }
    }

    return $result;
}
/**
 * 将用户输入的内容进行安全处理，并替换换行
 * @param string $content 用户输入的内容
 * @return string 处理后的安全内容
 */
function safeContent($content)
{

    $content = htmlspecialchars($content);
    $content = str_replace("\n", "<br>", $content);
    return $content;
}
/**
 * 记录调用者信息，返回调用者的文件和行号等信息
 * @param int $floor 调用层级，默认为0，表示返回所有调用者信息，1表示返回直接调用者信息，以此类推
 * @return array 包含调用者信息的数组，如果floor为0，则返回所有调用者信息的数组，否则返回指定层级调用者的信息数组
 * @example
 * function test() {
 *   $callerInfo = log_caller(1);
 *  print_r($callerInfo);
 * }
 * test();
 * 输出：
 * Array
 * (
 *  [file] => /path/to/caller.php
 *  [line] => 10
 *  [function] => test
 *  [args] => Array
 *   (
 *    )
 * )
 */
function log_caller($floor = 0): array
{
    $backtrace = debug_backtrace();
    // 第0层是当前函数（log_caller），第1层是调用它的函数
    if ($floor <= 0) {
        unset($backtrace[0]);
        return array_values($backtrace);
    }
    if (isset($backtrace[$floor])) {
        $caller = $backtrace[$floor];
        return $caller;
    } else {
        return ['file' => 'unknown', 'line' => 'unknown'];
    }
}

function alert($msg)
{
    echo "<script>alert('$msg')</script>";
}
function jsjump($u)
{
    echo "<script>window.location='$u'</script>";
}
function jsreload()
{
    echo "<script>window.location.reload()</script>";
}
