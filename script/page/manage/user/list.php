<?php

UI_STRUCTURE::header('用户管理', 1, '用户列表');

// 处理搜索和筛选
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$departmentFilter = $_GET['department'] ?? '';

// 获取用户列表
$users = [];

if (!empty($search)) {
    $users = USER::searchUsers($search, ['username', 'nickname', 'email', 'phone']);
} else {
    // 使用空搜索获取所有用户
    $users = USER::searchUsers('');
}

// 状态筛选
if ($statusFilter) {
    $users = array_filter($users, function($user) use ($statusFilter) {
        return ($user[USER::KEY_STATUS] ?? '') === $statusFilter;
    });
    $users = array_values($users);
}

// 角色筛选
if ($roleFilter) {
    $users = array_filter($users, function($user) use ($roleFilter) {
        return ($user[USER::KEY_ROLE] ?? '') === $roleFilter;
    });
    $users = array_values($users);
}

// 部门筛选
if ($departmentFilter) {
    $users = array_filter($users, function($user) use ($departmentFilter) {
        return ($user[USER::KEY_DEPARTMENT] ?? '') === $departmentFilter;
    });
    $users = array_values($users);
}

// 获取所有部门和角色用于筛选
$allDepartments = DEPARTMENT::getAll(DEPARTMENT::BASIC_ROWS);
$allRoles = USER::ROLES;

// 构建表格数据
$tableData = [['ID', '用户名', '昵称', '邮箱', '手机', '部门', '角色', '状态', '创建时间', '操作']];

foreach ($users as $user) {
    // 获取部门信息
    $deptName = '未分配';
    if (!empty($user[USER::KEY_DEPARTMENT])) {
        $dept = DEPARTMENT::getById($user[USER::KEY_DEPARTMENT]);
        if ($dept) {
            $deptName = htmlspecialchars($dept[DEPARTMENT::KEY_NAME]);
        }
    }

    // 获取角色名称
    $roleName = USER::getRoleName($user[USER::KEY_ROLE]);

    // 获取状态标签
    $statusClass = $user[USER::KEY_STATUS] === USER::STATUS_NORMAL ? 'success' : 'warning';
    $statusText = USER::getStatusName($user[USER::KEY_STATUS], false);

    // 构建操作按钮
    $actions = '<div class="btn-group" role="group">';
    $actions .= '<button type="button" class="btn btn-sm btn-primary" onclick="location.href=\'/manage/user/edit/' . htmlspecialchars($user[USER::KEY_ID]) . '\'">编辑</button>';
    $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(\'' . htmlspecialchars($user[USER::KEY_ID]) . '\', \'' . htmlspecialchars($user[USER::KEY_NICKNAME] ?? $user[USER::KEY_USERNAME]) . '\')">删除</button>';
    $actions .= '</div>';

    $tableData[] = [
        htmlspecialchars($user[USER::KEY_ID]),
        htmlspecialchars($user[USER::KEY_USERNAME]),
        htmlspecialchars($user[USER::KEY_NICKNAME] ?? '-'),
        htmlspecialchars($user[USER::KEY_EMAIL] ?? '-'),
        htmlspecialchars($user[USER::KEY_PHONE] ?? '-'),
        $deptName,
        htmlspecialchars($roleName),
        '<span class="badge bg-' . $statusClass . '">' . $statusText . '</span>',
        getDate_full(getDate_Auto($user[USER::KEY_CREATE_TIME] ?? '')),
        $actions
    ];
}

?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">用户列表</h4>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-primary" onclick="location.href='/manage/user/add'">
                            <?= UI_ICON::bi('plus') ?> 添加用户
                        </button>
                        <button type="button" class="btn btn-info" onclick="location.href='/manage/user/add#batch'">
                            <?= UI_ICON::bi('file-earmark-spreadsheet') ?> 批量导入
                        </button>
                    </div>
                </div>

                <!-- 搜索和筛选 -->
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search"
                                    placeholder="搜索用户名、昵称、邮箱或手机..."
                                    value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <?= UI_ICON::bi('search') ?> 搜索
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">全部状态</option>
                                <option value="<?= USER::STATUS_NORMAL ?>" <?= $statusFilter === USER::STATUS_NORMAL ? 'selected' : '' ?>>正常</option>
                                <option value="<?= USER::STATUS_DISABLED ?>" <?= $statusFilter === USER::STATUS_DISABLED ? 'selected' : '' ?>>禁用</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="role">
                                <option value="">全部角色</option>
                                <?php foreach ($allRoles as $roleKey => $roleName): ?>
                                    <option value="<?= $roleKey ?>" <?= $roleFilter === $roleKey ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($roleName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="department">
                                <option value="">全部部门</option>
                                <?php foreach ($allDepartments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept[DEPARTMENT::KEY_ID]) ?>"
                                        <?= $departmentFilter === $dept[DEPARTMENT::KEY_ID] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept[DEPARTMENT::KEY_NAME]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-secondary w-100" onclick="location.href='/manage/user/list'">
                                <?= UI_ICON::bi('x-circle') ?> 清空筛选
                            </button>
                        </div>
                    </div>
                </form>

                <!-- 用户列表 -->
                <?php
                if (empty($users)) {
                    echo '<div class="text-center py-5">';
                    echo '<h5 class="text-muted">暂无用户数据</h5>';
                    echo '<p class="text-muted">点击上方"添加用户"按钮创建第一个用户</p>';
                    echo '</div>';
                } else {
                    UI_TABLE::super_downloadable(
                        $tableData,
                        TABLE_DEFAULT_CFG,
                        [],
                        [],
                        true,
                        true,
                        true,
                        '用户列表'
                    );
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    if (confirm('确定要删除用户 "' + name + '" 吗？\n\n注意：删除用户后，该用户将无法登录系统。')) {
        location.href = '/manage/user/delete/' + id;
    }
}
</script>

<?php UI_STRUCTURE::footer(); ?>
