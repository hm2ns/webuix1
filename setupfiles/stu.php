<?php
try {
    Stu::initTable();
    return true;
} catch (Exception $e) {
    throw $e;
    return false;
}
