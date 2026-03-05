<?php
UI_STRUCTURE::header('首页', 1);
UI_NOTICE::alert('欢迎来到WEBUI-X的主页！', BS_SUCCESS);
UI_NOTICE::alert('这是一个示例页面，展示了UI组件的使用。', BS_PRIMARY);

?>
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between flex-wrap">
            <div class="d-flex align-items-end flex-wrap">
                <div class="me-md-3 me-xl-5">
                    <h2>你好,</h2>
                    <p class="mb-md-0">ZSV Studio & 实验电创 联合为您呈现</p>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-end flex-wrap">
                <button type="button" class="btn btn-light bg-white btn-icon me-3 d-none d-md-block ">
                    <i class="mdi mdi-download text-muted"></i>
                </button>
                <button type="button" class="btn btn-light bg-white btn-icon me-3 mt-2 mt-xl-0">
                    <i class="mdi mdi-clock-outline text-muted"></i>
                </button>
                <button type="button" class="btn btn-light bg-white btn-icon me-3 mt-2 mt-xl-0">
                    <i class="mdi mdi-plus text-muted"></i>
                </button>
                <button class="btn btn-primary mt-2 mt-xl-0">Generate report</button>
            </div>
        </div>
    </div>
</div>
<div class="row my-2">
    <div class="col-md-12 stretch-card">
        <div class="card">
            <div class="card-body">
                <p class="card-title">MarkDown</p>
                <div id="mddemo"># Hello!</div>
                <?php
                UI_MARKDOWN::Parser("mddemo");
                ?>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12 stretch-card">
        <div class="card">
            <div class="card-body">
                <p class="card-title">最近记录</p>
                <?php
                $tableData = [
                    ['ID', '名称', '日期'],
                    ['1', '记录一', '2024-06-01'],
                    ['2', '记录二', '2024-06-02'],
                    ['3', '记录三', '2024-06-03']
                ];
                UI_TABLE::super_downloadable(
                    $tableData,
                    TABLE_DEFAULT_CFG,
                    [],
                    [],
                    true,
                    true,
                    true,
                    '最近记录'
                );
                ?>
            </div>
        </div>
    </div>
</div>
<?php
UI_STRUCTURE::footer();
?>