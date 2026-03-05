<?php

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $parentId = trim($_POST['parent_id'] ?? '');
        $leaderId = trim($_POST['leader_id'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sort = intval($_POST['sort'] ?? 0);

        // 验证必填字段
        if (empty($name)) {
            throw new InvalidArgumentException('部门名称不能为空');
        }

        // 检查部门编码是否已存在
        if (!empty($code) && DEPARTMENT::codeExists($code)) {
            throw new InvalidArgumentException('部门编码已存在');
        }

        // 准备部门数据
        $departmentData = [
            DEPARTMENT::KEY_NAME => $name,
            DEPARTMENT::KEY_CODE => $code ?: null,
            DEPARTMENT::KEY_PARENT_ID => $parentId ?: null,
            DEPARTMENT::KEY_LEADER => $leaderId ?: null,
            DEPARTMENT::KEY_DESCRIPTION => $description ?: null,
            DEPARTMENT::KEY_SORT => $sort
        ];

        // 创建部门
        $deptId = DEPARTMENT::create($departmentData);

        if ($deptId) {
            $success = '部门创建成功！';
            UI_NOTICE::alert($success, BS_SUCCESS);
        } else {
            throw new RuntimeException('部门创建失败，请重试');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        UI_NOTICE::alert($error, BS_DANGER);
    }
}

// 获取所有部门（用于父部门选择）
$allDepartments = DEPARTMENT::getAll(DEPARTMENT::BASIC_ROWS);

// 获取所有用户（用于负责人选择）
$allUsers = USER::searchUsers("");

UI_STRUCTURE::header('添加部门', 1, '部门管理');
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">添加部门</h4>

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
                            <label for="name" class="form-label">部门名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                maxlength="255" required>
                            <div class="form-text">请输入部门名称</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">部门编码</label>
                            <input type="text" class="form-control" id="code" name="code"
                                value="<?= htmlspecialchars($_POST['code'] ?? '') ?>"
                                maxlength="64">
                            <div class="form-text">部门编码，用于系统识别，建议使用英文或数字</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="parent_id" class="form-label">上级部门</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">无上级部门（顶级部门）</option>
                                <?php foreach ($allDepartments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept[DEPARTMENT::KEY_ID]) ?>"
                                        <?= (($_POST['parent_id'] ?? '') === $dept[DEPARTMENT::KEY_ID]) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept[DEPARTMENT::KEY_NAME]) ?>
                                        (<?= htmlspecialchars($dept[DEPARTMENT::KEY_CODE] ?? '无编码') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">选择上级部门，不选则为顶级部门</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="leader_id" class="form-label">部门负责人</label>
                            <select class="form-select" id="leader_id" name="leader_id">
                                <option value="">未设置</option>
                                <?php foreach ($allUsers as $user): ?>
                                    <option value="<?= htmlspecialchars($user[USER::KEY_ID]) ?>"
                                        <?= (($_POST['leader_id'] ?? '') === $user[USER::KEY_ID]) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user[USER::KEY_NICKNAME] ?? $user[USER::KEY_USERNAME]) ?>
                                        (<?= htmlspecialchars($user[USER::KEY_USERNAME]) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">选择部门负责人</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">部门描述</label>
                        <textarea class="form-control" id="description" name="description"
                            rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        <div class="form-text">部门职能、职责等描述信息</div>
                    </div>

                    <div class="mb-4">
                        <label for="sort" class="form-label">排序</label>
                        <input type="number" class="form-control" id="sort" name="sort"
                            value="<?= htmlspecialchars($_POST['sort'] ?? 0) ?>" min="0" max="9999">
                        <div class="form-text">数字越小排序越靠前，默认为0</div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/manage/department/list" class="btn btn-secondary">
                            <?= UI_ICON::bi('arrow-left') ?> 返回列表
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <?= UI_ICON::bi('check-circle') ?> 创建部门
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php UI_STRUCTURE::footer(); ?>
