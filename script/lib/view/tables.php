<?php

const TABLE_DEFAULT_CFG = ["striped", "hover"];
const TABLE_FIRST_ROW_DANGER = ["table-danger"];
const TABLE_FIRST_ROW_WARNING = ["table-warning"];
const TABLE_FIRST_ROW_SUCCESS = ["table-success"];
const TABLE_FIRST_ROW_INFO = ["table-info"];
const TABLE_FIRST_ROW_DARK = ["table-dark"];
const TABLE_FIRST_ROW_LIGHT = ["table-light"];
const TABLE_FIRST_ROW_PRIMARY = ["table-primary"];
const TABLE_FIRST_ROW_SECONDARY = ["table-secondary"];
const TABLE_FIRST_ROW_MUTED = ["table-muted"];

class UI_TABLE
{
    /**
     * 使用JQDataTable插件增强表格功能，返回表格ID以供后续操作
     * @param string $id                    表格ID
     * @param bool $info 是否显示表格信息    默认true
     * @param bool $paging 是否启用分页     默认true
     * @param bool $search 是否启用搜索功能    默认true
     * @return string 返回表格ID
     */
    public static function JQDataTable(string $id, bool $info = true, bool $paging = true, bool $search = true): string
    {
        $newjs = "
        $('#$id').DataTable({
            searching: $search, paging: $paging, info: $info
        });";
        UI_STRUCTURE::appendImport("js", $newjs);
        return $id;
    }
    /**
     * 生成一个基础的HTML表格，返回表格ID以供后续操作
     * @param array $data 表格数据，二维数组，第一行作为表头
     * @param array $cfg 表格配置类，如striped、hover等，默认空数组
     * @param array $rowcfg 行配置类，二维数组，第一维为行索引，第二维为配置类，如table-danger等，默认空数组
     * @param array $colcfg 列配置类，三维数组，第一维为行索引，第二维为列索引，第三维为配置类，如table-danger等，默认空数组
     * @return string 返回表格ID
     */
    public static function common(array $data, array $cfg = [], array $rowcfg = [], array $colcfg = [])
    {
        $tableid = uuidGenerator("table-");
        $gcfg = "";
        foreach ($cfg as $value) {
            $gcfg .= " table-$value";
        }
        echo "<div class=\"table-responsive\" id=\"table-container-$tableid\">\n";
        echo "<table class='table $gcfg' id='$tableid'>\n";
        $data = array_values($data);

        $rcfg = $rowcfg[0] ?? "";
        echo "<thead><tr class='$rcfg'>";
        for ($i = 0; $i < count($data[0]); $i++) {
            $ccfg = $colcfg[0][$i] ?? "";
            echo "<th class=\"$ccfg\">" . $data[0][$i] . "</th>";
        }
        echo "</tr></thead>\n";
        echo "<tbody>";
        for ($i = 1; $i < count($data); $i++) {
            $rcfg = $rowcfg[$i] ?? "";
            echo "<tr class='$rcfg'>";
            for ($j = 0; $j < count($data[$i]); $j++) {
                $ccfg = $colcfg[$i][$j] ?? "";
                echo "<td class='$ccfg'>" . $data[$i][$j] . "</td>";
            }
            echo "</tr>\n";
        }
        echo "</tbody>\n</table>\n</div>";
        return $tableid;
    }
    /**
     * 生成一个增强功能的表格，使用JQDataTable插件，返回表格ID以供后续操作
     * @param array $data 表格数据，二维数组，第一行作为表头
     * @param array $cfg 表格配置类，如striped、hover等，默认空数组
     * @param array $rowcfg 行配置类，二维数组，第一维为行索引，第二维为配置类，如table-danger等，默认空数组
     * @param array $colcfg 列配置类，三维数组，第一维为行索引，第二维为列索引，第三维为配置类，如table-danger等，默认空数组
     * @param bool $info 是否显示表格信息    默认true
     * @param bool $paging 是否启用分页     默认true
     * @param bool $search 是否启用搜索功能    默认true
     * @return string 返回表格ID
     */
    public static function super(
        array $data,
        array $cfg = TABLE_DEFAULT_CFG,
        array $rowcfg = [],
        array $colcfg = [],
        bool $info = true,
        bool $paging = true,
        bool $search = true
    ) {
        $tableid = self::common($data, $cfg, $rowcfg, $colcfg);
        return self::JQDataTable($tableid, $info, $paging, $search);
    }

    const EXPORT_CONTROLER_ECHO = 0; //直接输出html控件
    const EXPORT_CONTROLER_RETURN = 1; //返回html控件字符串，由调用者决定如何使用
    const EXPORT_CONTROLER_NONE = 3; //不生成导出控件，由调用者自行处理

    /**
     * 生成一个增强功能的表格，使用JQDataTable插件，并添加导出功能
     * @param array $data 表格数据，二维数组，第一行作为表头
     * @param array $cfg 表格配置类，如striped、hover等，默认TABLE_DEFAULT_CFG
     * @param array $rowcfg 行配置类，二维数组，第一维为行索引，第二维为配置类，如table-danger等，默认空数组
     * @param array $colcfg 列配置类，三维数组，第一维为行索引，第二维为列索引，第三维为配置类，如table-danger等，默认空数组
     * @param bool $info 是否显示表格信息    默认true
     * @param bool $paging 是否启用分页     默认true
     * @param bool $search 是否启用搜索功能    默认true
     * @param string $filename 导出文件名，默认"表格导出"
     * @param int $controler 是否生成导出控件，0直接输出html控件，1返回html控件字符串，由调用者决定如何使用，3不生成导出控件，由调用者自行处理，默认0
     * @return string 返回表格ID或导出控件HTML字符串，取决于$controler参数
     */

    public static function super_downloadable(
        array $data,
        array $cfg = TABLE_DEFAULT_CFG,
        array $rowcfg = [],
        array $colcfg = [],
        bool $info = true,
        bool $paging = true,
        bool $search = true,
        string $filename = "表格导出",
        int $controler = 0
    ) {
        $tableid = self::common($data, $cfg, $rowcfg, $colcfg);
        self::JQDataTable($tableid, $info, $paging, $search);
        $newhtml = <<<HTML
                <div class="row mt-2">
                    <div class="col-4">
                        <input type="text" id="filename-{$tableid}" value="{$filename}" placeholder="输入文件名" class="form-control">
                    </div>

                    <div class="col-4">
                        <select id="filetype-{$tableid}" class="form-control">
                            <option value="xlsx">XLSX (Excel)</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>

                    <div class="col-4">
                        <button id="export-btn-{$tableid}" onclick="exportTable(`{$tableid}`)" class="btn btn-primary form-control">导出表格</button>
                    </div>
                </div>
HTML;
        if ($controler === self::EXPORT_CONTROLER_ECHO) {
            echo $newhtml;
        } elseif ($controler === self::EXPORT_CONTROLER_RETURN) {
            return $newhtml;
        }
        return $tableid;
    }
}
