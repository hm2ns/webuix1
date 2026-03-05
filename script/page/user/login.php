<?php

const KEY_USERNAME = 'username';
const KEY_PASSWORD = 'password';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST[KEY_USERNAME] ?? '');
        $password = $_POST[KEY_PASSWORD] ?? '';

        if (empty($username) || empty($password)) {
            throw new InvalidArgumentException('请输入用户名和密码');
        }

        if(user::login($username, $password,parseUserAgent())){
            if($_SESSION[KEY_REDIRECT_TO]){
                header("Location: ".$_SESSION[KEY_REDIRECT_TO]);
            }else{
                header("Location: /user/profile");
            }
        }else{
            UI_NOTICE::alert('用户名或密码错误,登录失败', 'danger');
        }
    } catch (Exception $e) {
        UI_NOTICE::alert($e->getMessage(), 'danger');
    }
}

UI_STRUCTURE::header("登录", 3);
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
                        <h4 class="mb-4">欢迎回来！</h4>
                        <h6 class="font-weight-light mb-4">请输入您的凭据登录</h6>
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
                                        placeholder="用户名" autofocus value="<?= htmlspecialchars($_POST[KEY_USERNAME] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="passwordInput">密码</label>
                                <div class="input-group">
                                    <div class="input-group-prepend bg-transparent">
                                        <span class="input-group-text bg-transparent border-right-0">
                                            <i class="bi bi-lock text-primary"></i>
                                        </span>
                                    </div>
                                    <input type="password" class="form-control form-control-lg border-left-0"
                                        id="passwordInput" name="password" required
                                        placeholder="密码">
                                </div>
                            </div>
                            <div class="my-3">
                                <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn">
                                    登录
                                </button>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="/user/register" class="auth-link">创建新账号</a>
                                <a href="/user/forgot" class="auth-link">忘记密码？</a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-6 login-half-bg d-flex flex-row">
                    <div class="container align-self-end">
                        <div class="text-center mb-4">
                            <h3 class="text-white">多设备同步</h3>
                            <p class="text-white-50">您的账户可以在多个设备上安全登录，随时管理您的会话</p>
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