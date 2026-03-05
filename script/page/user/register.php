<?php
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');         //用户名
        $xh = trim($_POST['xh'] ?? '');                     //学号
        $password = $_POST['password'] ?? '';               //密码
        $confirmPassword = $_POST['confirm_password'] ?? ''; //确认密码
        $email = trim($_POST['email'] ?? '');               //邮箱
        $nickname = trim($_POST['nickname'] ?? '');         //姓名

        // 验证输入
        if (
            empty($username) ||
            empty($password) ||
            empty($confirmPassword) ||
            empty($email) ||
            empty($nickname) ||
            empty($xh)
        ) {
            throw new InvalidArgumentException('请填写所有必填字段');
        }

        //校验用户名格式
        if (!preg_match('/^[a-zA-Z0-9_]{4,12}$/', $username)) {
            throw new InvalidArgumentException('用户名格式不合法');
        }

        if (USER::getUserInfoByUsername($username, force: true, rows: "id")) {
            throw new InvalidArgumentException('用户名已存在');
        }

        //学号仅允许数字,但前两位可以是GJ
        if (Stu::validateXh($xh) === false) {
            throw new InvalidArgumentException('学号格式错误');
        }

        $stuInfo = stu::getByXh($xh);
        if (!$stuInfo || !$stuInfo['name'] || $stuInfo['name'] != $nickname) {
            throw new InvalidArgumentException('学号与姓名不匹配');
        }

        if (USER::getUserInfoByEmail($email, rows: "id",force: true)) {
            throw new InvalidArgumentException('邮箱已存在');
        }

        if (USER::getUserInfoByStuid($stuInfo['id'], force: true, rows: "id")) {
            throw new InvalidArgumentException('该学生信息已绑定账户');
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException('密码长度至少8个字符');
        }

        if ($password !== $confirmPassword) {
            throw new InvalidArgumentException('两次输入的密码不一致');
        }

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('邮箱为空或格式不正确');
        }

        // 创建用户

        $profileData = [
            'nickname' => $username,
            'email' => $email,
            "username" => $username,
            "password" => password_hash($password, USER::PASSWORD_CODER),
            "department" => "student",
            "phone" => $email,
            USER::KEY_BINDING_STUID => $stuInfo['id'],
            USER::KEY_BINDING_GRADE => $stuInfo['grade'],
            USER::KEY_BINDING_CLASS => $stuInfo['class'],
            USER::KEY_STATUS => USER::STATUS_NORMAL,
        ];

        if (USER::createUser($profileData)) {
            EM::init();
            EM::send(
                $email,
                "$username-账号注册成功",
                EM::gen($nickname . "同学您好，", "恭喜您，您已成功注册。<br>
            您的登录账户名是：$username 。<br>
            请妥善保管您的登录信息。", "user/login")
            );
            $success = '注册成功！请登录您的账户.您的登录账户名是：' . $username . ' 。';
            unset($_POST);
        } else {
            throw new RuntimeException('注册失败，请重试');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

UI_STRUCTURE::header("注册", 3);
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
                        <h4 class="mb-4">创建新账户</h4>
                        <h6 class="font-weight-light mb-4">加入我们，开启精彩旅程！</h6>

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

                        <form class="pt-3" method="POST">
                            <div class="form-group">
                                <label for="username">用户名*</label>
                                <div class="input-group">
                                    <div class="input-group-prepend bg-transparent">
                                        <span class="input-group-text bg-transparent border-right-0">
                                            <i class="bi bi-person text-primary"></i>
                                        </span>
                                    </div>
                                    <input type="text" class="form-control form-control-lg border-left-0"
                                        id="username" name="username" required
                                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                        placeholder="自定义用户名" autofocus>
                                </div>
                                <small class="form-text text-muted">
                                    登录时使用的用户名，可包含字母、数字、下划线，长度4~12位
                                    <span class="text-danger text-bold">
                                        不可更改
                                    </span>
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="xh">学号*</label>
                                <div class="input-group">
                                    <div class="input-group-prepend bg-transparent">
                                        <span class="input-group-text bg-transparent border-right-0">
                                            <i class="bi bi-person text-primary"></i>
                                        </span>
                                    </div>
                                    <input type="text" class="form-control form-control-lg border-left-0"
                                        id="xh" name="xh" required
                                        value="<?= htmlspecialchars($_POST['xh'] ?? '') ?>"
                                        placeholder="八位制学号" autofocus>
                                </div>
                                <small class="form-text text-muted">8位学号组成为[年级][班内学号]，如20260101</small>
                            </div>

                            <div class="form-group">
                                <label for="nickname">姓名*</label>
                                <div class="input-group">
                                    <div class="input-group-prepend bg-transparent">
                                        <span class="input-group-text bg-transparent border-right-0">
                                            <i class="bi bi-chat-left-text text-primary"></i>
                                        </span>
                                    </div>
                                    <input type="text" class="form-control form-control-lg border-left-0"
                                        id="nickname" name="nickname"
                                        value="<?= htmlspecialchars($_POST['nickname'] ?? '') ?>"
                                        placeholder="姓名">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">邮箱*</label>
                                <div class="input-group">
                                    <div class="input-group-prepend bg-transparent">
                                        <span class="input-group-text bg-transparent border-right-0">
                                            <i class="bi bi-envelope text-primary"></i>
                                        </span>
                                    </div>
                                    <input type="email" class="form-control form-control-lg border-left-0"
                                        id="email" name="email"
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                        placeholder="电子邮箱地址" required>
                                </div>
                                <small class="form-text text-muted">
                                    <span class="text-danger text-bold">
                                        务必正确填写！
                                    </span>
                                    <span>用于找回密码和接收通知</span>
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="password">密码</label>
                                <div class="input-group">
                                    <div class="input-group-prepend bg-transparent">
                                        <span class="input-group-text bg-transparent border-right-0">
                                            <i class="bi bi-lock text-primary"></i>
                                        </span>
                                    </div>
                                    <input type="password" class="form-control form-control-lg border-left-0"
                                        id="password" name="password" required
                                        placeholder="密码（至少8个字符）">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">确认密码</label>
                                <div class="input-group">
                                    <div class="input-group-prepend bg-transparent">
                                        <span class="input-group-text bg-transparent border-right-0">
                                            <i class="bi bi-lock-fill text-primary"></i>
                                        </span>
                                    </div>
                                    <input type="password" class="form-control form-control-lg border-left-0"
                                        id="confirm_password" name="confirm_password" required
                                        placeholder="确认密码">
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn">
                                    <i class="bi bi-person-plus me-2"></i>注册账户
                                </button>
                            </div>

                            <div class="text-center mt-4 font-weight-light">
                                已有账号？ <a href="/user/login" class="text-primary font-weight-bold">立即登录</a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-6 register-half-bg d-flex flex-row">
                    <div class="container align-self-end">
                        <div class="text-center mb-4">
                            <h3 class="text-white">多重安全保障</h3>
                            <p class="text-white-50">我们使用最先进的加密技术保护您的账户安全</p>
                        </div>
                        <div class="row mb-4">
                            <div class="col-4 text-center">
                                <i class="bi bi-shield-check fs-1 text-white"></i>
                                <p class="text-white mt-2">数据加密</p>
                            </div>
                            <div class="col-4 text-center">
                                <i class="bi bi-device-hdd fs-1 text-white"></i>
                                <p class="text-white mt-2">设备管理</p>
                            </div>
                            <div class="col-4 text-center">
                                <i class="bi bi-people fs-1 text-white"></i>
                                <p class="text-white mt-2">隐私保护</p>
                            </div>
                        </div>
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
<style>
    label {
        display: none;
    }
</style>