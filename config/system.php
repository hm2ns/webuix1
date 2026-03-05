<?php
// 系统配置文件
return [
    'ENV' => 'SA',
    'titleSuffix' => '-WebUI-X',
    'logo' => '/static/image/tool/logo.png',
    'logo_long' => '/static/image/tool/logo_long.png',
    'site_url'=>"http://uix.zsvstudio.top/",

    'db' => array(
        'db_host' => 'localhost',
        'db_port' => '5432',
        'db_name' => 'DEMO',
        'db_user' => "DEMO",
        'db_pass' => 'DEMO',
        'db_driver' => 'pgsql',  //目前只支持 pgsql
    ),

    'redis'=>array(
        'host'=>'localhost',
        'port'=>6379,
        'password'=>'',
        'timeout'=>0,
        'baserange'=>1, //可用的表数量
        'offset'=>0, //表示可以使用0~(baserange-1)号表
    ),

    "departments"=>array( //默认部门列表，可通过数据库添加
        "SAO"=>"学生工作处",
        "IT"=>"运维团队",
    ),

    "dev_mode"=>true, //开发模式，开启后会显示更多错误信息和调试日志，生产环境请务必关闭
];
