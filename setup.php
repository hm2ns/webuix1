<?php
include "script/lib/loader.php";
// 系统安装步骤

const ROOTDIR = __DIR__ . "/";

$setupcfg = [];

const SETUPCFG = ROOTDIR . "config/setup.php";
if (file_exists(SETUPCFG)) {
    $setupcfg = require SETUPCFG;
} else {
    header("HTTP/1.1 500");
    echo "500 SERVER ERROR";
    exit;
}

if (empty($setupcfg)) {
    header("HTTP/1.1 500");
    echo "ALL SETUP HAS BEEN DONE";
    exit;
}

if (!GLOBAL_CONFIG::init()) {
    header("HTTP/1.1 500");
    echo "500 SERVER ERROR";
    exit;
}

DEBUGGER::setDebug(GLOBAL_CONFIG::is_dev());
DEBUGGER::setShowLevel(DEBUGGER::DEBUG_LEVEL_INFO);

if (!REQUEST::init()) {
    DEBUGGER::log("[FATAL] 请求处理模块初始化失败", DEBUGGER::DEBUG_LEVEL_FATAL);
    exit;
}

SECURITY::init();
RBAC::init();

foreach ($setupcfg as $key => $value) {
    try {
        if (require ROOTDIR . "setupfiles/" . $value . ".php") {
            unset($setupcfg[$key]);
            $setupcfg = array_unique($setupcfg);
            file_put_contents(SETUPCFG, "<?php return " . var_export($setupcfg, true) . ";");
            if (!headers_sent()) {
                echo "<h1>SETUP Of Moudle $value SUCCESS</h1>";
            }
            exit;
        } else {
            exit;
        }
    } catch (Exception $e) {
        DEBUGGER::log("[FATAL] 模块 $value 初始化失败", DEBUGGER::DEBUG_LEVEL_FATAL);
        exit;
    }
}
