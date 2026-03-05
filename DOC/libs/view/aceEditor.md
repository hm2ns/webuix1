# view\aceEditor.php

**文件路径**: `\script\lib\view\aceEditor.php`

## 类定义

### 类: `aceEditor`

富文本编辑器
@param string $code 初始内容
@param string $language 语言
@param int $rl 只读模式，0为可编辑，1为只读
@param string $outname 输出的表单名称，默认为ace-加上随机id
@return string 返回编辑器的id，可以通过这个id来获取编辑器内容
