<?php
function getstatic($name)
{
    return file_get_contents("static/$name");
}

function includePage($pagename)
{
    return "./script/page/$pagename.php";
}
function includeViewer($name)
{
    return "./script/view/$name.php";
}
function includeC($name)
{
    return "./script/$name.php";
}

function includeLib($libname)
{
    return "./script/lib/$libname.php";
}

include_once "./config/consts.php";

require_once includeLib("stateStorage");
require_once includeLib("basic/time");
require_once includeLib("basic/tools");
require_once includeLib("basic/globalconfig");
require_once includeLib("debugger");

require_once includeLib("access/request");
require_once includeLib("access/security");
require_once includeLib("access/RBAC");
require_once includeLib("access/router");
require_once includeLib("access/token");

require_once includeLib("view/glo");

require_once includeLib("db/pgsql");
require_once includeLib("db/redis");
include_once includeLib("email");

require_once includeLib("user/user");
require_once includeLib("stu");
