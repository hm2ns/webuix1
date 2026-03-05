# stateStorage.php

**文件路径**: `\script\lib\stateStorage.php`

## 类定义

### 类: `StateStorage`

状态存储类
用于临时存储当前会话信息，类似全局变量
内存存储，会话结束后自动销毁

## 函数
### `set`

    /**
     * 设置状态
     * @param string $key
     * @param mixed $value
     * @param string $domin 域，默认为main，可以用于区分不同模块的状态
     * @return bool 设置成功返回true
     */
### `get`
    /**
     * 获取状态
     * @param string $key
     * @param string $domin 域，默认为main，可以用于区分不同模块的状态
     * @return mixed 获取成功返回状态值
     */


