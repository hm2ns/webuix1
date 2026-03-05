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

// 获取权限组的完整权限（包含继承的基础权限组）
$authGroupPermissions = AUTHORITIES::getFullPermissions($authGroupId);

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
            case 'replace':
                // 替换用户权限（直接覆盖）
                foreach ($userIds as $userId) {
                    USER::updateUser([
                        USER::KEY_ID => $userId,
                        USER::KEY_AUTHORITY => $authGroupPermissions
                    ]);
                    $updatedCount++;
                }
                $success = "成功将 {$updatedCount} 个用户的权限替换为 '{$authGroup[AUTHORITIES::KEY_NAME]}'";
                break;

            case 'merge':
                // 合并用户权限（保留原有权限并添加新权限）
                foreach ($userIds as $userId) {
                    $user = USER::getUserInfoById($userId, USER::ALL_ROWS);
                    if ($user) {
                        $currentPermissions = $user[USER::KEY_AUTHORITY] ?? [];
                        $mergedPermissions = array_merge($currentPermissions, $authGroupPermissions);
                        USER::updateUser([
                            USER::KEY_ID => $userId,
                            USER::KEY_AUTHORITY => $mergedPermissions
                        ]);
                        $updatedCount++;
                    }
                }
                $success = "成功将权限组权限合并到 {$updatedCount} 个用户";
                break;

            case 'remove':
                // 移除权限组对应的权限
                foreach ($userIds as $userId) {
                    $user = USER::getUserInfoById($userId, USER::ALL_ROWS);
                    if ($user) {
                        $currentPermissions = $user[USER::KEY_AUTHORITY] ?? [];
                        // 移除权限组中的权限
                        foreach ($authGroupPermissions as $perm => $value) {
                            unset($currentPermissions[$perm]);
                        }
                        USER::updateUser([
                            USER::KEY_ID => $userId,
                            USER::KEY_AUTHORITY => $currentPermissions
                        ]);
                        $updatedCount++;
                    }
                }
                $success = "成功从 {$updatedCount} 个用户移除权限组权限";
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

UI_STRUCTURE::header('权限组用户管理', 1, '权限组管理');
?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">
                        <?= UI_ICON::bi('people') ?>
                        权限组用户管理
                    </h4>
                    <a href="/manage/auth/edit/<?= htmlspecialchars($authGroupId) ?>" class="btn btn-secondary">
                        <?= UI_ICON::bi('arrow-left') ?> 返回权限组编辑
                    </a>
                </div>

                <!-- 权限组信息 -->
                <div class="alert alert-info" role="alert">
                    <div class="d-flex align-items-center">
                        <?= UI_ICON::bi('info-circle', 'me-2 fs-4') ?>
                        <div>
                            <strong>当前权限组：</strong>
                            <?= htmlspecialchars($authGroup[AUTHORITIES::KEY_NAME]) ?>
                            <span class="badge bg-secondary ms-2"><?= htmlspecialchars($authGroup[AUTHORITIES::KEY_CODE] ?? '无编码') ?></span>
                            <span class="text-muted small">（ID: <?= htmlspecialchars($authGroup[AUTHORITIES::KEY_ID]) ?>）</span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <strong>包含权限：</strong>
                        <?php if (!empty($authGroupPermissions)): ?>
                            <?php foreach ($authGroupPermissions as $perm => $value): ?>
                                <?php if ($value === true): ?>
                                    <span class="badge bg-success me-1"><?= htmlspecialchars(RBAC::getPermissionName($perm)) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">无权限</span>
                        <?php endif; ?>
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
                <form method="GET" action="/manage/auth/batchToUsers/<?= htmlspecialchars($authGroupId) ?>" class="mb-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search"
                            placeholder="搜索用户名、昵称、邮箱或电话..."
                            value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-outline-secondary">
                            <?= UI_ICON::bi('search') ?> 搜索
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="/manage/auth/batchToUsers/<?= htmlspecialchars($authGroupId) ?>" class="btn btn-outline-secondary">
                                <?= UI_ICON::bi('x-circle') ?> 清空
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- 批量操作表单 -->
                <form method="POST" id="batchForm">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($authGroupId) ?>">
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
                                        <th>角色</th>
                                        <th>邮箱</th>
                                        <th>电话</th>
                                        <th>当前权限</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <?php
                                        $userPermissions = $user[USER::KEY_AUTHORITY] ?? [];
                                        $hasAuthGroupPerms = false;

                                        // 检查用户是否拥有权限组的所有权限
                                        $hasAllPerms = true;
                                        $missingPerms = [];
                                        foreach ($authGroupPermissions as $perm => $value) {
                                            if ($value === true && ($userPermissions[$perm] ?? false) !== true) {
                                                $hasAllPerms = false;
                                                $missingPerms[] = $perm;
                                            }
                                        }

                                        // 权限状态
                                        $permStatus = '';
                                        if (empty($userPermissions)) {
                                            $permStatus = '<span class="badge bg-secondary">无权限</span>';
                                        } elseif ($hasAllPerms) {
                                            $permStatus = '<span class="badge bg-success">包含全部权限</span>';
                                        } else {
                                            $permStatus = '<span class="badge bg-warning">部分权限</span>';
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
                                            <td>
                                                <span class="badge bg-info"><?= htmlspecialchars($user[USER::KEY_ROLE] ?? 'STUDENT') ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($user[USER::KEY_EMAIL] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($user[USER::KEY_PHONE] ?? '-') ?></td>
                                            <td><?= $permStatus ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary"
                                                    onclick="applyToUser('<?= htmlspecialchars($user[USER::KEY_NICKNAME] ?? $user[USER::KEY_USERNAME]) ?>', '<?= htmlspecialchars($user[USER::KEY_ID]) ?>')">
                                                    <?= UI_ICON::bi('check-circle') ?> 应用
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- 分页 -->
                        <?php if ($totalPages > 1): ?>
                            <?php UI_STRUCTURE::pageCutNav($page, $totalPages, "/manage/auth/batchToUsers/" . htmlspecialchars($authGroupId) . (!empty($search) ? "?search=" . urlencode($search) : "") . "?page="); ?>
                        <?php endif; ?>

                        <!-- 批量操作按钮 -->
                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">批量操作</h5>
                                <p class="text-muted small">已选择 <strong id="selectedCount">0</strong> 个用户</p>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-primary" onclick="batchAction('replace')">
                                        <?= UI_ICON::bi('arrow-repeat') ?> 替换权限
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="batchAction('merge')">
                                        <?= UI_ICON::bi('plus-circle') ?> 合并权限
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="batchAction('remove')">
                                        <?= UI_ICON::bi('x-circle') ?> 移除权限
                                    </button>
                                </div>
                                <p class="text-muted small mt-2">
                                    <strong>替换：</strong>将用户权限完全替换为当前权限组的权限<br>
                                    <strong>合并：</strong>将当前权限组的权限添加到用户现有权限中<br>
                                    <strong>移除：</strong>从用户权限中移除当前权限组的权限
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
    let actionText = '';
    switch(action) {
        case 'replace':
            actionText = '替换权限';
            confirmMessage = '确定要将选中的用户权限替换为当前权限组的权限吗？\n\n此操作将完全覆盖用户原有的权限。';
            break;
        case 'merge':
            actionText = '合并权限';
            confirmMessage = '确定要将当前权限组的权限合并到选中用户吗？\n\n此操作将保留用户原有权限并添加新权限。';
            break;
        case 'remove':
            actionText = '移除权限';
            confirmMessage = '确定要从选中用户移除当前权限组的权限吗？';
            break;
    }

    if (confirm(confirmMessage + '\n\n将对 ' + selectedIds.length + ' 个用户执行' + actionText + '操作。')) {
        document.getElementById('actionField').value = action;
        document.getElementById('batchForm').submit();
    }
}

function applyToUser(username, userId) {
    const checkbox = document.querySelector(`.user-checkbox[value="${userId}"]`);
    if (checkbox) {
        checkbox.checked = true;
        updateSelectAll();
    }
    if (confirm(`确定要将权限组应用到用户 "${username}" 吗？\n\n此操作将替换用户原有的权限。`)) {
        batchAction('replace');
    }
}

// 初始化选中数量
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedCount();
});
</script>

<?php UI_STRUCTURE::footer(); ?>
