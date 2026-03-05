<?php
return [
    //第一个参数为Uri，第二个参数为对应文件相对路径 script\page\...
    ["/", "index"],

    // 用户管理路由
    ["user/login", "user/login", Router::MODULE_NOT_LOGINED],
    ["user/register", "user/register", Router::MODULE_NOT_LOGINED],
    ["user/forgot", "user/forgot", Router::MODULE_NOT_LOGINED],
    ["user/f2nd", "user/forgot2nd", Router::MODULE_NOT_LOGINED],

    ["user/logout", "user/logout", Router::MODULE_LOGINED],
    ["user/profile", "user/profile", Router::MODULE_LOGINED],
    ["user/view/<id>", "user/view", Router::MODULE_LOGINED],
    ["user/profile/edit", "user/profile_edit", Router::MODULE_LOGINED],
    ["user/profile/safety", "user/profile_safety", Router::MODULE_LOGINED],
    ["user/profile/devices", "user/profile_devices", Router::MODULE_LOGINED],

    ["manage/department/list", "manage/department/list", PEM_DEPARTMENTMANAGE],                // 部门管理-列表
    ["manage/department/add", "manage/department/add", PEM_DEPARTMENTMANAGE],                  // 部门管理-添加
    ["manage/department/edit/<id>", "manage/department/edit", PEM_DEPARTMENTMANAGE],                // 部门管理-编辑
    ["manage/department/delete/<id>", "manage/department/delete", PEM_DEPARTMENTMANAGE],                // 部门管理-删除
    ["manage/department/users/<id>", "manage/department/batchToUsers", PEM_DEPARTMENTMANAGE],    // 部门管理-批量应用到用户（每个用户只能有一个部门）

    ["manage/auth/list", "manage/auth/list", PEM_AUTHMANAGE], // 权限组管理-列表
    ["manage/auth/add", "manage/auth/add", PEM_AUTHMANAGE],  // 权限组管理-添加
    ["manage/auth/edit/<id>", "manage/auth/edit", PEM_AUTHMANAGE], // 权限组管理-编辑
    ["manage/auth/delete/<id>", "manage/auth/delete", PEM_AUTHMANAGE], // 权限组管理-删除
    ["manage/auth/users/<id>", "manage/auth/batchToUsers", PEM_AUTHMANAGE], // 权限组管理-批量应用到用户 （用户数据内只保存合并后的权限）

    ["manage/user/list", "manage/user/list", PEM_USERMANAGE],      // 用户管理-列表
    ["manage/user/add", "manage/user/add", PEM_USERMANAGE],        // 用户管理-添加
    ["manage/user/edit/<id>", "manage/user/edit", PEM_USERMANAGE],      // 用户管理-编辑
    ["manage/user/delete/<id>", "manage/user/delete", PEM_USERMANAGE],  // 用户管理-删除
];
