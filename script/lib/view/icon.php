<?php

/**
 * 图标驱动
 */
class UI_ICON
{
    public static function mdi(string $name, string $other = "")
    {
        return "<i class='mdi mdi-$name $other'></i>";
    }
    
    public static function bi(string $name, string $other = "")
    {
        return "<i class='bi bi-$name $other'></i>";
    }
    
    public static function icon(string $name, string $other = "")
    {
        return self::mdi($name, $other);
    }
}
