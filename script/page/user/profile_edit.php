<?php

// 检查用户是否已登录
$user = USER::getInstance();

if (!$user->isLoggedIn()) {
    $_SESSION['redirect_to'] = '/user/profile/edit';
    header("Location: /user/login");
    exit;
}

$userInfo = $user->getCurrentUserInfo(USER::ALL_ROWS);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updateData = [];

        $nickname = trim($_POST['nickname'] ?? '');
        if (empty($nickname)) {
            throw new InvalidArgumentException('昵称不能为空');
        }
        $updateData[USER::KEY_NICKNAME] = $nickname;

        $phone = trim($_POST['phone'] ?? '');
        $updateData[USER::KEY_PHONE] = $phone;

        // 备注信息
        $profile = trim($_POST['profile'] ?? '');
        if (!empty($profile)) {
            $updateData[USER::KEY_PROFILE] = [USER::KEY_PROFILE => $profile];
        }

        $result = $user->updateCurrentUser($updateData);

        if ($result) {
            $success = '个人资料更新成功！';
            // 刷新用户信息缓存
            $user->clearUserInfoCache(USER::SOCIAL_ROWS);
            $userInfo = $user->getCurrentUserInfo(USER::SOCIAL_ROWS);
        } else {
            throw new RuntimeException('更新失败，请重试');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

UI_STRUCTURE::header("编辑个人资料", 1, "个人资料");
?>
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">编辑个人资料</h4>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group mb-3">
                        <label for="username">用户名</label>
                        <input type="text" class="form-control" id="username"
                            value="<?= htmlspecialchars($userInfo[USER::KEY_USERNAME]) ?>" disabled>
                        <small class="form-text text-muted">用户名不可更改</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="nickname">昵称</label>
                        <input type="text" class="form-control" id="nickname" name="nickname"
                            value="<?= htmlspecialchars($userInfo[USER::KEY_NICKNAME] ?? $userInfo[USER::KEY_USERNAME]) ?>"
                            maxlength="64" required>
                        <small class="form-text text-muted">最多64个字符</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="phone">电话</label>
                        <input type="text" class="form-control" id="phone" name="phone"
                            value="<?= htmlspecialchars($userInfo[USER::KEY_PHONE] ?? '') ?>">
                        <small class="form-text text-muted">用于显示联系方式</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="profile">个人备注</label>
                        <textarea class="form-control" id="profile" name="profile"
                            rows="4"><?= htmlspecialchars($userInfo[USER::KEY_PROFILE][USER::KEY_PROFILE] ?? '') ?></textarea>
                        <small class="form-text text-muted">个人简介、备注等信息</small>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="/user/profile" class="btn btn-secondary">取消</a>
                        <button type="submit" class="btn btn-primary">保存更改</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php UI_STRUCTURE::import(); ?>
