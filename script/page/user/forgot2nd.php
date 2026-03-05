<?php
$targetUser = $_GET['id'] ?? 0;
if (!$targetUser) {
    jsjump('/');
    exit;
}
$vcode = $_GET['vcode'] ?? '';
$vcode = trim($vcode);
if (!$vcode) {
    jsjump('/');
    exit;
}

$userCfg = USER::getUserInfoById($targetUser);
if (!$userCfg) {
    UI_ERRORPAGES::show(404, '用户不存在');
    exit;
}
if (!isset($userCfg['profile']['vcode']) || $userCfg['profile']['vcode'] == '') {
    jsjump('/user/forgot');
    exit;
}
if (!password_verify($vcode, $userCfg['profile']['vcode'] ?? '')) {
    UI_NOTICE::alert('验证码错误', 'danger');
    UI_ERRORPAGES::show(403, '验证码错误');
    exit;
}

if ($userCfg['profile']['recv'] < time() - 600) {
    UI_NOTICE::alert('验证码已过期', 'danger');
    UI_ERRORPAGES::show(403, '验证码已过期');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        if (!$password || !$password2) {
            throw new Exception('请填写完整的密码信息');
        }
        if ($password != $password2) {
            throw new Exception('两次输入的密码不一致');
            exit;
        }
        unset($userCfg['profile']['vcode']);
        unset($userCfg['profile']['recv']);
        USER::updateUser([
            'id' => $targetUser,
            'profile' => $userCfg['profile'],
        ]);
        UI_NOTICE::alert('密码重置成功，', 'success');
    } catch (Exception $e) {
        UI_NOTICE::alert($e->getMessage(), 'danger');
    }
}

UI_STRUCTURE::header("重置密码", 3);
?>
<div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-stretch auth auth-img-bg">
            <div class="row flex-grow">
                <div class="col-lg-6 d-flex align-items-center justify-content-center">
                    <div class="auth-form-transparent text-left p-3">
                    <div class="brand-logo mb-4">
                            <img src="<?= GLOBAL_CONFIG::get('logo_long') ?>" alt="logo" class="img-fluid" style="max-height: 80px;">
                        </div>
                        <h4 class="mb-4">重置您的密码</h4>
                        <h6 class="font-weight-light mb-4">请设置新密码</h6>

                        <form class="pt-3" method="POST">
                            <div class="form-group">
                                <label for="passwordInput">新密码</label>
                                <div class="input-group">
                                    <div class="input-group-prepend bg-transparent">
                                        <span class="input-group-text bg-transparent border-right-0">
                                            <i class="bi bi-person text-primary"></i>
                                        </span>
                                    </div>
                                    <input type="password" class="form-control form-control-lg border-left-0"
                                        id="passwordInput" name="password" required
                                        value="<?= htmlspecialchars($_POST['password'] ?? '') ?>"
                                        placeholder="新密码" autofocus>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="password2Input">重复密码</label>
                                <div class="input-group">
                                    <div class="input-group-prepend bg-transparent">
                                        <span class="input-group-text bg-transparent border-right-0">
                                            <i class="bi bi-person text-primary"></i>
                                        </span>
                                    </div>
                                    <input type="password" class="form-control form-control-lg border-left-0"
                                        id="password2Input" name="password2" required
                                        value="<?= htmlspecialchars($_POST['password2'] ?? '') ?>"
                                        placeholder="重复新密码">
                                </div>
                            </div>

                            <div class="my-3">
                                <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn">
                                    确定
                                </button>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="/user/login" class="auth-link">返回登录</a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-6 login-half-bg d-flex flex-row">
                    <div class="container align-self-end">
                        <p class="text-white font-weight-medium text-center flex-grow align-self-end">
                            Copyright &copy; <?= date('Y') ?> All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php UI_STRUCTURE::import(); ?>