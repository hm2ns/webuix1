<?php

// 从路由参数或GET参数获取部门ID
$deptId = Router::getParam('id') ?? $_GET['id'] ?? '';

if (empty($deptId)) {
    UI_NOTICE::alert('部门ID不能为空', BS_DANGER);
    header("Location: /manage/department/list");
    exit;
}

// 获取部门信息
$department = DEPARTMENT::getById($deptId, DEPARTMENT::BASIC_ROWS);

if (!$department) {
    UI_NOTICE::alert('部门不存在', BS_DANGER);
    header("Location: /manage/department/list");
    exit;
}

$error = '';
$success = '';
$message = '';

// 处理批量操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $userIds = $_POST['user_ids'] ?? [];

        if (empty($userIds)) {
            throw new InvalidArgumentException('请选择至少一个用户');
        }

        $updatedCount = 0;

        switch ($action) {
            case 'add':
                // 添加用户到部门
                foreach ($userIds as $userId) {
                    // 获取当前用户信息
                    $user = USER::getUserInfoById($userId, USER::BASIC_ROWS);
                    if ($user) {
                        // 检查用户是否已有部门
                        $currentDept = $user[USER::KEY_DEPARTMENT] ?? '';
                        if (empty($currentDept)) {
                            // 用户没有部门，可以直接添加
                            USER::updateUser([
                                USER::KEY_ID => $userId,
                                USER::KEY_DEPARTMENT => $department[DEPARTMENT::KEY_ID]
                            ]);
                            $updatedCount++;
                        } else {
                            // 用户已有部门，需要提示覆盖
                            $str = $user[USER::KEY_NICKNAME] ?? $user[USER::KEY_USERNAME];
                            throw new InvalidArgumentException("用户 '$str' 已有部门，无法直接添加。请使用'覆盖'模式。");
                        }
                    }
                }
                $success = "成功添加 {$updatedCount} 个用户到部门";
                break;

            case 'replace':
                // 覆盖用户部门（替换已有部门）
                foreach ($userIds as $userId) {
                    USER::updateUser([
                        USER::KEY_ID => $userId,
                        USER::KEY_DEPARTMENT => $department[DEPARTMENT::KEY_ID]
                    ]);
                    $updatedCount++;
                }
                $success = "成功将 {$updatedCount} 个用户的部门更新为 '{$department[DEPARTMENT::KEY_NAME]}'";
                break;

            case 'remove':
                // 移除用户部门
                foreach ($userIds as $userId) {
                    USER::updateUser([
                        USER::KEY_ID => $userId,
                        USER::KEY_DEPARTMENT => ''
                    ]);
                    $updatedCount++;
                }
                $success = "成功移除 {$updatedCount} 个用户的部门";
                break;

            default:
                throw new InvalidArgumentException('无效的操作类型');
        }

        if ($success) {
            UI_NOTICE::alert($success, BS_SUCCESS);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        UI_NOTICE::alert($error, BS_DANGER);
    }
}

// 获取所有用户（分页）
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 20;
$offset = ($page - 1) * $pageSize;

// 搜索功能
$search = $_GET['search'] ?? '';
$users = [];

if (!empty($search)) {
    $users = USER::searchUsers($search, ['username', 'nickname', 'email', 'phone'], $pageSize, $offset);
} else {
    // 使用空搜索获取所有用户
    $users = USER::searchUsers('', ['username', 'nickname', 'email', 'phone'], $pageSize, $offset);
}

// 获取用户总数（用于分页）
$totalUsers = count($users); // 简化处理，实际应该使用数据库查询获取总数
$totalPages = max(1, ceil($totalUsers / $pageSize));

UI_STRUCTURE::header('部门用户管理', 1, '部门管理');
?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">
                        <?= UI_ICON::bi('people') ?>
                        部门用户管理
                    </h4>
                    <a href="/manage/department/edit/<?= htmlspecialchars($deptId) ?>" class="btn btn-secondary">
                        <?= UI_ICON::bi('arrow-left') ?> 返回部门编辑
                    </a>
                </div>

                <!-- 部门信息 -->
                <div class="alert alert-info" role="alert">
                    <div class="d-flex align-items-center">
                        <?= UI_ICON::bi('info-circle', 'me-2 fs-4') ?>
                        <div>
                            <strong>当前部门：</strong>
                            <?= htmlspecialchars($department[DEPARTMENT::KEY_NAME]) ?>
                            <span class="badge bg-secondary ms-2"><?= htmlspecialchars($department[DEPARTMENT::KEY_CODE] ?? '无编码') ?></span>
                            <span class="text-muted small">（ID: <?= htmlspecialchars($department[DEPARTMENT::KEY_ID]) ?>）</span>
                        </div>
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

                <!-- 搜索框 -->
                <form method="GET" action="/manage/department/batchToUsers/<?= htmlspecialchars($deptId) ?>" class="mb-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search"
                            placeholder="搜索用户名、昵称、邮箱或电话..."
                            value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-outline-secondary">
                            <?= UI_ICON::bi('search') ?> 搜索
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="/manage/department/batchToUsers/<?= htmlspecialchars($deptId) ?>" class="btn btn-outline-secondary">
                                <?= UI_ICON::bi('x-circle') ?> 清空
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- 批量操作表单 -->
                <form method="POST" id="batchForm">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($deptId) ?>">
                    <input type="hidden" name="action" id="actionField" value="">

                    <!-- 用户列表 -->
                    <?php if (empty($users)): ?>
                        <div class="text-center py-5">
                            <h5 class="text-muted">没有找到用户</h5>
                            <?php if (!empty($search)): ?>
                                <p class="text-muted">尝试更换搜索关键词</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleSelectAll(this)">
                                        </th>
                                        <th>用户名</th>
                                        <th>昵称</th>
                                        <th>邮箱</th>
                                        <th>电话</th>
                                        <th>当前部门</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <?php
                                        $userDeptId = $user[USER::KEY_DEPARTMENT] ?? '';
                                        $userDeptName = '未分配';

                                        if (!empty($userDeptId)) {
                                            $userDept = DEPARTMENT::getById($userDeptId);
                                            if ($userDept) {
                                                $userDeptName = htmlspecialchars($userDept[DEPARTMENT::KEY_NAME]);
                                                if ($userDeptId === $deptId) {
                                                    $userDeptName .= ' <span class="badge bg-success">当前部门</span>';
                                                }
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input user-checkbox" name="user_ids[]"
                                                    value="<?= htmlspecialchars($user[USER::KEY_ID]) ?>"
                                                    onchange="updateSelectAll()">
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($user[USER::KEY_USERNAME]) ?>
                                            </td>
                                            <td><?= htmlspecialchars($user[USER::KEY_NICKNAME] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($user[USER::KEY_EMAIL] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($user[USER::KEY_PHONE] ?? '-') ?></td>
                                            <td><?= $userDeptName ?></td>
                                            <td>
                                                <?php if ($userDeptId === $deptId): ?>
                                                    <span class="text-muted">已在该部门</span>
                                                <?php elseif (!empty($userDeptId)): ?>
                                                    <button type="button" class="btn btn-sm btn-warning"
                                                        onclick="confirmReplace('<?= htmlspecialchars($user[USER::KEY_NICKNAME] ?? $user[USER::KEY_USERNAME]) ?>', '<?= htmlspecialchars($user[USER::KEY_ID]) ?>')">
                                                        <?= UI_ICON::bi('arrow-repeat') ?> 覆盖
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-success"
                                                        onclick="addToDepartment('<?= htmlspecialchars($user[USER::KEY_ID]) ?>')">
                                                        <?= UI_ICON::bi('plus') ?> 添加
                                                    </button>
                                                <?php endif; ?>

                                                <?php if (!empty($userDeptId)): ?>
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                        onclick="removeFromDepartment('<?= htmlspecialchars($user[USER::KEY_NICKNAME] ?? $user[USER::KEY_USERNAME]) ?>', '<?= htmlspecialchars($user[USER::KEY_ID]) ?>')">
                                                        <?= UI_ICON::bi('x-circle') ?> 移除
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- 分页 -->
                        <?php if ($totalPages > 1): ?>
                            <?php UI_STRUCTURE::pageCutNav($page, $totalPages, "/manage/department/batchToUsers/" . htmlspecialchars($deptId) . (!empty($search) ? "?search=" . urlencode($search) : "") . "?page="); ?>
                        <?php endif; ?>

                        <!-- 批量操作按钮 -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">批量操作</h5>
                                <p class="text-muted small">已选择 <strong id="selectedCount">0</strong> 个用户</p>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-success" onclick="batchAction('add')">
                                        <?= UI_ICON::bi('plus-circle') ?> 添加到部门
                                    </button>
                                    <button type="button" class="btn btn-warning" onclick="batchAction('replace')">
                                        <?= UI_ICON::bi('arrow-repeat') ?> 覆盖部门
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="batchAction('remove')">
                                        <?= UI_ICON::bi('x-circle') ?> 移除部门
                                    </button>
                                </div>
                                <p class="text-muted small mt-2">
                                    <strong>添加：</strong>将未分配部门的用户添加到当前部门<br>
                                    <strong>覆盖：</strong>将选中用户的部门替换为当前部门<br>
                                    <strong>移除：</strong>移除选中用户的部门
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateSelectedCount();
}

function updateSelectAll() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const selectAll = document.getElementById('selectAll');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    selectAll.checked = allChecked && checkboxes.length > 0;
    updateSelectedCount();
}

function updateSelectedCount() {
    const selected = document.querySelectorAll('.user-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = selected;
}

function getSelectedUserIds() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function batchAction(action) {
    const selectedIds = getSelectedUserIds();
    if (selectedIds.length === 0) {
        alert('请至少选择一个用户');
        return false;
    }

    let confirmMessage = '';
    switch(action) {
        case 'add':
            confirmMessage = '确定要将选中的用户添加到当前部门吗？\n\n注意：已有部门的用户将不会被添加。';
            break;
        case 'replace':
            confirmMessage = '确定要将选中用户的部门覆盖为当前部门吗？\n\n此操作将替换用户原有的部门。';
            break;
        case 'remove':
            confirmMessage = '确定要移除选中用户的部门吗？';
            break;
    }

    if (confirm(confirmMessage)) {
        document.getElementById('actionField').value = action;
        document.getElementById('batchForm').submit();
    }
}

function addToDepartment(userId) {
    const checkbox = document.querySelector(`.user-checkbox[value="${userId}"]`);
    if (checkbox) {
        checkbox.checked = true;
        updateSelectAll();
    }
    if (confirm('确定要添加此用户到当前部门吗？')) {
        batchAction('add');
    }
}

function confirmReplace(username, userId) {
    const checkbox = document.querySelector(`.user-checkbox[value="${userId}"]`);
    if (checkbox) {
        checkbox.checked = true;
        updateSelectAll();
    }
    if (confirm(`确定要将用户 "${username}" 的部门覆盖为当前部门吗？\n\n此操作将替换用户原有的部门。`)) {
        batchAction('replace');
    }
}

function removeFromDepartment(username, userId) {
    const checkbox = document.querySelector(`.user-checkbox[value="${userId}"]`);
    if (checkbox) {
        checkbox.checked = true;
        updateSelectAll();
    }
    if (confirm(`确定要移除用户 "${username}" 的部门吗？`)) {
        batchAction('remove');
    }
}

// 初始化选中数量
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>

<?php UI_STRUCTURE::footer(); ?>
