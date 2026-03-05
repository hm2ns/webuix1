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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nickname = trim($_POST['nickname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $departmentId = trim($_POST['department_id'] ?? '');
        $role = trim($_POST['role'] ?? USER::DEFAULT_ROLE);
        $status = trim($_POST['status'] ?? USER::STATUS_NORMAL);
        $profile = trim($_POST['profile'] ?? '');

        // 验证必填字段
        if (empty($nickname)) {
            throw new InvalidArgumentException('昵称不能为空');
        }

        // 检查邮箱是否已被其他用户使用
        if (!empty($email) && USER::emailExists($email, $userId)) {
            throw new InvalidArgumentException('邮箱已被其他用户使用');
        }

        // 检查手机号是否已被其他用户使用
        if (!empty($phone) && USER::phoneExists($phone, $userId)) {
            throw new InvalidArgumentException('手机号已被其他用户使用');
        }

        // 验证邮箱格式
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('邮箱格式不正确');
        }

        // 准备更新数据
        $user[USER::KEY_PROFILE][USER::KEY_PROFILE] = $profile; // 更新个人简介到用户信息中
        $updateData = [
            USER::KEY_ID => $userId,
            USER::KEY_NICKNAME => $nickname,
            USER::KEY_EMAIL => $email ?: null,
            USER::KEY_PHONE => $phone ?: null,
            USER::KEY_DEPARTMENT => $departmentId ?: null,
            USER::KEY_ROLE => $role,
            USER::KEY_STATUS => $status,
            USER::KEY_PROFILE => $user[USER::KEY_PROFILE],
        ];

        // 更新用户
        $result = USER::updateUser($updateData);

        if ($result) {
            $success = '用户更新成功！';
            UI_NOTICE::alert($success, BS_SUCCESS);

            // 重新获取用户信息
            $user = USER::getUserInfoById($userId, USER::ALL_ROWS);
        } else {
            throw new RuntimeException('用户更新失败，请重试');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        UI_NOTICE::alert($error, BS_DANGER);
    }
}

// 获取所有部门和角色
$allDepartments = DEPARTMENT::getAll(DEPARTMENT::BASIC_ROWS);
$allRoles = USER::ROLES;

// 获取用户当前的部门信息
$currentDeptName = '未分配';
if (!empty($user[USER::KEY_DEPARTMENT])) {
    $currentDept = DEPARTMENT::getById($user[USER::KEY_DEPARTMENT]);
    if ($currentDept) {
        $currentDeptName = htmlspecialchars($currentDept[DEPARTMENT::KEY_NAME]);
    }
}

UI_STRUCTURE::header('编辑用户', 1, '用户管理');
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">编辑用户</h4>

                <!-- 用户基本信息 -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted">用户ID</label>
                        <div class="form-control-plaintext"><?= htmlspecialchars($user[USER::KEY_ID]) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">用户名</label>
                        <div class="form-control-plaintext"><?= htmlspecialchars($user[USER::KEY_USERNAME]) ?></div>
                    </div>
                </div>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="nickname" class="form-label">昵称 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nickname" name="nickname"
                            value="<?= htmlspecialchars($_POST['nickname'] ?? $user[USER::KEY_NICKNAME]) ?>"
                            maxlength="64" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">邮箱</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?= htmlspecialchars($_POST['email'] ?? $user[USER::KEY_EMAIL] ?? '') ?>"
                                maxlength="255">
                            <div class="form-text">用于找回密码等操作</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">手机号</label>
                            <input type="text" class="form-control" id="phone" name="phone"
                                value="<?= htmlspecialchars($_POST['phone'] ?? $user[USER::KEY_PHONE] ?? '') ?>"
                                maxlength="32">
                            <div class="form-text">用于手机号登录等操作</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department_id" class="form-label">部门</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">未分配部门</option>
                                <?php foreach ($allDepartments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept[DEPARTMENT::KEY_ID]) ?>"
                                        <?= ((($_POST['department_id'] ?? $user[USER::KEY_DEPARTMENT] ?? '') === $dept[DEPARTMENT::KEY_ID]) ? 'selected' : '') ?>>
                                        <?= htmlspecialchars($dept[DEPARTMENT::KEY_NAME]) ?>
                                        (<?= htmlspecialchars($dept[DEPARTMENT::KEY_CODE] ?? '无编码') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">角色</label>
                            <select class="form-select" id="role" name="role" required>
                                <?php foreach ($allRoles as $roleKey => $roleName): ?>
                                    <option value="<?= $roleKey ?>"
                                        <?= ((($_POST['role'] ?? $user[USER::KEY_ROLE] ?? '') === $roleKey) ? 'selected' : '') ?>>
                                        <?= htmlspecialchars($roleName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="profile" class="form-label">个人简介</label>
                        <textarea class="form-control" id="profile" name="profile"
                            rows="4"><?= htmlspecialchars($_POST['profile'] ?? $user[USER::KEY_PROFILE][USER::KEY_PROFILE] ?? '') ?></textarea>
                        <div class="form-text">个人简介、备注等信息</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">状态</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status"
                                    id="status_normal" value="<?= USER::STATUS_NORMAL ?>"
                                    <?= ($user[USER::KEY_STATUS] === USER::STATUS_NORMAL) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="status_normal">
                                    <span class="badge bg-success">正常</span>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status"
                                    id="status_disabled" value="<?= USER::STATUS_DISABLED ?>"
                                    <?= ($user[USER::KEY_STATUS] === USER::STATUS_DISABLED) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="status_disabled">
                                    <span class="badge bg-warning">禁用</span>
                                </label>
                            </div>
                        </div>
                        <div class="form-text">禁用的用户无法登录系统</div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/manage/user/list" class="btn btn-secondary">
                            <?= UI_ICON::bi('arrow-left') ?> 返回列表
                        </a>
                        <div class="btn-group" role="group">
                            <button type="submit" class="btn btn-primary">
                                <?= UI_ICON::bi('check-circle') ?> 保存更改
                            </button>
                            <button type="button" class="btn btn-warning" onclick="showResetPasswordModal()">
                                <?= UI_ICON::bi('key') ?> 重置密码
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 用户信息卡片 -->
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">用户信息</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>创建时间：</strong><?= getDate_full(getDate_Auto($user[USER::KEY_CREATE_TIME] ?? '')) ?></p>
                        <p class="mb-1"><strong>更新时间：</strong><?= getDate_full(getDate_Auto($user[USER::KEY_UPDATE_TIME] ?? '')) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>当前部门：</strong><?= $currentDeptName ?></p>
                        <p class="mb-1"><strong>当前角色：</strong><?= USER::getRoleName($user[USER::KEY_ROLE]) ?></p>
                    </div>
                </div>

                <!-- 权限信息 -->
                <hr>
                <h6 class="mb-3">权限信息</h6>
                <?php
                $authority = $user[USER::KEY_AUTHORITY] ?? [];
                if (empty($authority) || !is_array($authority)) {
                    echo '<p class="text-muted">无特殊权限</p>';
                } else {
                    echo '<div class="row">';
                    foreach ($authority as $key => $value) {
                        echo '<div class="col-md-4 mb-2">';
                        echo '<span class="badge bg-info">' . htmlspecialchars($key) . ': ' . ($value ? '是' : '否') . '</span>';
                        echo '</div>';
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <!-- 重置密码模态框 -->
        <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">重置用户密码</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="resetPasswordForm">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">新密码 <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="new_password" name="new_password"
                                    minlength="6" required>
                                <div class="form-text">密码长度至少为6位</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_new_password" class="form-label">确认新密码 <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password"
                                    minlength="6" required>
                            </div>
                            <div class="alert alert-warning" role="alert">
                                <strong>注意：</strong>重置密码后，用户下次登录需要使用新密码。
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-warning" onclick="resetPassword()">
                            <?= UI_ICON::bi('key') ?> 确认重置
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showResetPasswordModal() {
    var modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
}

function resetPassword() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_new_password').value;

    if (newPassword.length < 6) {
        alert('密码长度至少为6位');
        return;
    }

    if (newPassword !== confirmPassword) {
        alert('两次输入的密码不一致');
        return;
    }

    // 使用AJAX提交重置密码请求
    const formData = new FormData();
    formData.append('action', 'reset_password');
    formData.append('user_id', '<?= htmlspecialchars($userId) ?>');
    formData.append('new_password', newPassword);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert('密码重置成功！');
        bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal')).hide();
        document.getElementById('resetPasswordForm').reset();
    })
    .catch(error => {
        alert('密码重置失败，请重试');
    });
}
</script>

<?php UI_STRUCTURE::footer(); ?>
