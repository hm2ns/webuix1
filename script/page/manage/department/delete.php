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

// 检查是否有子部门
$children = DEPARTMENT::getChildren($deptId);
$hasChildren = !empty($children);

// 确认删除（POST请求）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $confirm = $_POST['confirm'] ?? '';
        $forceDelete = isset($_POST['force_delete']);

        if ($confirm !== 'yes') {
            throw new InvalidArgumentException('请确认删除操作');
        }

        if ($hasChildren && !$forceDelete) {
            throw new InvalidArgumentException('部门下存在子部门，请先删除子部门或选择强制删除');
        }

        // 执行删除
        $result = DEPARTMENT::delete($deptId, $forceDelete);

        if ($result) {
            $success = '部门删除成功！';
            UI_NOTICE::alert($success, BS_SUCCESS);

            // 延迟跳转，让用户看到成功消息
            UI_STRUCTURE::appendImport('js', "setTimeout(() => { location.href = '/manage/department/list'; }, 1500);");
        } else {
            throw new RuntimeException('部门删除失败，请重试');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        UI_NOTICE::alert($error, BS_DANGER);
    }
}

// 获取该部门的用户数量（需要查看USER类是否有相关方法）
// 这里假设有方法可以获取部门的用户
$userCount = 0; // TODO: 实现获取部门用户数量的逻辑

UI_STRUCTURE::header('删除部门', 1, '部门管理');
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">删除部门</h4>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <?= UI_ICON::bi('check-circle', 'me-2 fs-4') ?>
                            <div>
                                <strong>删除成功</strong>
                                <div class="small"><?= htmlspecialchars($success) ?></div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php else: ?>

                    <!-- 警告信息 -->
                    <div class="alert alert-warning" role="alert">
                        <h5 class="alert-heading"><?= UI_ICON::bi('exclamation-triangle') ?> 警告</h5>
                        <p class="mb-0">您即将删除部门，此操作不可恢复！</p>
                    </div>

                    <!-- 部门信息 -->
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-3">部门信息</h5>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <strong>部门ID：</strong>
                                    <code><?= htmlspecialchars($department[DEPARTMENT::KEY_ID]) ?></code>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>部门名称：</strong>
                                    <?= htmlspecialchars($department[DEPARTMENT::KEY_NAME]) ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>部门编码：</strong>
                                    <?= htmlspecialchars($department[DEPARTMENT::KEY_CODE] ?? '无') ?>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>层级：</strong>
                                    Level <?= $department[DEPARTMENT::KEY_LEVEL] ?>
                                </div>
                            </div>
                            <?php if (!empty($department[DEPARTMENT::KEY_DESCRIPTION])): ?>
                                <hr>
                                <div>
                                    <strong>部门描述：</strong>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($department[DEPARTMENT::KEY_DESCRIPTION])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 子部门信息 -->
                    <?php if ($hasChildren): ?>
                        <div class="alert alert-danger" role="alert">
                            <h5 class="alert-heading"><?= UI_ICON::bi('x-circle') ?> 存在子部门</h5>
                            <p class="mb-2">该部门下有 <?= count($children) ?> 个子部门：</p>
                            <ul class="mb-0">
                                <?php foreach ($children as $child): ?>
                                    <li><?= htmlspecialchars($child[DEPARTMENT::KEY_NAME]) ?> (<?= htmlspecialchars($child[DEPARTMENT::KEY_CODE] ?? '无编码') ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- 用户信息 -->
                    <?php if ($userCount > 0): ?>
                        <div class="alert alert-info" role="alert">
                            <h5 class="alert-heading"><?= UI_ICON::bi('people') ?> 部门用户</h5>
                            <p class="mb-0">该部门下有 <strong><?= $userCount ?></strong> 个用户，删除部门后这些用户将失去部门归属。</p>
                        </div>
                    <?php endif; ?>

                    <!-- 错误信息 -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- 删除确认表单 -->
                    <form method="POST">
                        <input type="hidden" name="confirm" value="yes">

                        <?php if ($hasChildren): ?>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="force_delete" name="force_delete">
                                <label class="form-check-label" for="force_delete">
                                    <strong>强制删除</strong>：同时删除所有子部门（共 <?= count($children) ?> 个）
                                </label>
                                <div class="form-text text-danger">此操作将级联删除所有子部门，请谨慎操作！</div>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between">
                            <a href="/manage/department/edit/<?= htmlspecialchars($deptId) ?>" class="btn btn-secondary">
                                <?= UI_ICON::bi('arrow-left') ?> 返回编辑
                            </a>
                            <button type="submit" class="btn btn-danger"
                                <?= $hasChildren ? 'onclick="return confirmForceDelete();"' : 'onclick="return confirmDelete();"' ?>>
                                <?= UI_ICON::bi('trash') ?> 确认删除
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- 返回链接 -->
        <?php if (empty($success)): ?>
            <div class="mt-3 text-center">
                <a href="/manage/department/list" class="text-decoration-none text-muted">
                    <?= UI_ICON::bi('list') ?> 返回部门列表
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDelete() {
    return confirm('确定要删除此部门吗？\n\n此操作不可恢复！');
}

function confirmForceDelete() {
    if (!document.getElementById('force_delete').checked) {
        alert('请先勾选"强制删除"选项');
        return false;
    }
    return confirm('确定要强制删除此部门及其所有子部门吗？\n\n此操作将同时删除 <?= count($children) ?> 个子部门，不可恢复！');
}
</script>

<?php UI_STRUCTURE::footer(); ?>
