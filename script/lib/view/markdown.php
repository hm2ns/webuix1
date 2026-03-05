<?php

/**
 * Markdown 相关的视图函数
 */
class UI_MARKDOWN
{
    /**
     * 从 DOM 中获取 Markdown 内容并解析为 HTML，适用于已经存在的元素内容需要被解析的情况
     * @param string $id 要解析的 DOM 元素的 ID，默认为 "pFace"
     * @param bool $noecho 是否返回解析脚本而不是直接输出，默认为 0（直接输出）
     */
    public static function Parser(string $id = "pFace", bool $noecho = false)
    {
        $script = "\n<script>mardownParser(`{$id}`)\n</script>\n";
        if ($noecho) {
            return $script;
        }
        echo $script;
        return null;
    }
}
