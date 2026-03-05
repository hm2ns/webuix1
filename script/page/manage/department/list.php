<?php

UI_STRUCTURE::header('部门管理', 1, '部门列表');

// 获取部门列表
$departments = DEPARTMENT::getAll(DEPARTMENT::BASIC_ROWS);

// 处理搜索和筛选
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

if ($search) {
    $departments = DEPARTMENT::search($search, ['name', 'code', 'description']);
}

if ($statusFilter) {
    $departments = array_filter($departments, function($dept) use ($statusFilter) {
        return $dept[DEPARTMENT::KEY_STATUS] === $statusFilter;
    });
    $departments = array_values($departments);
}

// 构建表格数据
$tableData = [['ID', '部门名称', '部门编码', '层级', '负责人', '状态', '创建时间', '操作']];

foreach ($departments as $dept) {
    // 获取负责人信息
    $leaderName = '未设置';
    if (!empty($dept[DEPARTMENT::KEY_LEADER])) {
        $leader = USER::getUserInfoById($dept[DEPARTMENT::KEY_LEADER]);
        if ($leader) {
            $leaderName = htmlspecialchars($leader[USER::KEY_NICKNAME] ?? $leader[USER::KEY_USERNAME]);
        }
    }

    // 获取状态标签
    $statusClass = $dept[DEPARTMENT::KEY_STATUS] === DEPARTMENT::STATUS_NORMAL ? 'success' : 'secondary';
    $statusText = $dept[DEPARTMENT::KEY_STATUS] === DEPARTMENT::STATUS_NORMAL ? '正常' : '禁用';

    // 构建操作按钮
    $actions = '<div class="btn-group" role="group">';
    $actions .= '<button type="button" class="btn btn-sm btn-primary" onclick="location.href=\'/manage/department/edit/' . htmlspecialchars($dept[DEPARTMENT::KEY_ID]) . '\'">编辑</button>';
    $actions .= '<button type="button" class="btn btn-sm btn-info" onclick="location.href=\'/manage/department/users/' . htmlspecialchars($dept[DEPARTMENT::KEY_ID]) . '\'">用户</button>';
    $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(\'' . htmlspecialchars($dept[DEPARTMENT::KEY_ID]) . '\', \'' . htmlspecialchars($dept[DEPARTMENT::KEY_NAME]) . '\')">删除</button>';
    $actions .= '</div>';

    $tableData[] = [
        htmlspecialchars($dept[DEPARTMENT::KEY_ID]),
        htmlspecialchars($dept[DEPARTMENT::KEY_NAME]),
        htmlspecialchars($dept[DEPARTMENT::KEY_CODE] ?? '-'),
        $dept[DEPARTMENT::KEY_LEVEL],
        $leaderName,
        '<span class="badge bg-' . $statusClass . '">' . $statusText . '</span>',
        getDate_full(getDate_Auto($dept[DEPARTMENT::KEY_CREATE_TIME] ?? '')),
        $actions
    ];
}

?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">部门列表</h4>
                    <button type="button" class="btn btn-primary" onclick="location.href='/manage/department/add'">
                        <?= UI_ICON::bi('plus') ?> 添加部门
                    </button>
                </div>

                <!-- 搜索和筛选 -->
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search"
                                    placeholder="搜索部门名称、编码或描述..."
                                    value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <?= UI_ICON::bi('search') ?> 搜索
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">全部状态</option>
                                <option value="<?= DEPARTMENT::STATUS_NORMAL ?>" <?= $statusFilter === DEPARTMENT::STATUS_NORMAL ? 'selected' : '' ?>>正常</option>
                                <option value="<?= DEPARTMENT::STATUS_DISABLED ?>" <?= $statusFilter === DEPARTMENT::STATUS_DISABLED ? 'selected' : '' ?>>禁用</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-secondary" onclick="location.href='/manage/department/list'">
                                <?= UI_ICON::bi('x-circle') ?> 清空筛选
                            </button>
                        </div>
                    </div>
                </form>

                <!-- 部门列表 -->
                <?php
                if (empty($departments)) {
                    echo '<div class="text-center py-5">';
                    echo '<h5 class="text-muted">暂无部门数据</h5>';
                    echo '<p class="text-muted">点击上方"添加部门"按钮创建第一个部门</p>';
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
                        '部门列表'
                    );
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    if (confirm('确定要删除部门 "' + name + '" 吗？\n\n注意：删除部门后，该部门的用户将失去部门归属。')) {
        location.href = '/manage/department/delete/' + id;
    }
}
</script>

<?php UI_STRUCTURE::footer(); ?>
