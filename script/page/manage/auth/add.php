<?php

// 初始化RBAC
RBAC::init();

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

        // 检查权限组编码是否已存在
        if (!empty($code) && AUTHORITIES::codeExists($code)) {
            throw new InvalidArgumentException('权限组编码已存在');
        }

        // 获取权限设置
        $permissions = [];
        $allPermissions = RBAC::getAllPermissions();

        foreach ($allPermissions as $perm => $permName) {
            $permCode = preg_replace('/[^a-z0-9]/i', '', $perm);
            $permissions[$perm] = isset($_POST['perm_' . $permCode]) && $_POST['perm_' . $permCode] === '1';
        }

        // 准备权限组数据
        $authGroupData = [
            AUTHORITIES::KEY_NAME => $name,
            AUTHORITIES::KEY_CODE => $code ?: null,
            AUTHORITIES::KEY_DESCRIPTION => $description ?: null,
            AUTHORITIES::KEY_BASE_GROUP => $baseGroupId ?: null,
            AUTHORITIES::KEY_PERMISSIONS => $permissions,
            AUTHORITIES::KEY_SORT => $sort,
            AUTHORITIES::KEY_STATUS => $status
        ];

        // 创建权限组
        $authGroupId = AUTHORITIES::create($authGroupData);

        if ($authGroupId) {
            $success = '权限组创建成功！';
            UI_NOTICE::alert($success, BS_SUCCESS);
        } else {
            throw new RuntimeException('权限组创建失败，请重试');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        UI_NOTICE::alert($error, BS_DANGER);
    }
}

// 获取所有权限组（用于基础权限组选择）
$allAuthGroups = AUTHORITIES::getAll(AUTHORITIES::BASIC_ROWS);

// 获取所有权限
$allPermissions = RBAC::getAllPermissions();

UI_STRUCTURE::header('添加权限组', 1, '权限组管理');
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">添加权限组</h4>

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
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                maxlength="255" required>
                            <div class="form-text">请输入权限组名称</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">权限组编码</label>
                            <input type="text" class="form-control" id="code" name="code"
                                value="<?= htmlspecialchars($_POST['code'] ?? '') ?>"
                                maxlength="64">
                            <div class="form-text">权限组编码，用于系统识别，建议使用英文或数字</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">权限组描述</label>
                        <textarea class="form-control" id="description" name="description"
                            rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        <div class="form-text">权限组的用途和功能描述</div>
                    </div>

                    <div class="mb-3">
                        <label for="base_group_id" class="form-label">基础权限组</label>
                        <select class="form-select" id="base_group_id" name="base_group_id">
                            <option value="">无基础权限组</option>
                            <?php foreach ($allAuthGroups as $group): ?>
                                <option value="<?= htmlspecialchars($group[AUTHORITIES::KEY_ID]) ?>"
                                    <?= ($_POST['base_group_id'] ?? '') === $group[AUTHORITIES::KEY_ID] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($group[AUTHORITIES::KEY_NAME]) ?>
                                    (<?= htmlspecialchars($group[AUTHORITIES::KEY_CODE] ?? '无编码') ?>)
                                </option>
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
                                            <?= (isset($_POST['perm_' . $permInputCode]) && $_POST['perm_' . $permInputCode] === '1') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="perm_<?= $permInputCode ?>">
                                            <?= htmlspecialchars($permName) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="form-text mt-2">勾选表示该权限组拥有对应权限</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sort" class="form-label">排序</label>
                            <input type="number" class="form-control" id="sort" name="sort"
                                value="<?= htmlspecialchars($_POST['sort'] ?? 0) ?>" min="0" max="9999">
                            <div class="form-text">数字越小排序越靠前，默认为0</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">状态</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="status"
                                        id="status_normal" value="<?= AUTHORITIES::STATUS_NORMAL ?>"
                                        <?= ($_POST['status'] ?? AUTHORITIES::STATUS_NORMAL) === AUTHORITIES::STATUS_NORMAL ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="status_normal">
                                        <span class="badge bg-success">正常</span>
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="status"
                                        id="status_disabled" value="<?= AUTHORITIES::STATUS_DISABLED ?>"
                                        <?= ($_POST['status'] ?? '') === AUTHORITIES::STATUS_DISABLED ? 'checked' : '' ?>>
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
                        <button type="submit" class="btn btn-primary">
                            <?= UI_ICON::bi('check-circle') ?> 创建权限组
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php UI_STRUCTURE::footer(); ?>
