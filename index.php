<?php
include "script/lib/loader.php";
// 初始化系统配置

const ROOTDIR = __DIR__ . "/";

if (!GLOBAL_CONFIG::init()) {
    header("HTTP/1.1 500");
    echo "500 SERVER ERROR";
    exit;
}

DEBUGGER::setDebug(GLOBAL_CONFIG::is_dev());
DEBUGGER::setShowLevel(DEBUGGER::DEBUG_LEVEL_WARNING);

if (!REQUEST::init()) {
    DEBUGGER::log("[FATAL] 请求处理模块初始化失败", DEBUGGER::DEBUG_LEVEL_FATAL);
    exit;
}

SECURITY::init();
RBAC::init();

session_start();

$userInstance = USER::getInstance();

define('MyAcc', $userInstance); // 当前用户实例


// 定义日志记录闭包
$logClosure = function () use (&$AccessStatus, &$userInstance) {
    $logPath = ROOTDIR . "logs/access." . getDate_daily() . ".log";
    $userId = ($userInstance && method_exists($userInstance, 'isLoggedIn') && $userInstance->isLoggedIn())
        ? MyAcc->getCurrentUserId() : "GST";

    $log = "[" . getDate_full() . "] " . REQUEST::ip() . " " .
        REQUEST::method() ." " . $AccessStatus . " " . REQUEST::uri() .  " " . $userId . "\n";

    // 防止目录不存在导致报错
    if (!is_dir(dirname($logPath))) {
        mkdir(dirname($logPath), 0755, true);
    }
    file_put_contents($logPath, $log, FILE_APPEND);
};

// 注册关闭函数 (处理 exit 情况)
register_shutdown_function($logClosure);

$AccessStatus = "UKNOWN";

try {
    if (!SECURITY::refererCheck()) {
        UI_ERRORPAGES::show("CRSF", "检测到疑似跨站请求伪造，请返回首页后重新进入此页面👇");
        $AccessStatus = "CSRF";
    } elseif (SECURITY::isIPBanned()) {
        UI_ERRORPAGES::show("IPBANNED", "您当前的IP已被加入黑名单");
        $AccessStatus = "IPBANNED";
    } else {
        Router::init($userInstance->getUserAuthority(), $userInstance->isLoggedIn());
        $path = Router::getScriptPath(REQUEST::uri());
        if ($path) {
            $path = includePage($path);
            if (file_exists($path)) {
                $AccessStatus = "DEALING";
                include $path;
                $AccessStatus = "DONE";
            } else {
                UI_ERRORPAGES::show(404);
                $AccessStatus = "404";
            }
        }
    }
} catch (Exception $e) {
    DEBUGGER::log("[EXCEPTION] 未捕获的异常 " . $e->getMessage(), DEBUGGER::DEBUG_LEVEL_ERROR);
    UI_ERRORPAGES::show("EXCEPTION", "发生了一个错误，请稍后再试");
}

return;
