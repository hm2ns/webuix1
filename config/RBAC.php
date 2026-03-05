<?php
return [
    // 权限名称定义
    "permissions" => [
        PEM_USERMANAGE => '用户管理',
        PEM_DEPARTMENTMANAGE => '部门管理',
        PEM_AUTHMANAGE => '权限组管理',
    ],

    "groups" => [
        "default" => [  //必须！ 后续权限组可继承此组权限
            PEM_USERMANAGE => false,
            PEM_DEPARTMENTMANAGE => false,
            PEM_AUTHMANAGE => false,
        ],
        "guest" => "default",
    ]
];
