<?php

/**
 * 一个页面最基本的UI构成
 */
class UI_STRUCTURE
{
    public static $pagename = '';
    public static $title = '';
    const NAV_TYPE_NORMAL = 0;
    const NAV_TYPE_ONLYICONS = 1;
    const NAV_TYPE_ONLYTOP = 2;
    const NAV_TYPE_NONE = 3;

    private static array $importStack = [
        'messagebox' => '',
        'alert' => '',
        'js' => '',
        'css' => '',
        'temp' => ['acecnt' => 0],
    ];
    /**
     * 设置导入栈中的值（内部使用）
     */
    public static function setImport(string $key, string $value, bool $prepend = false): void
    {
        if (!isset(self::$importStack[$key])) {
            self::$importStack[$key] = '';
        }
        if ($prepend) {
            self::$importStack[$key] = $value . self::$importStack[$key];
        } else {
            self::$importStack[$key] = $value;
        }
    }

    /**
     * 累加导入栈中的值（用于消息、JS 等）
     */
    public static function appendImport(string $key, string $value): void
    {
        if (!isset(self::$importStack[$key])) {
            self::$importStack[$key] = '';
        }
        self::$importStack[$key] .= $value;
    }

    /**
     * 获取导入栈值
     */
    private static function getImport(string $key, string $default = ''): string
    {
        return self::$importStack[$key] ?? $default;
    }

    /**
     * 输出页面头部HTML结构
     *
     * @param string $title 页面标题，默认为"首页"
     * @param int $nav 导航栏类型：0-正常显示, 1-仅显示图标, 2-仅顶部导航, 3-无导航
     * @param string $pagename 页面名称，用于标识当前页面，默认为空则使用$title
     * @param string $oh 额外的HTML头部内容，如自定义CSS、meta标签等
     * 
     * @return void
     * 
     * @example
     * // 基本用法
     * UI_STRUCTURE::header("用户管理", 0, "user_manage");
     * 
     * // 自定义头部内容
     * UI_STRUCTURE::header(
     *     "仪表板", 
     *     0, 
     *     "dashboard",
     *     '<meta name="description" content="系统仪表板页面">'
     * );
     * 
     * @note 此方法会自动防止重复调用，多次调用只有第一次生效
     * @see VIEWER_STATE::HEADER_SHOWED() 检查头部是否已显示
     */
    public static function header(string $title = "首页", int $nav = 0, string $pagename = "", string  $oh = "")
    {
        if (VIEWER_STATE::HEADER_SHOWED()) {
            return;
        }
        self::$pagename = $pagename ?: $title;
        self::$title = $title;
        $title = $title . GLOBAL_CONFIG::get('titleSuffix', '');
        echo "<!DOCTYPE html>\n<head>\n";
        include includeViewer("header");
        echo "\n<title>", htmlspecialchars($title), "</title>\n";
        echo $oh;

        $navout = "";
        switch ($nav) {
            case 1:
                $navout = "sidebar-icon-only";
                break;
            case 2:
                $navout = "";
                echo "
                    <style>
                    .sidebar-offcanvas{ display:none; }
                    .main-panel{ margin:auto; }
                    </style>
                ";
                break;
            case 3:
                echo "</head>\n<body class='$navout'>";
                //echo '<div class="full-scroll"><div class="content-wrapper">';
                return;
            default:
                $navout = "";
                break;
        }

        echo "</head>\n<body class='$navout'>";
        include includeViewer("nav");
        echo '<div class="main-panel full-scroll"><div class="content-wrapper">';

        VIEWER_STATE::SET(VIEWER_STATE::KEY_HEADER_SHOWED, true);
        VIEWER_STATE::SET(VIEWER_STATE::KEY_SHOWED, true);
    }

    /**
     * 输出页面底部HTML结构并结束页面
     *
     * @return void
     * 
     * @example
     * // 标准用法，在页面内容输出完成后调用
     * UI_STRUCTURE::footer();
     * 
     * @note 此方法会自动防止重复调用，多次调用只有第一次生效
     * @note 会自动调用import()方法输出累积的消息框、警告框、JS和CSS
     * @see UI_STRUCTURE::import() 输出累积的资源
     * @see VIEWER_STATE::FOOTER_SHOWED() 检查底部是否已显示
     */
    public static function footer()
    {
        if (VIEWER_STATE::FOOTER_SHOWED()) {
            return;
        }
        echo "\n</div>\n";
        include includeViewer("foot");
        echo "\n</div>\n";
        self::import();
        VIEWER_STATE::SET(VIEWER_STATE::KEY_FOOTER_SHOWED, true);
    }

    /**
     * 输出累积的页面资源（消息框、警告框、JS、CSS）并关闭HTML标签
     *
     * @return void
     * 
     * @example
     * // 通常在footer()中自动调用，也可手动调用
     * UI_STRUCTURE::import();
     * 
     * @note 此方法会清空所有资源栈，避免重复输出
     * @note 输出顺序：MessageBox -> Alert -> JS -> CSS -> HTML结束标签
     * @see UI_STRUCTURE::appendImport() 向资源栈添加内容
     * @see UI_STRUCTURE::setImport() 设置资源栈内容
     */
    public static function import()
    {
        // MessageBox
        $mb = self::getImport('messagebox');
        echo "<div class=\"toast-container right-pos\" id='messageboxbox'>";
        echo $mb !== '' ? $mb : '';
        echo "</div>";

        // Alert
        $alert = self::getImport('alert');
        echo "<div class=\"top-pos\" id='alertboxbox'>";
        echo $alert !== '' ? $alert : '';
        echo "</div>";

        // JS
        $js = self::getImport('js');
        if ($js !== '') {
            echo "\n<script>\n" . $js . "\n</script>\n";
        }

        // CSS
        $css = self::getImport('css');
        if ($css !== '') {
            echo "\n<style>\n" . $css . "\n</style>\n";
        }

        // 清空栈（可选，但符合原逻辑）
        self::$importStack['messagebox'] = '';
        self::$importStack['alert'] = '';
        self::$importStack['js'] = '';
        self::$importStack['css'] = '';

        echo "\n</body>\n</html>";
    }

    /**
     * 生成分页导航HTML
     *
     * @param int $now 当前页码
     * @param int $total 总页数
     * @param string $url 基础URL，会在后面追加?page=参数
     * 
     * @return void 直接输出HTML到浏览器
     * 
     * @example
     * // 基本用法
     * UI_STRUCTURE::pageCutNav(3, 15, "/user/list");
     * // 输出：上一页 1 2 [3] 4 5 ... 15 下一页
     * 
     * // 带查询参数的URL
     * UI_STRUCTURE::pageCutNav(2, 8, "/search?keyword=admin");
     * // 输出：上一页 [1] 2 3 4 5 6 7 8 下一页
     * 
     * @note 当总页数<=1时不会输出任何内容
     * @note 当总页数<=10时显示所有页码
     * @note 当总页数>10时采用智能省略策略，保持当前页前后各2个页码可见
     * @note 会自动处理URL中已存在的查询参数
     */
    public static function pageCutNav($now, $total, $url)
    {
        if ($total <= 1) return;

        $url .= (strpos($url, "?") !== false) ? "&" : "?";

        echo "<div class='my-2'><ul class=\"pagination justify-content-center\">";

        // 上一页
        if ($now > 1) {
            echo "<li class=\"page-item\"><a class=\"page-link\" href=\"{$url}page=" . ($now - 1) . "\">上一页</a></li>";
        } else {
            echo "<li class=\"page-item disabled\"><a class=\"page-link\" href=\"#\">上一页</a></li>";
        }

        // 页码
        if ($total <= 10) {
            for ($i = 1; $i <= $total; $i++) {
                $active = ($i == $now) ? ' active' : '';
                echo "<li class=\"page-item{$active}\"><a class=\"page-link\" href=\"{$url}page={$i}\">{$i}</a></li>";
            }
        } else {
            $dotsBefore = false;
            $dotsAfter = false;
            for ($i = 1; $i <= $total; $i++) {
                if ($i == 1 || $i == $total || ($i >= $now - 2 && $i <= $now + 2)) {
                    $active = ($i == $now) ? ' active' : '';
                    echo "<li class=\"page-item{$active}\"><a class=\"page-link\" href=\"{$url}page={$i}\">{$i}</a></li>";
                    if ($i < $now - 2) $dotsBefore = true;
                    if ($i > $now + 2) $dotsAfter = true;
                } elseif ($i == 2 && !$dotsBefore) {
                    echo "<li class=\"page-item disabled\"><a class=\"page-link\" href=\"#\">...</a></li>";
                    $dotsBefore = true;
                } elseif ($i == $total - 1 && !$dotsAfter) {
                    echo "<li class=\"page-item disabled\"><a class=\"page-link\" href=\"#\">...</a></li>";
                    $dotsAfter = true;
                }
            }
        }

        // 下一页
        if ($now < $total) {
            echo "<li class=\"page-item\"><a class=\"page-link\" href=\"{$url}page=" . ($now + 1) . "\">下一页</a></li>";
        } else {
            echo "<li class=\"page-item disabled\"><a class=\"page-link\" href=\"#\">下一页</a></li>";
        }

        echo "</ul></div>";
    }
}
