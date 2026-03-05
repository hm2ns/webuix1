<?php

// 检查用户是否已登录
$user = USER::getInstance();

if (!$user->isLoggedIn()) {
    $_SESSION['redirect_to'] = '/user/profile/devices';
    header("Location: /user/login");
    exit;
}

$error = '';
$success = '';

// 处理登出指定设备请求
if (isset($_GET['logout']) && !empty($_GET['logout'])) {
    try {
        $deviceToken = $_GET['logout'];
        $result = $user->logoutDevice($deviceToken);

        if ($result) {
            $success = '设备已成功登出';

            // 如果是当前设备，清除本地TOKEN并跳转到登录页
            if (isset($_GET['current']) && $_GET['current'] === '1') {
                $user->logout();
                header("Location: /user/login");
                exit;
            }
        } else {
            $error = '登出失败，请重试';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 处理登出所有其他设备请求
if (isset($_GET['logout_all'])) {
    try {
        $count = $user->logoutOtherDevices();

        if ($count > 0) {
            $success = "已成功登出 {$count} 个其他设备";
        } else {
            $success = '没有其他设备需要登出';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 获取用户设备
$devices = $user->getLoginDevices();

UI_STRUCTURE::header("设备管理", 1, "设备管理");
?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">登录设备管理</h4>
                <p class="text-muted mb-4">您可以在下面查看和管理所有登录设备。点击"登出"按钮可以远程注销该设备的会话。</p>

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

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>设备</th>
                                <th>操作系统</th>
                                <th>IP地址</th>
                                <th>登录时间</th>
                                <th>状态</th>
                                <th class="text-end">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($devices)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-device-hdd fs-1 mb-2"></i>
                                            <p class="mt-2">暂无登录设备记录</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($devices as $device): ?>
                                    <?php
                                    $deviceInfo = $device['device_info'] ?? [];
                                    $deviceName = $deviceInfo['device_type'] ?? '未知设备';
                                    $osInfo = $deviceInfo['os'] ?? '未知';
                                    ?>
                                    <tr class="<?= $device['is_current'] ? 'table-primary' : '' ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <?php if ($deviceName === '移动设备'): ?>
                                                        <i class="bi bi-phone text-primary fs-4"></i>
                                                    <?php elseif ($deviceName === '便携设备/平板'): ?>
                                                        <i class="bi bi-tablet text-success fs-4"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-display text-info fs-4"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($deviceName) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($osInfo) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars($device['ip']) ?></span>
                                        </td>
                                        <td>
                                            <div class="text-muted"><?= date('Y-m-d H:i:s', $device['login_time']) ?></div>
                                            <?php if (!$device['is_current']): ?>
                                                <div class="text-muted small">过期: <?= date('Y-m-d H:i:s', $device['expire']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($device['is_current']): ?>
                                                <span class="badge bg-success">当前设备</span>
                                            <?php elseif (time() > $device['expire']): ?>
                                                <span class="badge bg-secondary">已过期</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">活跃</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if (!$device['is_current']): ?>
                                                <a href="/user/profile/devices?logout=<?= urlencode($device['token']) ?>&current=0"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('确定要登出此设备吗？')">
                                                    <i class="bi bi-box-arrow-right me-1"></i>登出
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">当前设备</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 p-3 bg-light rounded">
                    <h6 class="mb-3">安全提示</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bi bi-shield-check text-success me-2"></i>定期检查您的登录设备，确保没有未知设备</li>
                        <li class="mb-2"><i class="bi bi-clock-history text-warning me-2"></i>不使用的设备应及时登出</li>
                        <li class="mb-2"><i class="bi bi-geo-alt text-info me-2"></i>注意检查登录地点是否正常</li>
                    </ul>
                </div>

                <div class="mt-4 text-end">
                    <button class="btn btn-danger" onclick="logoutAllDevices()">
                        <i class="bi bi-box-arrow-right me-1"></i>登出所有设备
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function logoutAllDevices() {
        if (confirm('确定要登出所有设备吗？包括当前设备。')) {
            window.location.href = '/user/profile/devices?logout_all=1';
        }
    }
</script>

<?php UI_STRUCTURE::import(); ?>
