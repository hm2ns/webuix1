<?php

// 检查用户是否已登录
$user = USER::getInstance();

if (!$user->isLoggedIn()) {
    $_SESSION['redirect_to'] = '/user/profile/safety';
    header("Location: /user/login");
    exit;
}

$userInfo = $user->getCurrentUserInfo(USER::SOCIAL_ROWS);
$error = '';
$success = '';

// 处理密码修改
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    try {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            throw new InvalidArgumentException('请填写所有密码字段');
        }

        if (strlen($newPassword) < 8) {
            throw new InvalidArgumentException('新密码长度至少8个字符');
        }

        if ($newPassword !== $confirmPassword) {
            throw new InvalidArgumentException('两次输入的新密码不一致');
        }

        // 修改密码
        $result = $user->changePassword($currentPassword, $newPassword);

        if (!$result) {
            throw new InvalidArgumentException('当前密码错误');
        }

        $success = '密码修改成功！下次请使用新密码重新登录';
        // 刷新用户信息缓存
        $user->clearUserInfoCache(USER::PASS_ROWS);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 处理邮箱更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email'])) {
    try {
        $newEmail = trim($_POST['new_email'] ?? '');
        $password = $_POST['password_for_email'] ?? '';

        if (empty($newEmail) || empty($password)) {
            throw new InvalidArgumentException('请填写邮箱和密码');
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('邮箱格式不正确');
        }

        // 验证密码
        $passInfo = $user->getUserInfoById($user->getCurrentUserId(), USER::PASS_ROWS);
        if (!$passInfo || !password_verify($password, $passInfo[USER::KEY_PASSWORD])) {
            throw new InvalidArgumentException('密码错误');
        }

        // 检查邮箱是否已存在
        if (USER::emailExists($newEmail, $user->getCurrentUserId())) {
            throw new InvalidArgumentException('该邮箱已被其他账户使用');
        }

        // 更新用户邮箱
        $result = $user->updateCurrentUser([
            USER::KEY_EMAIL => $newEmail
        ]);

        if (!$result) {
            throw new RuntimeException('邮箱更新失败');
        }

        $success = '邮箱更新成功！';
        // 刷新用户信息缓存
        $user->clearUserInfoCache(USER::SOCIAL_ROWS);
        $userInfo = $user->getCurrentUserInfo(USER::SOCIAL_ROWS);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

UI_STRUCTURE::header("账户安全", 1, "账户安全");
?>
<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">账户安全设置</h4>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-key me-2"></i>修改密码</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">当前密码</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">新密码</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                        <small class="form-text text-muted">密码至少8个字符，建议包含大小写字母、数字和特殊字符</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">确认新密码</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-danger w-100">
                                        <i class="bi bi-lock me-2"></i>修改密码
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-envelope me-2"></i>修改安全邮箱</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="current_email" class="form-label">当前邮箱</label>
                                        <input type="email" class="form-control" id="current_email" value="<?= htmlspecialchars($userInfo[USER::KEY_EMAIL] ?? '未设置') ?>" disabled>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_email" class="form-label">新邮箱</label>
                                        <input type="email" class="form-control" id="new_email" name="new_email" required>
                                        <small class="form-text text-muted">用于接收账户通知和找回密码
                                        </small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password_for_email" class="form-label">验证密码</label>
                                        <input type="password" class="form-control" id="password_for_email" name="password_for_email" required>
                                        <small class="form-text text-muted">请输入当前密码以验证身份</small>
                                    </div>
                                    <button type="submit" name="update_email" class="btn btn-warning w-100">
                                        <i class="bi bi-envelope-plus me-2"></i>更新邮箱
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>登录安全</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="mb-4">
                                        <h6 class="mb-3">登录设备管理</h6>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>管理所有登录设备</span>
                                            <span class="badge bg-primary"><?= count($user->getLoginDevices()) ?> 个设备</span>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary w-100" onclick="location.href='/user/profile/devices'">
                                            <i class="bi bi-device-hdd me-1"></i>管理登录设备
                                        </button>
                                        <small class="text-muted">查看和管理所有登录会话</small>
                                    </div>

                                    <div>
                                        <h6 class="mb-3">登录活动</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>时间</th>
                                                        <th>设备</th>
                                                        <th>IP</th>
                                                        <th>状态</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $devices = $user->getLoginDevices();
                                                    $recentDevices = array_slice($devices, 0, 5);
                                                    foreach ($recentDevices as $device):
                                                        $deviceInfo = $device['device_info'] ?? [];
                                                        $deviceName = $deviceInfo['device_type'] ?? '未知设备';
                                                        $osInfo = $deviceInfo['os'] ?? '';
                                                    ?>
                                                        <tr>
                                                            <td><?= date('Y-m-d H:i', $device['login_time']) ?></td>
                                                            <td>
                                                                <i class="bi bi-<?= $deviceName === '移动设备' ? 'phone' : ($deviceName === '便携设备/平板' ? 'tablet' : 'display') ?> me-1"></i>
                                                                <?= htmlspecialchars($deviceName) ?>
                                                                <?php if ($osInfo): ?>
                                                                    <div class="text-muted small"><?= htmlspecialchars($osInfo) ?></div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?= htmlspecialchars($device['ip']) ?></td>
                                                            <td>
                                                                <?php if ($device['is_current']): ?>
                                                                    <span class="badge bg-success">当前</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-info">活跃</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <button class="btn btn-sm btn-outline-secondary w-100" onclick="location.href='/user/profile/devices'">
                                            <i class="bi bi-list-ul me-1"></i>查看更多
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>账户风险</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning">
                                        <h6 class="alert-heading"><i class="bi bi-shield-exclamation me-2"></i>安全建议</h6>
                                        <ul class="mb-0">
                                            <li>定期修改密码，避免使用简单密码</li>
                                            <li>不要在公共设备上保存登录状态</li>
                                            <li>定期检查登录设备，移除不使用的设备</li>
                                            <li>使用复杂密码以增强账户安全</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php UI_STRUCTURE::import(); ?>