<?php
if(MyAcc->logout()){
    header("Location: /");
}else{
    UI_ERRORPAGES::show(500,"退出登录失败");
}
