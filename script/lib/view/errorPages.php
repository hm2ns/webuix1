<?php
class UI_ERRORPAGES
{

    public static $message = '';
    /**
     * 显示错误页面
     * ！不会截断脚本执行
     * @param int|string $code 错误代码，可以是数字或者字符串，默认为404
     * @param string $message 错误信息，默认为空字符串
     * @return void
     */
    static public function show(int|string $code = 404, string $message = '')
    {
        self::$message = $message;
        $htmlSatusCodes = [
            200 => 'OK',
            403 => '权限错误...',
            404 => '未找到页面...',
            500 => '服务器错误'
        ];
        if (isset($htmlSatusCodes[$code])) {
            if (!headers_sent()) {
                header("HTTP/1.1 $code $htmlSatusCodes[$code]");
            }
            $message = $message ?: $htmlSatusCodes[$code];
        }
        if (!VIEWER_STATE::HEADER_SHOWED()) {
            UI_STRUCTURE::header("出错啦！-" . $message, UI_STRUCTURE::NAV_TYPE_ONLYTOP);
        }
        if (!file_exists(includeViewer("error/$code"))) {
            include includeViewer("error/common");
        } else include includeViewer("error/$code");
        if (!VIEWER_STATE::FOOTER_SHOWED()) {
            UI_STRUCTURE::footer();
        }
    }
}
