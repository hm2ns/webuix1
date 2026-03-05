<?php

$error = '';
$success = '';
$importResult = null; // 存储导入结果

// 处理批量导入请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'batch_import') {
    try {
        $jsonData = $_POST['users_data'] ?? '';

        if (empty($jsonData)) {
            throw new InvalidArgumentException('没有导入数据');
        }

        $users = json_decode($jsonData, true);
        if (!is_array($users)) {
            throw new InvalidArgumentException('数据格式错误');
        }

        $createdCount = 0;
        $updatedCount = 0;
        $failedUsers = [];

        foreach ($users as $index => $userData) {
            try {
                $username = trim($userData['username'] ?? '');
                $nickname = trim($userData['nickname'] ?? '');
                $password = trim($userData['password'] ?? '');
                $email = trim($userData['email'] ?? '');
                $phone = trim($userData['phone'] ?? '');
                $deptCode = trim($userData['department_code'] ?? '');
                $role = trim($userData['role'] ?? USER::DEFAULT_ROLE);

                // 验证必填字段
                if (empty($username) || empty($nickname) || empty($password)) {
                    throw new InvalidArgumentException("第{$index}行：用户名、昵称和密码不能为空");
                }

                // 验证密码长度
                if (strlen($password) < 6) {
                    throw new InvalidArgumentException("第{$index}行：密码长度至少为6位");
                }

                // 检查用户名是否已存在
                $existingUser = USER::getUserInfoByUsername($username, USER::ALL_ROWS);

                // 获取部门ID（通过部门编码）
                $departmentId = null;
                if (!empty($deptCode)) {
                    $dept = DEPARTMENT::getByCode($deptCode);
                    if ($dept) {
                        $departmentId = $dept[DEPARTMENT::KEY_ID];
                    }
                }

                if ($existingUser) {
                    // 更新已有用户（包括密码）
                    $updateData = [
                        USER::KEY_ID => $existingUser[USER::KEY_ID],
                        USER::KEY_PASSWORD => password_hash($password, USER::PASSWORD_CODER),
                        USER::KEY_NICKNAME => $nickname,
                        USER::KEY_EMAIL => $email ?: null,
                        USER::KEY_PHONE => $phone ?: null,
                        USER::KEY_DEPARTMENT => $departmentId,
                        USER::KEY_ROLE => $role
                    ];

                    USER::updateUser($updateData);
                    $updatedCount++;
                } else {
                    // 创建新用户（使用Excel中提供的密码）
                    $newUserData = [
                        USER::KEY_USERNAME => $username,
                        USER::KEY_PASSWORD => password_hash($password, USER::PASSWORD_CODER),
                        USER::KEY_NICKNAME => $nickname,
                        USER::KEY_EMAIL => $email ?: null,
                        USER::KEY_PHONE => $phone ?: null,
                        USER::KEY_DEPARTMENT => $departmentId,
                        USER::KEY_ROLE => $role,
                        USER::KEY_STATUS => USER::STATUS_NORMAL
                    ];

                    USER::createUser($newUserData);
                    $createdCount++;
                }
            } catch (Exception $e) {
                $failedUsers[] = "第{$index}行：" . $e->getMessage();
            }
        }

        $importResult = [
            'total' => count($users),
            'created' => $createdCount,
            'updated' => $updatedCount,
            'failed' => count($failedUsers),
            'failedUsers' => $failedUsers
        ];

        $success = "导入完成！共处理 {$importResult['total']} 条数据，创建 {$createdCount} 个用户，更新 {$updatedCount} 个用户";
        if (!empty($failedUsers)) {
            $success .= "，失败 {$importResult['failed']} 条";
        }
        UI_NOTICE::alert($success, !empty($failedUsers) ? BS_WARNING : BS_SUCCESS);
    } catch (Exception $e) {
        $error = $e->getMessage();
        UI_NOTICE::alert($error, BS_DANGER);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'batch_import') {
    try {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        $nickname = trim($_POST['nickname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $departmentId = trim($_POST['department_id'] ?? '');
        $role = trim($_POST['role'] ?? USER::DEFAULT_ROLE);
        $status = trim($_POST['status'] ?? USER::STATUS_NORMAL);

        // 验证必填字段
        if (empty($username)) {
            throw new InvalidArgumentException('用户名不能为空');
        }

        if (empty($password)) {
            throw new InvalidArgumentException('密码不能为空');
        }

        if ($password !== $confirmPassword) {
            throw new InvalidArgumentException('两次输入的密码不一致');
        }

        if (strlen($password) < 6) {
            throw new InvalidArgumentException('密码长度至少为6位');
        }

        if (empty($nickname)) {
            throw new InvalidArgumentException('昵称不能为空');
        }

        // 检查用户名是否已存在
        if (USER::usernameExists($username)) {
            throw new InvalidArgumentException('用户名已存在');
        }

        // 检查邮箱是否已存在
        if (!empty($email) && USER::emailExists($email)) {
            throw new InvalidArgumentException('邮箱已被使用');
        }

        // 检查手机号是否已存在
        if (!empty($phone) && USER::phoneExists($phone)) {
            throw new InvalidArgumentException('手机号已被使用');
        }

        // 验证邮箱格式
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('邮箱格式不正确');
        }

        // 准备用户数据
        $userData = [
            USER::KEY_USERNAME => $username,
            USER::KEY_PASSWORD => password_hash($password, USER::PASSWORD_CODER),
            USER::KEY_NICKNAME => $nickname,
            USER::KEY_EMAIL => $email ?: null,
            USER::KEY_PHONE => $phone ?: null,
            USER::KEY_DEPARTMENT => $departmentId ?: null,
            USER::KEY_ROLE => $role,
            USER::KEY_STATUS => $status
        ];

        // 创建用户
        $userId = USER::createUser($userData);

        if ($userId) {
            $success = '用户创建成功！';
            UI_NOTICE::alert($success, BS_SUCCESS);
        } else {
            throw new RuntimeException('用户创建失败，请重试');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        UI_NOTICE::alert($error, BS_DANGER);
    }
}

// 获取所有部门（用于部门选择）
$allDepartments = DEPARTMENT::getAll(DEPARTMENT::BASIC_ROWS);
$allRoles = USER::ROLES;

UI_STRUCTURE::header('添加用户', 1, '用户管理');
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs mb-4" id="userTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="single-tab" data-bs-toggle="tab" data-bs-target="#single" type="button" role="tab">
                            <?= UI_ICON::bi('person-plus') ?> 单个添加
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="batch-tab" data-bs-toggle="tab" data-bs-target="#batch" type="button" role="tab">
                            <?= UI_ICON::bi('file-earmark-spreadsheet') ?> 批量导入
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="userTabContent">
                    <!-- 单个添加 -->
                    <div class="tab-pane fade show active" id="single" role="tabpanel">
                        <h5 class="card-title mb-4">单个添加用户</h5>

                        <?php if (!empty($success) && !$importResult): ?>
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
                                    <label for="username" class="form-label">用户名 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username"
                                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                        maxlength="255" required>
                                    <div class="form-text">用于登录的用户名，建议使用字母、数字或下划线</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="nickname" class="form-label">昵称 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nickname" name="nickname"
                                        value="<?= htmlspecialchars($_POST['nickname'] ?? '') ?>"
                                        maxlength="64" required>
                                    <div class="form-text">用户显示名称</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">密码 <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password"
                                        minlength="6" required>
                                    <div class="form-text">密码长度至少为6位</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">确认密码 <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                        minlength="6" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">邮箱</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                        maxlength="255">
                                    <div class="form-text">用于找回密码等操作</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">手机号</label>
                                    <input type="text" class="form-control" id="phone" name="phone"
                                        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                        maxlength="32">
                                    <div class="form-text">用于手机号登录等操作</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="department_id" class="form-label">部门</label>
                                    <select class="form-select" id="department_id" name="department_id">
                                        <option value="">未分配部门</option>
                                        <?php foreach ($allDepartments as $dept): ?>
                                            <option value="<?= htmlspecialchars($dept[DEPARTMENT::KEY_ID]) ?>"
                                                <?= (($_POST['department_id'] ?? '') === $dept[DEPARTMENT::KEY_ID]) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($dept[DEPARTMENT::KEY_NAME]) ?>
                                                (<?= htmlspecialchars($dept[DEPARTMENT::KEY_CODE] ?? '无编码') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">选择用户所属部门</div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">角色</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <?php foreach ($allRoles as $roleKey => $roleName): ?>
                                            <option value="<?= $roleKey ?>"
                                                <?= (($_POST['role'] ?? USER::DEFAULT_ROLE) === $roleKey) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($roleName) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">用户在系统中的角色</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">状态</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status"
                                            id="status_normal" value="<?= USER::STATUS_NORMAL ?>"
                                            <?= (($_POST['status'] ?? USER::STATUS_NORMAL) === USER::STATUS_NORMAL) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="status_normal">
                                            <span class="badge bg-success">正常</span>
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status"
                                            id="status_disabled" value="<?= USER::STATUS_DISABLED ?>"
                                            <?= (($_POST['status'] ?? '') === USER::STATUS_DISABLED) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="status_disabled">
                                            <span class="badge bg-warning">禁用</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-text">禁用的用户无法登录系统</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="/manage/user/list" class="btn btn-secondary">
                                    <?= UI_ICON::bi('arrow-left') ?> 返回列表
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <?= UI_ICON::bi('check-circle') ?> 创建用户
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- 批量导入 -->
                    <div class="tab-pane fade" id="batch" role="tabpanel">
                        <h5 class="card-title mb-4">批量导入用户</h5>

                        <?php if ($importResult): ?>
                            <div class="alert alert-<?= $importResult['failed'] > 0 ? 'warning' : 'success' ?> alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($success) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>

                            <?php if (!empty($importResult['failedUsers'])): ?>
                                <div class="alert alert-danger" role="alert">
                                    <h6 class="alert-heading">失败详情</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($importResult['failedUsers'] as $failed): ?>
                                            <li><?= htmlspecialchars($failed) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="alert alert-info" role="alert">
                            <h6 class="alert-heading">导入说明</h6>
                            <ul class="mb-0">
                                <li>支持上传 <strong>.xlsx</strong> 格式的 Excel 文件</li>
                                <li>第一行为标题行，必须包含以下列：用户名、昵称、密码</li>
                                <li>可选列：邮箱、手机号、部门编码、角色</li>
                                <li>如果用户名已存在，将自动更新该用户信息（包括密码）</li>
                                <li>密码长度至少为6位</li>
                                <li>角色值：ROOT、ADMIN、MANAGER、TEACHER、STUDENT</li>
                            </ul>
                        </div>

                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h6 class="card-title">Excel 文件模板</h6>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>用户名*</th>
                                            <th>昵称*</th>
                                            <th>密码*</th>
                                            <th>邮箱</th>
                                            <th>手机号</th>
                                            <th>部门编码</th>
                                            <th>角色</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>user001</td>
                                            <td>张三</td>
                                            <td>123456</td>
                                            <td>zhangsan@example.com</td>
                                            <td>13800138000</td>
                                            <td>IT</td>
                                            <td>STUDENT</td>
                                        </tr>
                                        <tr>
                                            <td>user002</td>
                                            <td>李四</td>
                                            <td>123456</td>
                                            <td>lisi@example.com</td>
                                            <td>13800138001</td>
                                            <td>HR</td>
                                            <td>TEACHER</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="downloadTemplate()">
                                    <?= UI_ICON::bi('download') ?> 下载模板
                                </button>
                            </div>
                        </div>

                        <form method="POST" id="batchForm">
                            <input type="hidden" name="action" value="batch_import">
                            <input type="hidden" name="users_data" id="users_data" value="">

                            <div class="mb-3">
                                <label for="fileInput" class="form-label">选择 Excel 文件</label>
                                <input type="file" class="form-control" id="fileInput" accept=".xlsx,.xls">
                                <div class="form-text">请选择要导入的 Excel 文件</div>
                            </div>

                            <div class="mb-3" id="previewContainer" style="display: none;">
                                <label class="form-label">数据预览</label>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered" id="previewTable">
                                        <!-- 动态填充 -->
                                    </table>
                                </div>
                                <div class="mt-2">
                                    <span class="badge bg-info">共 <span id="previewCount">0</span> 条数据</span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="/manage/user/list" class="btn btn-secondary">
                                    <?= UI_ICON::bi('arrow-left') ?> 返回列表
                                </a>
                                <div>
                                    <button type="button" class="btn btn-outline-primary" onclick="previewFile()">
                                        <?= UI_ICON::bi('eye') ?> 预览
                                    </button>
                                    <button type="submit" class="btn btn-primary" onclick="return submitBatchImport();">
                                        <?= UI_ICON::bi('upload') ?> 开始导入
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let parsedData = [];

// 下载模板
function downloadTemplate() {
    const headers = ['用户名', '昵称', '密码', '邮箱', '手机号', '部门编码', '角色'];
    const data = [
        headers,
        ['user001', '张三', '123456', 'zhangsan@example.com', '13800138000', 'IT', 'STUDENT'],
        ['user002', '李四', '123456', 'lisi@example.com', '13800138001', 'HR', 'TEACHER']
    ];

    const ws = XLSX.utils.aoa_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "用户模板");

    // 设置列宽
    ws['!cols'] = [
        { wch: 15 },
        { wch: 15 },
        { wch: 15 },
        { wch: 25 },
        { wch: 15 },
        { wch: 15 },
        { wch: 15 }
    ];

    XLSX.writeFile(wb, "用户导入模板.xlsx");
}

// 预览文件
function previewFile() {
    const fileInput = document.getElementById('fileInput');
    const file = fileInput.files[0];

    if (!file) {
        alert('请先选择一个 Excel 文件');
        return false;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

            if (jsonData.length < 2) {
                alert('文件中没有有效数据');
                return;
            }

            parsedData = jsonData.slice(1).map(row => {
                const obj = {};
                jsonData[0].forEach((header, index) => {
                    obj[header] = row[index] || '';
                });
                return obj;
            });

            displayPreview(jsonData);
        } catch (error) {
            alert('文件解析失败：' + error.message);
        }
    };
    reader.readAsArrayBuffer(file);
    return false;
}

// 显示预览
function displayPreview(data) {
    const previewContainer = document.getElementById('previewContainer');
    const previewTable = document.getElementById('previewTable');
    const previewCount = document.getElementById('previewCount');

    previewTable.innerHTML = '';
    previewCount.textContent = data.length - 1;

    // 生成表格
    for (let i = 0; i < Math.min(11, data.length); i++) {
        const row = data[i];
        const tr = document.createElement('tr');

        row.forEach(cell => {
            const td = document.createElement(i === 0 ? 'th' : 'td');
            td.textContent = cell !== undefined ? cell : '';
            tr.appendChild(td);
        });

        previewTable.appendChild(tr);

        if (i === 10 && data.length > 11) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = data[0].length;
            td.className = 'text-center text-muted';
            td.textContent = `... 还有 ${data.length - 11} 条数据`;
            tr.appendChild(td);
            previewTable.appendChild(tr);
        }
    }

    previewContainer.style.display = 'block';
}

// 提交批量导入
function submitBatchImport() {
    if (parsedData.length === 0) {
        alert('请先预览文件内容');
        return false;
    }

    const mappedData = parsedData.map(row => {
        return {
            username: row['用户名'] || '',
            nickname: row['昵称'] || '',
            password: row['密码'] || '',
            email: row['邮箱'] || '',
            phone: row['手机号'] || '',
            department_code: row['部门编码'] || '',
            role: row['角色'] || 'STUDENT'
        };
    });

    document.getElementById('users_data').value = JSON.stringify(mappedData);

    const confirmed = confirm(`确认导入 ${mappedData.length} 条数据吗？\n\n注意：如果用户名已存在，将自动更新该用户信息。`);
    return confirmed;
}

// 文件选择变化时清空预览
document.getElementById('fileInput').addEventListener('change', function() {
    document.getElementById('previewContainer').style.display = 'none';
    parsedData = [];
});
</script>

<?php UI_STRUCTURE::footer(); ?>
