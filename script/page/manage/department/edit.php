<?php

// 从路由参数或GET参数获取部门ID
$deptId = Router::getParam('id') ?? $_GET['id'] ?? '';

if (empty($deptId)) {
    UI_NOTICE::alert('部门ID不能为空', BS_DANGER);
    header("Location: /manage/department/list");
    exit;
}

// 获取部门信息
$department = DEPARTMENT::getById($deptId, DEPARTMENT::ALL_ROWS);

if (!$department) {
    UI_NOTICE::alert('部门不存在', BS_DANGER);
    header("Location: /manage/department/list");
    exit;
}

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

        // 检查部门编码是否已存在（排除当前部门）
        if (!empty($code) && DEPARTMENT::codeExists($code, $deptId)) {
            throw new InvalidArgumentException('部门编码已存在');
        }

        // 检查是否将部门移动到自己的子部门下
        if (!empty($parentId) && $parentId !== $deptId) {
            $descendants = DEPARTMENT::getDescendants($deptId);
            foreach ($descendants as $descendant) {
                if ($descendant[DEPARTMENT::KEY_ID] === $parentId) {
                    throw new InvalidArgumentException('不能将部门移动到其子部门下');
                }
            }
        }

        // 准备更新数据
        $updateData = [
            DEPARTMENT::KEY_NAME => $name,
            DEPARTMENT::KEY_CODE => $code ?: null,
            DEPARTMENT::KEY_LEADER => $leaderId ?: null,
            DEPARTMENT::KEY_DESCRIPTION => $description ?: null,
            DEPARTMENT::KEY_SORT => $sort
        ];

        // 如果父部门发生变化，需要移动部门
        $currentParentId = $department[DEPARTMENT::KEY_PARENT_ID];
        if ($parentId !== $currentParentId) {
            // 如果设置为空，则移动到顶级
            if (empty($parentId)) {
                DEPARTMENT::move($deptId, null);
            } else {
                DEPARTMENT::move($deptId, $parentId);
            }
        }

        // 更新其他字段
        $result = DEPARTMENT::update($deptId, $updateData);

        if ($result) {
            $success = '部门更新成功！';
            UI_NOTICE::alert($success, BS_SUCCESS);

            // 重新获取部门信息
            $department = DEPARTMENT::getById($deptId, DEPARTMENT::ALL_ROWS);
        } else {
            throw new RuntimeException('部门更新失败，请重试');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        UI_NOTICE::alert($error, BS_DANGER);
    }
}

// 获取所有部门（用于父部门选择）
$allDepartments = DEPARTMENT::getAll(DEPARTMENT::BASIC_ROWS);

// 排除当前部门及其子部门作为父部门选项
$descendantIds = array_column(DEPARTMENT::getDescendants($deptId), DEPARTMENT::KEY_ID);
$availableParents = array_filter($allDepartments, function($dept) use ($deptId, $descendantIds) {
    return $dept[DEPARTMENT::KEY_ID] !== $deptId && !in_array($dept[DEPARTMENT::KEY_ID], $descendantIds);
});

// 获取所有用户（用于负责人选择）
$allUsers = USER::searchUsers("");

UI_STRUCTURE::header('编辑部门', 1, '部门管理');
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">编辑部门</h4>

                <!-- 部门基本信息 -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted">部门ID</label>
                        <div class="form-control-plaintext"><?= htmlspecialchars($department[DEPARTMENT::KEY_ID]) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">当前层级</label>
                        <div class="form-control-plaintext">Level <?= $department[DEPARTMENT::KEY_LEVEL] ?></div>
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
                            <label for="name" class="form-label">部门名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="<?= htmlspecialchars($_POST['name'] ?? $department[DEPARTMENT::KEY_NAME]) ?>"
                                maxlength="255" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">部门编码</label>
                            <input type="text" class="form-control" id="code" name="code"
                                value="<?= htmlspecialchars($_POST['code'] ?? $department[DEPARTMENT::KEY_CODE] ?? '') ?>"
                                maxlength="64">
                            <div class="form-text">部门编码，用于系统识别，建议使用英文或数字</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="parent_id" class="form-label">上级部门</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">无上级部门（顶级部门）</option>
                                <?php foreach ($availableParents as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept[DEPARTMENT::KEY_ID]) ?>"
                                        <?= ((($_POST['parent_id'] ?? $department[DEPARTMENT::KEY_PARENT_ID] ?? '') === $dept[DEPARTMENT::KEY_ID]) ? 'selected' : '') ?>>
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
                                        <?= ((($_POST['leader_id'] ?? $department[DEPARTMENT::KEY_LEADER] ?? '') === $user[USER::KEY_ID]) ? 'selected' : '') ?>>
                                        <?= htmlspecialchars($user[USER::KEY_NICKNAME] ?? $user[USER::KEY_USERNAME]) ?>
                                        (<?= htmlspecialchars($user[USER::KEY_USERNAME]) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">部门描述</label>
                        <textarea class="form-control" id="description" name="description"
                            rows="4"><?= htmlspecialchars($_POST['description'] ?? $department[DEPARTMENT::KEY_DESCRIPTION] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="sort" class="form-label">排序</label>
                        <input type="number" class="form-control" id="sort" name="sort"
                            value="<?= htmlspecialchars($_POST['sort'] ?? $department[DEPARTMENT::KEY_SORT] ?? 0) ?>" min="0" max="9999">
                        <div class="form-text">数字越小排序越靠前，默认为0</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">状态</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status"
                                    id="status_normal" value="<?= DEPARTMENT::STATUS_NORMAL ?>"
                                    <?= ($department[DEPARTMENT::KEY_STATUS] === DEPARTMENT::STATUS_NORMAL) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="status_normal">
                                    <span class="badge bg-success">正常</span>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status"
                                    id="status_disabled" value="<?= DEPARTMENT::STATUS_DISABLED ?>"
                                    <?= ($department[DEPARTMENT::KEY_STATUS] === DEPARTMENT::STATUS_DISABLED) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="status_disabled">
                                    <span class="badge bg-secondary">禁用</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/manage/department/list" class="btn btn-secondary">
                            <?= UI_ICON::bi('arrow-left') ?> 返回列表
                        </a>
                        <div class="btn-group" role="group">
                            <button type="submit" class="btn btn-primary">
                                <?= UI_ICON::bi('check-circle') ?> 保存更改
                            </button>
                            <button type="button" class="btn btn-info"
                                onclick="location.href='/manage/department/users/<?= htmlspecialchars($deptId) ?>'">
                                <?= UI_ICON::bi('people') ?> 管理部门用户
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 部门统计信息 -->
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">部门信息</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>创建时间：</strong><?= getDate_full(getDate_Auto($department[DEPARTMENT::KEY_CREATE_TIME] ?? '')) ?></p>
                        <p class="mb-1"><strong>更新时间：</strong><?= getDate_full(getDate_Auto($department[DEPARTMENT::KEY_UPDATE_TIME] ?? '')) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>部门路径：</strong><?= htmlspecialchars($department[DEPARTMENT::KEY_PATH] ?? '') ?></p>
                    </div>
                </div>

                <!-- 子部门列表 -->
                <hr>
                <h6 class="mb-3">子部门</h6>
                <?php
                $children = DEPARTMENT::getChildren($deptId);
                if (empty($children)) {
                    echo '<p class="text-muted">无子部门</p>';
                } else {
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-sm">';
                    echo '<thead><tr><th>ID</th><th>名称</th><th>编码</th><th>层级</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($children as $child) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($child[DEPARTMENT::KEY_ID]) . '</td>';
                        echo '<td><a href="/manage/department/edit/' . htmlspecialchars($child[DEPARTMENT::KEY_ID]) . '">' . htmlspecialchars($child[DEPARTMENT::KEY_NAME]) . '</a></td>';
                        echo '<td>' . htmlspecialchars($child[DEPARTMENT::KEY_CODE] ?? '-') . '</td>';
                        echo '<td>' . $child[DEPARTMENT::KEY_LEVEL] . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table></div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php UI_STRUCTURE::footer(); ?>
