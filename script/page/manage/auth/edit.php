<?php

// 从路由参数或GET参数获取权限组ID
$authGroupId = Router::getParam('id') ?? $_GET['id'] ?? '';

if (empty($authGroupId)) {
    UI_NOTICE::alert('权限组ID不能为空', BS_DANGER);
    header("Location: /manage/auth/list");
    exit;
}

// 初始化RBAC
RBAC::init();

// 获取权限组信息
$authGroup = AUTHORITIES::getById($authGroupId, AUTHORITIES::ALL_ROWS);

if (!$authGroup) {
    UI_NOTICE::alert('权限组不存在', BS_DANGER);
    header("Location: /manage/auth/list");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $baseGroupId = trim($_POST['base_group_id'] ?? '');
        $sort = intval($_POST['sort'] ?? 0);
        $status = trim($_POST['status'] ?? AUTHORITIES::STATUS_NORMAL);

        // 验证必填字段
        if (empty($name)) {
            throw new InvalidArgumentException('权限组名称不能为空');
        }

        // 检查权限组编码是否已存在（排除当前权限组）
        if (!empty($code) && AUTHORITIES::codeExists($code, $authGroupId)) {
            throw new InvalidArgumentException('权限组编码已存在');
        }

        // 检查是否将权限组移动到自己的子权限组下（避免循环引用）
        if (!empty($baseGroupId) && $baseGroupId !== $authGroupId) {
            $currentBase = $baseGroupId;
            $visited = [$authGroupId];
            while (!empty($currentBase)) {
                if (in_array($currentBase, $visited)) {
                    throw new InvalidArgumentException('不能设置循环引用的基础权限组');
                }
                $visited[] = $currentBase;
                $tempGroup = AUTHORITIES::getById($currentBase);
                if (!$tempGroup) {
                    break;
                }
                $currentBase = $tempGroup[AUTHORITIES::KEY_BASE_GROUP] ?? '';
            }
        }

        // 获取权限设置
        $permissions = [];
        $allPermissions = RBAC::getAllPermissions();

        foreach ($allPermissions as $perm => $permName) {
            $permCode = preg_replace('/[^a-z0-9]/i', '', $perm);
            $permissions[$perm] = isset($_POST['perm_' . $permCode]) && $_POST['perm_' . $permCode] === '1';
        }

        // 准备更新数据
        $updateData = [
            AUTHORITIES::KEY_NAME => $name,
            AUTHORITIES::KEY_CODE => $code ?: null,
            AUTHORITIES::KEY_DESCRIPTION => $description ?: null,
            AUTHORITIES::KEY_BASE_GROUP => $baseGroupId ?: null,
            AUTHORITIES::KEY_PERMISSIONS => $permissions,
            AUTHORITIES::KEY_SORT => $sort,
            AUTHORITIES::KEY_STATUS => $status
        ];

        // 更新权限组
        $result = AUTHORITIES::update($authGroupId, $updateData);

        if ($result) {
            $success = '权限组更新成功！';
            UI_NOTICE::alert($success, BS_SUCCESS);

            // 重新获取权限组信息
            $authGroup = AUTHORITIES::getById($authGroupId, AUTHORITIES::ALL_ROWS);
        } else {
            throw new RuntimeException('权限组更新失败，请重试');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        UI_NOTICE::alert($error, BS_DANGER);
    }
}

// 获取所有权限组（用于基础权限组选择）
$allAuthGroups = AUTHORITIES::getAll(AUTHORITIES::BASIC_ROWS);

// 排除当前权限组及其子权限组作为基础权限组选项（避免循环引用）
$excludeIds = [$authGroupId];
$currentBase = $authGroup[AUTHORITIES::KEY_BASE_GROUP] ?? '';
while (!empty($currentBase)) {
    $tempGroup = AUTHORITIES::getById($currentBase);
    if (!$tempGroup) {
        break;
    }
    $excludeIds[] = $currentBase;
    $currentBase = $tempGroup[AUTHORITIES::KEY_BASE_GROUP] ?? '';
}

// 获取所有权限
$allPermissions = RBAC::getAllPermissions();

// 获取当前权限
$currentPermissions = $authGroup[AUTHORITIES::KEY_PERMISSIONS] ?? [];

UI_STRUCTURE::header('编辑权限组', 1, '权限组管理');
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">编辑权限组</h4>

                <!-- 权限组基本信息 -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted">权限组ID</label>
                        <div class="form-control-plaintext"><?= htmlspecialchars($authGroup[AUTHORITIES::KEY_ID]) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">创建时间</label>
                        <div class="form-control-plaintext"><?= getDate_full(getDate_Auto($authGroup[AUTHORITIES::KEY_CREATE_TIME] ?? '')) ?></div>
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">权限组名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="<?= htmlspecialchars($_POST['name'] ?? $authGroup[AUTHORITIES::KEY_NAME]) ?>"
                                maxlength="255" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">权限组编码</label>
                            <input type="text" class="form-control" id="code" name="code"
                                value="<?= htmlspecialchars($_POST['code'] ?? $authGroup[AUTHORITIES::KEY_CODE] ?? '') ?>"
                                maxlength="64">
                            <div class="form-text">权限组编码，用于系统识别，建议使用英文或数字</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">权限组描述</label>
                        <textarea class="form-control" id="description" name="description"
                            rows="3"><?= htmlspecialchars($_POST['description'] ?? $authGroup[AUTHORITIES::KEY_DESCRIPTION] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="base_group_id" class="form-label">基础权限组</label>
                        <select class="form-select" id="base_group_id" name="base_group_id">
                            <option value="">无基础权限组</option>
                            <?php foreach ($allAuthGroups as $group): ?>
                                <?php if (!in_array($group[AUTHORITIES::KEY_ID], $excludeIds)): ?>
                                    <option value="<?= htmlspecialchars($group[AUTHORITIES::KEY_ID]) ?>"
                                        <?= (($_POST['base_group_id'] ?? $authGroup[AUTHORITIES::KEY_BASE_GROUP] ?? '') === $group[AUTHORITIES::KEY_ID]) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($group[AUTHORITIES::KEY_NAME]) ?>
                                        (<?= htmlspecialchars($group[AUTHORITIES::KEY_CODE] ?? '无编码') ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">选择基础权限组后，当前权限组将继承基础权限组的所有权限</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">权限设置</label>
                        <div class="card">
                            <div class="card-body">
                                <?php foreach ($allPermissions as $permCode => $permName): ?>
                                    <?php $permInputCode = preg_replace('/[^a-z0-9]/i', '', $permCode); ?>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox"
                                            id="perm_<?= $permInputCode ?>"
                                            name="perm_<?= $permInputCode ?>"
                                            value="1"
                                            <?= (($currentPermissions[$permCode] ?? false) === true) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="perm_<?= $permInputCode ?>">
                                            <?= htmlspecialchars($permName) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sort" class="form-label">排序</label>
                            <input type="number" class="form-control" id="sort" name="sort"
                                value="<?= htmlspecialchars($_POST['sort'] ?? $authGroup[AUTHORITIES::KEY_SORT] ?? 0) ?>" min="0" max="9999">
                            <div class="form-text">数字越小排序越靠前，默认为0</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">状态</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="status"
                                        id="status_normal" value="<?= AUTHORITIES::STATUS_NORMAL ?>"
                                        <?= ($authGroup[AUTHORITIES::KEY_STATUS] === AUTHORITIES::STATUS_NORMAL) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="status_normal">
                                        <span class="badge bg-success">正常</span>
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="status"
                                        id="status_disabled" value="<?= AUTHORITIES::STATUS_DISABLED ?>"
                                        <?= ($authGroup[AUTHORITIES::KEY_STATUS] === AUTHORITIES::STATUS_DISABLED) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="status_disabled">
                                        <span class="badge bg-secondary">禁用</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/manage/auth/list" class="btn btn-secondary">
                            <?= UI_ICON::bi('arrow-left') ?> 返回列表
                        </a>
                        <div class="btn-group" role="group">
                            <button type="submit" class="btn btn-primary">
                                <?= UI_ICON::bi('check-circle') ?> 保存更改
                            </button>
                            <button type="button" class="btn btn-info"
                                onclick="location.href='/manage/auth/users/<?= htmlspecialchars($authGroupId) ?>'">
                                <?= UI_ICON::bi('people') ?> 管理用户
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 权限组统计信息 -->
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">权限组信息</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>更新时间：</strong><?= getDate_full(getDate_Auto($authGroup[AUTHORITIES::KEY_UPDATE_TIME] ?? '')) ?></p>
                    </div>
                </div>

                <!-- 继承的权限组链 -->
                <?php if (!empty($authGroup[AUTHORITIES::KEY_BASE_GROUP])): ?>
                    <hr>
                    <h6 class="mb-3">继承链</h6>
                    <p class="text-muted mb-2">当前权限组继承了以下权限组的权限：</p>
                    <ul class="list-group list-group-flush" id="inheritanceChain">
                        <!-- 动态填充 -->
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// 加载继承链
<?php if (!empty($authGroup[AUTHORITIES::KEY_BASE_GROUP])): ?>
document.addEventListener('DOMContentLoaded', function() {
    const chainContainer = document.getElementById('inheritanceChain');
    const currentBase = '<?= htmlspecialchars($authGroup[AUTHORITIES::KEY_BASE_GROUP]) ?>';

    // 这里可以扩展为AJAX调用后端API获取完整的继承链
    // 简化处理，只显示直接基础权限组
    chainContainer.innerHTML = '<li class="list-group-item">' +
        '<span class="badge bg-info">基础权限组</span> ' +
        '<?= htmlspecialchars(AUTHORITIES::getById($authGroup[AUTHORITIES::KEY_BASE_GROUP])[AUTHORITIES::KEY_NAME] ?? $authGroup[AUTHORITIES::KEY_BASE_GROUP]) ?> ' +
        '</li>';
});
<?php endif; ?>
</script>

<?php UI_STRUCTURE::footer(); ?>
