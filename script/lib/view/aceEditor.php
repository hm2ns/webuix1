<?php

/**
 * 富文本编辑器
 * @param string $code 初始内容
 * @param string $language 语言
 * @param int $rl 只读模式，0为可编辑，1为只读
 * @param string $outname 输出的表单名称，默认为ace-加上随机id
 * @return string 返回编辑器的id，可以通过这个id来获取编辑器内容
 */
class aceEditor
{
    public $id;

    public function __construct($code = "", $language = "markdown", $rl = 0, $outname = "")
    {
        $code = str_replace("`", "\\`", $code);
        $id = uuidGenerator("ace_");

        if ($outname === "") {
            $outname = "ace-$id";
        }

        $theme = GLOBAL_CONFIG::get('aceTheme', 'github');
        echo <<<HTML
        <input id="ace-$id" name="$outname" type="hidden">
        <pre id='codeEditor{$id}' class="ace_editor" style="min-height:320px"><s:textarea class="ace_text-input" cssStyle="width:97.5%;height:320px;"/></pre>
        <script>
        initEditor($id,'$language',$rl);
        editors[$id]?.insert?.(`$code`);
        editors[$id]?.setTheme?.("ace/theme/$theme");
        </script>
HTML;
        return $id;
    }
}
