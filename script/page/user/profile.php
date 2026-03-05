<?php

$userInfo = MyAcc->getCurrentUserInfo(USER::ALL_ROWS);

// 获取部门信息
$deptName = '未分配';
if (!empty($userInfo[USER::KEY_DEPARTMENT])) {
    $dept = DEPARTMENT::getById($userInfo[USER::KEY_DEPARTMENT]);
    if ($dept) {
        $deptName = htmlspecialchars($dept[DEPARTMENT::KEY_NAME]);
    }
}

// 初始化RBAC并获取权限信息
RBAC::init();
$allPermissions = RBAC::getAllPermissions();
$userPermissions = MyAcc->getUserAuthority();

// 获取用户已拥有的权限列表
$activePermissions = [];
$inactivePermissions = [];
foreach ($allPermissions as $permCode => $permName) {
    if (($userPermissions[$permCode] ?? false) === true) {
        $activePermissions[$permCode] = $permName;
    } else {
        $inactivePermissions[$permCode] = $permName;
    }
}

UI_STRUCTURE::header("我的账户", 1, "个人资料");
?>
<style>
.permissions-list {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
}

.permission-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    margin-bottom: 6px;
    background: #f8f9fa;
    border-radius: 6px;
    transition: background-color 0.2s ease;
    margin: 5px;
}

.permission-item:hover {
    background: #e9ecef;
}

.permission-active {
    background: #d1e7dd;
    color: white;
}

.permission-active:hover {
    background: #c0d3ec;
}

.permission-icon {
    margin-right: 10px;
    color: #198754;
}

.permission-active .permission-icon {
    color: #ffffff;
}

.permission-name {
    font-weight: 500;
    flex: 1;
}

.permission-code {
    font-size: 0.8em;
    opacity: 0.7;
}

.accordion-button .title {
    display: flex;
    align-items: center;
}

.accordion-button .accordion-icon {
    transition: transform 0.2s ease;
}

.accordion-button:not(.collapsed) .accordion-icon {
    transform: rotate(180deg);
}
</style>

<div class="row justify-content-center">
    <div class="col-xl-5 col-lg-6 col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body descriptionbody">
                <div class="header">
                    <div class="image">
                        <img src="<?= user::getAvatar($userInfo) ?>" class="img-fluid rounded-circle" alt="">
                    </div>
                    <div class="username">
                        <span class="desc"><?= htmlspecialchars($userInfo[USER::KEY_NICKNAME] ?? $userInfo[USER::KEY_USERNAME]) ?></span>
                        <span class="sub-desc">账号 <?= htmlspecialchars($userInfo[USER::KEY_USERNAME]) ?></span>
                    </div>
                </div>
                <hr>
                <div class="selfdesc">
                    <div class="desc-row">
                        <span class="title">
                            <span class="icon"><?= UI_ICON::bi("hash") ?></span>
                            <span class="text">状态</span>
                        </span>
                        <span class="desc"><?= USER::getStatusName($userInfo[USER::KEY_STATUS]) ?></span>
                    </div>
                    <div class="desc-row">
                        <span class="title">
                            <span class="icon"><?= UI_ICON::bi("check-circle") ?></span>
                            <span class="text">身份</span>
                        </span>
                        <span class="desc"><?= USER::getRoleName($userInfo[USER::KEY_ROLE]) ?></span>
                    </div>
                    <div class="desc-row">
                        <span class="title">
                            <span class="icon"><?= UI_ICON::bi("building") ?></span>
                            <span class="text">部门</span>
                        </span>
                        <span class="desc"><?= $deptName ?></span>
                    </div>
                    <div class="desc-row">
                        <span class="title">
                            <span class="icon"><?= UI_ICON::bi("telephone") ?></span>
                            <span class="text">电话</span>
                        </span>
                        <span class="desc"><?= htmlspecialchars($userInfo[USER::KEY_PHONE] ?? '未设置') ?></span>
                    </div>
                    <div class="desc-row">
                        <span class="title">
                            <span class="icon"><?= UI_ICON::bi("envelope-fill") ?></span>
                            <span class="text">邮箱</span>
                        </span>
                        <span class="desc"><?= htmlspecialchars($userInfo[USER::KEY_EMAIL] ?? '未设置') ?></span>
                    </div>
                    <div class="desc-row">
                        <span class="title">
                            <span class="icon"><?= UI_ICON::bi("calendar") ?></span>
                            <span class="text">注册时间</span>
                        </span>
                        <span class="desc"><?= htmlspecialchars(getDate_full(getDate_Auto($userInfo[USER::KEY_CREATE_TIME]))) ?></span>
                    </div>
                    <div class="desc-row">
                        <span class="title">
                            <span class="icon"><?= UI_ICON::bi("calendar-plus") ?></span>
                            <span class="text">上次活动</span>
                        </span>
                        <span class="desc"><?= htmlspecialchars(getDate_full(getDate_Auto($userInfo[USER::KEY_UPDATE_TIME]))) ?></span>
                    </div>
                    <?php if (!empty($userInfo[USER::KEY_PROFILE][USER::KEY_PROFILE])): ?>
                    <div class="desc-row">
                        <span class="title">
                            <span class="icon"><?= UI_ICON::bi("card-text") ?></span>
                            <span class="text">个人备注</span>
                        </span>
                        <span class="desc"><?= nl2br(htmlspecialchars($userInfo[USER::KEY_PROFILE][USER::KEY_PROFILE])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 权限信息 -->
                <hr>
                <div class="accordion" id="permissionsAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="permissionsHeading">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#permissionsCollapse" aria-expanded="false" aria-controls="permissionsCollapse">
                                <span class="title">
                                    <span class="icon"><?= UI_ICON::bi("shield-check") ?></span>
                                    <span class="text">我的权限</span>
                                </span>
                                <span class="badge bg-info ms-2"><?= count($activePermissions) ?></span>
                                <span class="accordion-icon ms-auto">
                                </span>
                            </button>
                        </h2>
                        <div id="permissionsCollapse" class="accordion-collapse collapse" aria-labelledby="permissionsHeading" data-bs-parent="#permissionsAccordion">
                            <div class="accordion-body">
                                <div class="permissions-list">
                                    <?php if (!empty($activePermissions)): ?>
                                        <?php foreach ($activePermissions as $permCode => $permName): ?>
                                            <div class="permission-item permission-active">
                                                <span class="permission-icon">
                                                    <?= UI_ICON::bi('check-circle-fill') ?>
                                                </span>
                                                <span class="permission-name"><?= htmlspecialchars($permName) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">当前没有配置任何权限。</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="buttons">
                    <button onclick='location.href="/user/profile/edit"' class="btn btn-icon btn-inverse-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="修改个人信息"><?= UI_ICON::bi('pencil-fill') ?></button>
                    <button onclick='location.href="/user/profile/safety"' class="btn btn-icon btn-inverse-success" data-bs-toggle="tooltip" data-bs-placement="bottom" title="账号安全"><?= UI_ICON::bi('shield-fill-plus') ?></button>
                    <button onclick='location.href="/user/logout"' class="btn btn-icon btn-inverse-danger" data-bs-toggle="tooltip" data-bs-placement="bottom" title="退出登录"><?= UI_ICON::bi('box-arrow-right') ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php UI_STRUCTURE::footer(); ?>