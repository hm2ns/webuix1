<?php

UI_STRUCTURE::header('权限组管理', 1, '权限组列表');

// 初始化RBAC
RBAC::init();

// 获取所有权限组
$authGroups = AUTHORITIES::getAll(AUTHORITIES::BASIC_ROWS);

// 处理搜索和筛选
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

if ($search) {
    $authGroups = AUTHORITIES::search($search, ['name', 'code', 'description']);
}

if ($statusFilter) {
    $authGroups = array_filter($authGroups, function($group) use ($statusFilter) {
        return $group[AUTHORITIES::KEY_STATUS] === $statusFilter;
    });
    $authGroups = array_values($authGroups);
}

// 按排序字段排序
usort($authGroups, function($a, $b) {
    $sortA = intval($a[AUTHORITIES::KEY_SORT] ?? 0);
    $sortB = intval($b[AUTHORITIES::KEY_SORT] ?? 0);
    if ($sortA === $sortB) {
        return 0;
    }
    return ($sortA < $sortB) ? -1 : 1;
});

// 获取所有权限
$allPermissions = RBAC::getAllPermissions();

// 构建表格数据
$tableData = [['ID', '权限组名称', '权限组编码', '权限列表', '基础权限组', '排序', '状态', '创建时间', '操作']];

foreach ($authGroups as $group) {
    // 获取权限列表
    $permissions = [];
    if (!empty($group[AUTHORITIES::KEY_PERMISSIONS]) && is_array($group[AUTHORITIES::KEY_PERMISSIONS])) {
        foreach ($group[AUTHORITIES::KEY_PERMISSIONS] as $perm => $value) {
            if ($value === true) {
                $permissions[] = RBAC::getPermissionName($perm);
            }
        }
    }
    $permissionsText = !empty($permissions) ? implode(', ', $permissions) : '无权限';

    // 获取基础权限组
    $baseGroupText = '无';
    if (!empty($group[AUTHORITIES::KEY_BASE_GROUP])) {
        $baseGroup = AUTHORITIES::getById($group[AUTHORITIES::KEY_BASE_GROUP]);
        if ($baseGroup) {
            $baseGroupText = htmlspecialchars($baseGroup[AUTHORITIES::KEY_NAME]);
        }
    }

    // 获取状态标签
    $statusClass = $group[AUTHORITIES::KEY_STATUS] === AUTHORITIES::STATUS_NORMAL ? 'success' : 'secondary';
    $statusText = $group[AUTHORITIES::KEY_STATUS] === AUTHORITIES::STATUS_NORMAL ? '正常' : '禁用';

    // 构建操作按钮
    $actions = '<div class="btn-group" role="group">';
    $actions .= '<button type="button" class="btn btn-sm btn-primary" onclick="location.href=\'/manage/auth/edit/' . htmlspecialchars($group[AUTHORITIES::KEY_ID]) . '\'">编辑</button>';
    $actions .= '<button type="button" class="btn btn-sm btn-info" onclick="location.href=\'/manage/auth/users/' . htmlspecialchars($group[AUTHORITIES::KEY_ID]) . '\'">用户</button>';
    $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(\'' . htmlspecialchars($group[AUTHORITIES::KEY_ID]) . '\', \'' . htmlspecialchars($group[AUTHORITIES::KEY_NAME]) . '\')">删除</button>';
    $actions .= '</div>';

    $tableData[] = [
        htmlspecialchars($group[AUTHORITIES::KEY_ID]),
        htmlspecialchars($group[AUTHORITIES::KEY_NAME]),
        htmlspecialchars($group[AUTHORITIES::KEY_CODE] ?? '-'),
        $permissionsText,
        $baseGroupText,
        $group[AUTHORITIES::KEY_SORT],
        '<span class="badge bg-' . $statusClass . '">' . $statusText . '</span>',
        getDate_full(getDate_Auto($group[AUTHORITIES::KEY_CREATE_TIME] ?? '')),
        $actions
    ];
}

?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">权限组列表</h4>
                    <button type="button" class="btn btn-primary" onclick="location.href='/manage/auth/add'">
                        <?= UI_ICON::bi('plus') ?> 添加权限组
                    </button>
                </div>

                <!-- 搜索和筛选 -->
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search"
                                    placeholder="搜索权限组名称、编码或描述..."
                                    value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <?= UI_ICON::bi('search') ?> 搜索
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">全部状态</option>
                                <option value="<?= AUTHORITIES::STATUS_NORMAL ?>" <?= $statusFilter === AUTHORITIES::STATUS_NORMAL ? 'selected' : '' ?>>正常</option>
                                <option value="<?= AUTHORITIES::STATUS_DISABLED ?>" <?= $statusFilter === AUTHORITIES::STATUS_DISABLED ? 'selected' : '' ?>>禁用</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-secondary" onclick="location.href='/manage/auth/list'">
                                <?= UI_ICON::bi('x-circle') ?> 清空筛选
                            </button>
                        </div>
                    </div>
                </form>

                <!-- 权限组列表 -->
                <?php
                if (empty($authGroups)) {
                    echo '<div class="text-center py-5">';
                    echo '<h5 class="text-muted">暂无权限组数据</h5>';
                    echo '<p class="text-muted">点击上方"添加权限组"按钮创建第一个权限组</p>';
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
                        '权限组列表'
                    );
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    if (confirm('确定要删除权限组 "' + name + '" 吗？\n\n注意：删除权限组后，不会影响已应用该权限组的用户。')) {
        location.href = '/manage/auth/delete/' + id;
    }
}
</script>

<?php UI_STRUCTURE::footer(); ?>
