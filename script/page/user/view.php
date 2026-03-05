<?php
$userName = Router::getParam("id");

if (!$userName) {
    UI_ERRORPAGES::show(404, "未找到指定的用户");
    exit;
}

$userInfo = USER::getUserInfoByUsername($userName, USER::ALL_ROWS);
if (!$userInfo) {
    UI_ERRORPAGES::show(404, "未找到指定的用户");
    exit;
}

// 获取部门信息
$deptName = '未分配';
if (!empty($userInfo[USER::KEY_DEPARTMENT])) {
    $dept = DEPARTMENT::getById($userInfo[USER::KEY_DEPARTMENT]);
    if ($dept) {
        $deptName = htmlspecialchars($dept[DEPARTMENT::KEY_NAME]);
    }
}
UI_STRUCTURE::header("访问个人空间", 1, "个人资料");
?>
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
                <div class="buttons">
                    <button onclick='location.href="/user/profile"' class="btn btn-icon btn-inverse-primary" data-bs-toggle="tooltip" data-bs-placement="bottom" title="返回我的主页"><?= UI_ICON::bi('house') ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php UI_STRUCTURE::footer(); ?>