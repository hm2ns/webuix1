<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        $email = $_POST['email'] ?? '';

        if (empty($username) || empty($email)) {
            throw new InvalidArgumentException('请输入用户名和安全邮箱');
        }

        $userCfg = USER::getUserInfoByUsername($username, rows: USER::ALL_ROWS);

        if (!$userCfg) {
            throw new InvalidArgumentException('用户不存在');
        }

        if (($userCfg[USER::KEY_ROLE] ?? "") != USER::DEFAULT_ROLE) {
            throw new InvalidArgumentException('仅学生(常规)账户可重置密码！');
        }

        $userPrc = $userCfg['profile'] ?? [];

        if (($userPrc['recv'] ?? 0) > (time() - 600)) {
            throw new Exception('请勿重复发送重置邮件,10分钟后再试！剩余时间：' .
                secondsToTime($userPrc['recv'] + 600 - time()));
        }

        if (($userCfg[USER::KEY_EMAIL] ?? "") != $email) {
            throw new InvalidArgumentException('邮箱与用户名不匹配');
        }

        //生成八位随机数验证码
        $vcode = substr(md5(uniqid()), 0, 8);
        EM::init();
        $emres = EM::send(
            $email,
            '账户恢复链接',
            EM::gen(
                "正在申请重置您的账户密码",
                '如果这不是您本人操作，请尽快联系管理员。<br>点击下方按钮重设密码',
                "user/f2nd?vcode=$vcode&id=" . $userCfg['id']
            )
        );
        if (!$emres) {
            throw new Exception('密码重置邮件发送失败');
        }
        $userPrc['recv'] = time();
        $userPrc['vcode'] = password_hash($vcode, PASSWORD_BCRYPT);
        if (
            User::updateUser(
                [
                    "id" => $userCfg[USER::KEY_ID],
                    "profile" => $userPrc
                ]
            )
        ) {
            UI_Notice::alert('账户恢复链接已发送，请登录邮箱查看', 'success');
        } else
            throw new Exception('数据库错误');
    } catch (Exception $e) {
        UI_Notice::alert($e->getMessage(), 'danger');
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
                        <h6 class="font-weight-light mb-4">请输入您的凭据来重置密码</h6>

                        <form class="pt-3" method="POST">
                            <div class="form-group">
                                <label for="usernameInput">用户名</label>
                                <div class="input-group">
                                    <div class="input-group-prepend bg-transparent">
                                        <span class="input-group-text bg-transparent border-right-0">
                                            <i class="bi bi-person text-primary"></i>
                                        </span>
                                    </div>
                                    <input type="text" class="form-control form-control-lg border-left-0"
                                        id="usernameInput" name="username" required
                                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                        placeholder="用户名" autofocus>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">安全邮箱*</label>
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
                                <small class="form-text text-muted"></small>
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
<?php UI_STRUCTURE::footer(); ?>