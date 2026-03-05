<?php

// 从路由参数或GET参数获取用户ID
$userId = Router::getParam('id') ?? $_GET['id'] ?? '';

if (empty($userId)) {
    UI_NOTICE::alert('用户ID不能为空', BS_DANGER);
    header("Location: /manage/user/list");
    exit;
}

// 获取用户信息
$user = USER::getUserInfoById($userId, USER::ALL_ROWS);

if (!$user) {
    UI_NOTICE::alert('用户不存在', BS_DANGER);
    header("Location: /manage/user/list");
    exit;
}

$error = '';
$success = '';

// 处理AJAX重置密码请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
    try {
        $newPassword = trim($_POST['new_password'] ?? '');

        if (empty($newPassword)) {
            throw new InvalidArgumentException('新密码不能为空');
        }

        if (strlen($newPassword) < 6) {
            throw new InvalidArgumentException('密码长度至少为6位');
        }

        // 更新密码
        $result = USER::updateUser([
            USER::KEY_ID => $userId,
            USER::KEY_PASSWORD => password_hash($newPassword, USER::PASSWORD_CODER)
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => '密码重置成功']);
        } else {
            throw new RuntimeException('密码重置失败，请重试');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 确认删除（POST请求）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $confirm = $_POST['confirm'] ?? '';

        if ($confirm !== 'yes') {
            throw new InvalidArgumentException('请确认删除操作');
        }

        // 执行删除
        $result = USER::deleteUser($userId);

        if ($result) {
            $success = '用户删除成功！';
            UI_NOTICE::alert($success, BS_SUCCESS);

            // 延迟跳转，让用户看到成功消息
            UI_STRUCTURE::appendImport('js', "setTimeout(() => { location.href = '/manage/user/list'; }, 1500);");
        } else {
            throw new RuntimeException('用户删除失败，请重试');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        UI_NOTICE::alert($error, BS_DANGER);
    }
}

// 获取用户所在部门信息
$deptName = '未分配';
if (!empty($user[USER::KEY_DEPARTMENT])) {
    $dept = DEPARTMENT::getById($user[USER::KEY_DEPARTMENT]);
    if ($dept) {
        $deptName = htmlspecialchars($dept[DEPARTMENT::KEY_NAME]);
    }
}

UI_STRUCTURE::header('删除用户', 1, '用户管理');
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">删除用户</h4>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <?= UI_ICON::bi('check-circle', 'me-2 fs-4') ?>
                            <div>
                                <strong>删除成功</strong>
                                <div class="small"><?= htmlspecialchars($success) ?></div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php else: ?>

                    <!-- 警告信息 -->
                    <div class="alert alert-warning" role="alert">
                        <h5 class="alert-heading"><?= UI_ICON::bi('exclamation-triangle') ?> 警告</h5>
                        <p class="mb-0">您即将删除用户，此操作不可恢复！</p>
                    </div>

                    <!-- 用户信息 -->
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-3">用户信息</h5>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <strong>用户ID：</strong>
                                    <code><?= htmlspecialchars($user[USER::KEY_ID]) ?></code>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>用户名：</strong>
                                    <?= htmlspecialchars($user[USER::KEY_USERNAME]) ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>昵称：</strong>
                                    <?= htmlspecialchars($user[USER::KEY_NICKNAME]) ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>邮箱：</strong>
                                    <?= htmlspecialchars($user[USER::KEY_EMAIL] ?? '未设置') ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>手机号：</strong>
                                    <?= htmlspecialchars($user[USER::KEY_PHONE] ?? '未设置') ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>角色：</strong>
                                    <?= USER::getRoleName($user[USER::KEY_ROLE]) ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>部门：</strong>
                                    <?= $deptName ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>状态：</strong>
                                    <?= USER::getStatusName($user[USER::KEY_STATUS]) ?>
                                </div>
                            </div>
                            <?php if (!empty($user[USER::KEY_PROFILE]) && is_string($user[USER::KEY_PROFILE])): ?>
                                <hr>
                                <div>
                                    <strong>个人简介：</strong>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($user[USER::KEY_PROFILE])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 统计信息 -->
                    <div class="alert alert-info" role="alert">
                        <h5 class="alert-heading"><?= UI_ICON::bi('clock') ?> 账户信息</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>创建时间：</strong><?= getDate_full(getDate_Auto($user[USER::KEY_CREATE_TIME] ?? '')) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>最后更新：</strong><?= getDate_full(getDate_Auto($user[USER::KEY_UPDATE_TIME] ?? '')) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- 错误信息 -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- 删除确认表单 -->
                    <form method="POST">
                        <input type="hidden" name="confirm" value="yes">

                        <div class="d-flex justify-content-between">
                            <a href="/manage/user/edit/<?= htmlspecialchars($userId) ?>" class="btn btn-secondary">
                                <?= UI_ICON::bi('arrow-left') ?> 返回编辑
                            </a>
                            <button type="submit" class="btn btn-danger" onclick="return confirmDelete();">
                                <?= UI_ICON::bi('trash') ?> 确认删除
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- 返回链接 -->
        <?php if (empty($success)): ?>
            <div class="mt-3 text-center">
                <a href="/manage/user/list" class="text-decoration-none text-muted">
                    <?= UI_ICON::bi('list') ?> 返回用户列表
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete() {
    return confirm('确定要删除此用户吗？\n\n此操作不可恢复！\n\n用户删除后将无法登录系统。');
}
</script>

<?php UI_STRUCTURE::footer(); ?>
