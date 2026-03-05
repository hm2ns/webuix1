<?php
const BS_SUCCESS = "success";
const BS_INFO = "info";
const BS_WARNING = "warning";
const BS_DANGER = "danger";
const BS_PRIMARY = "primary";
const BS_SECONDARY = "secondary";
const BS_LIGHT = "light";
const BS_DARK = "dark";
const BS_STRIPED = "striped";
const BS_HOVER = "hover";
const BS_ANIMATED = "animated";
const BS_BORDERED = "bordered";

/**
 * VIEWER状态全局变量
 */
class VIEWER_STATE
{
    const DOMIN = "viewer";
    const KEY_SHOWED = "showed";
    const KEY_HEADER_SHOWED = "header_showed";
    const KEY_FOOTER_SHOWED = "footer_showed";
    public static function GET($key)
    {
        return StateStorage::get($key, self::DOMIN);
    }
    public static function SET($key, $value)
    {
        StateStorage::set($key, $value, self::DOMIN);
    }

    public static function HEADER_SHOWED()
    {
        return self::GET(self::KEY_HEADER_SHOWED);
    }
    public static function FOOTER_SHOWED()
    {
        return self::GET(self::KEY_FOOTER_SHOWED);
    }
}

require_once includeLib("view/UIStructure");
require_once includeLib("view/errorPages");
require_once includeLib("view/buttons");
require_once includeLib("view/tables");
require_once includeLib("view/icon");
require_once includeLib("view/notice");
require_once includeLib("view/aceEditor");
require_once includeLib("view/markdown");
