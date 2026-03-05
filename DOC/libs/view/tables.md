# view\tables.php

**文件路径**: `\script\lib\view\tables.php`

## 函数定义

### 方法: `JQDataTable`

使用JQDataTable插件增强表格功能，返回表格ID以供后续操作
@param string $id                    表格ID
@param bool $info 是否显示表格信息    默认true
@param bool $paging 是否启用分页     默认true
@param bool $search 是否启用搜索功能    默认true
@return string 返回表格ID

### 方法: `common`

生成一个基础的HTML表格，返回表格ID以供后续操作
@param array $data 表格数据，二维数组，第一行作为表头
@param array $cfg 表格配置类，如striped、hover等，默认空数组
@param array $rowcfg 行配置类，二维数组，第一维为行索引，第二维为配置类，如table-danger等，默认空数组
@param array $colcfg 列配置类，三维数组，第一维为行索引，第二维为列索引，第三维为配置类，如table-danger等，默认空数组
@return string 返回表格ID

### 方法: `super`

生成一个增强功能的表格，使用JQDataTable插件，返回表格ID以供后续操作
@param array $data 表格数据，二维数组，第一行作为表头
@param array $cfg 表格配置类，如striped、hover等，默认空数组
@param array $rowcfg 行配置类，二维数组，第一维为行索引，第二维为配置类，如table-danger等，默认空数组
@param array $colcfg 列配置类，三维数组，第一维为行索引，第二维为列索引，第三维为配置类，如table-danger等，默认空数组
@param bool $info 是否显示表格信息    默认true
@param bool $paging 是否启用分页     默认true
@param bool $search 是否启用搜索功能    默认true
@return string 返回表格ID

### 方法: `super_downloadable`

生成一个增强功能的表格，使用JQDataTable插件，并添加导出功能
@param array $data 表格数据，二维数组，第一行作为表头
@param array $cfg 表格配置类，如striped、hover等，默认TABLE_DEFAULT_CFG
@param array $rowcfg 行配置类，二维数组，第一维为行索引，第二维为配置类，如table-danger等，默认空数组
@param array $colcfg 列配置类，三维数组，第一维为行索引，第二维为列索引，第三维为配置类，如table-danger等，默认空数组
@param bool $info 是否显示表格信息    默认true
@param bool $paging 是否启用分页     默认true
@param bool $search 是否启用搜索功能    默认true
@param string $filename 导出文件名，默认"表格导出"
@param int $controler 是否生成导出控件，0直接输出html控件，1返回html控件字符串，由调用者决定如何使用，3不生成导出控件，由调用者自行处理，默认0
@return string 返回表格ID或导出控件HTML字符串，取决于$controler参数
