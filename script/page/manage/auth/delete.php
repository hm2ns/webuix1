<?php

// 从路由参数或GET参数获取权限组ID
$authGroupId = Router::getParam('id') ?? $_GET['id'] ?? '';

if (empty($authGroupId)) {
    UI_NOTICE::alert('权限组ID不能为空', BS_DANGER);
    header("Location: /manage/auth/list");
    exit;
}

// 获取权限组信息
$authGroup = AUTHORITIES::getById($authGroupId, AUTHORITIES::BASIC_ROWS);

if (!$authGroup) {
    UI_NOTICE::alert('权限组不存在', BS_DANGER);
    header("Location: /manage/auth/list");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $confirm = $_POST['confirm'] ?? '';
        $force = isset($_POST['force']) && $_POST['force'] === '1';

        if ($confirm !== 'yes') {
            throw new InvalidArgumentException('请确认删除操作');
        }

        // 删除权限组
        $result = AUTHORITIES::delete($authGroupId, $force);

        if ($result) {
            $success = '权限组删除成功！';
            UI_NOTICE::alert($success, BS_SUCCESS);
            header("Location: /manage/auth/list");
            exit;
        } else {
            throw new RuntimeException('权限组删除失败，请重试');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        UI_NOTICE::alert($error, BS_DANGER);
    }
}

UI_STRUCTURE::header('删除权限组', 1, '权限组管理');
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">删除权限组</h4>

                <!-- 权限组信息 -->
                <div class="alert alert-info" role="alert">
                    <h6 class="alert-heading">权限组信息</h6>
                    <div class="mb-1">
                        <strong>权限组名称：</strong>
                        <?= htmlspecialchars($authGroup[AUTHORITIES::KEY_NAME]) ?>
                    </div>
                    <div class="mb-1">
                        <strong>权限组编码：</strong>
                        <?= htmlspecialchars($authGroup[AUTHORITIES::KEY_CODE] ?? '无') ?>
                    </div>
                    <div>
                        <strong>权限组ID：</strong>
                        <?= htmlspecialchars($authGroup[AUTHORITIES::KEY_ID]) ?>
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

                <!-- 删除警告 -->
                <div class="alert alert-warning" role="alert">
                    <h6 class="alert-heading">
                        <?= UI_ICON::bi('exclamation-triangle', 'me-2') ?>
                        删除警告
                    </h6>
                    <p class="mb-2">删除权限组后，以下操作将会发生：</p>
                    <ul class="mb-0">
                        <li>该权限组将被标记为"已删除"状态</li>
                        <li>已应用该权限组的用户<strong>不会</strong>失去权限（权限已保存到用户数据中）</li>
                        <li>如果该权限组被其他权限组作为基础权限组，将会影响继承关系</li>
                    </ul>
                </div>

                <form method="POST">
                    <input type="hidden" name="confirm" value="yes">

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="force" name="force" value="1">
                            <label class="form-check-label" for="force">
                                <strong>强制删除</strong>（忽略权限组使用检查）
                            </label>
                            <div class="form-text text-muted">
                                勾选此选项将强制删除权限组，即使有用户正在使用该权限组。
                                不勾选时，如果检测到用户使用该权限组，删除将会失败。
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/manage/auth/edit/<?= htmlspecialchars($authGroupId) ?>" class="btn btn-secondary">
                            <?= UI_ICON::bi('arrow-left') ?> 返回编辑
                        </a>
                        <button type="submit" class="btn btn-danger" onclick="return confirmDelete();">
                            <?= UI_ICON::bi('trash') ?> 确认删除
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    const force = document.getElementById('force').checked;
    const message = force
        ? '确定要强制删除权限组 "<?= htmlspecialchars($authGroup[AUTHORITIES::KEY_NAME]) ?>" 吗？\n\n此操作将强制删除权限组，不检查是否有用户正在使用。'
        : '确定要删除权限组 "<?= htmlspecialchars($authGroup[AUTHORITIES::KEY_NAME]) ?>" 吗？\n\n此操作将删除权限组。';
    return confirm(message);
}
</script>

<?php UI_STRUCTURE::footer(); ?>
