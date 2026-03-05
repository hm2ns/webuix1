<?php

const SECONDS_PER_DAY = 86400;
const SECONDS_PER_HOUR = 3600;
const SECONDS_PER_MINUTE = 60;
date_default_timezone_set('Asia/Shanghai');
function autoConvertTime(string|int $time = 0)
{
    date_default_timezone_set('Asia/Shanghai');
    if (empty($time) || $time == "" || $time == "0") {
        return time();
    }
    if (is_string($time)) {
        $time = strtotime($time);
    }
    return $time;
}

function getDate_full(string|int $stamp = 0)
{
    $stamp = autoConvertTime($stamp);
    return date("Y-m-d H:i:s", $stamp);
}

function getDate_daily(string|int $stamp = 0)
{
    $stamp = autoConvertTime($stamp);
    return date("Y-m-d", $stamp);
}
function getDate_time(string|int $stamp = 0)
{
    $stamp = autoConvertTime($stamp);
    return date("H:i:s", $stamp);
}

function getDate_Auto(string|int $stamp)
{
    $stamp = autoConvertTime($stamp);
    $diff = time() - $stamp;
    $diff = abs($diff);
    if ($diff < SECONDS_PER_DAY) {
        return getDate_time($stamp);
    }
    if (date('Y') === date('Y', $stamp)) {
        return date("m-d", $stamp);
    }
    return date("Y-m-d", $stamp);
}
/**
 * 自动返回传入值到现在相距的时间，如果超过1天，就返回日期
 * @return string
 */
function getDate_ToNow(string|int $stamp = 0)
{
    //自动返回传入值到现在相距的时间，如果超过1天，就返回日期
    $stamp = autoConvertTime($stamp);
    $now = time();
    $diff = $now - $stamp;
    $day = 3600 * 24;
    if ($diff > $day * 7) {
        return date("Y-m-d H:i:s", $stamp);
    } elseif ($diff > $day) {
        return round($diff / (60 * 24 * 60), 2) . "天前";
    } elseif ($diff > 3600) {
        return floor($diff / 3600) . "小时前";
    } elseif ($diff > 60) {
        return floor($diff / 60) . "分钟前";
    } elseif ($diff > 0) {
        return $diff . "秒前";
    } else {
        return "刚刚";
    }
}
/**
 * 将秒转为时分秒
 * @param int $seconds
 * @return string
 */
function secondsToTime(int $seconds): string
{
    $hours = floor($seconds / SECONDS_PER_HOUR);
    $minutes = floor(($seconds % SECONDS_PER_DAY) / SECONDS_PER_MINUTE);
    $seconds = $seconds % SECONDS_PER_MINUTE;

    if ($minutes == 0 && $hours == 0) {
        return sprintf("%02d", $seconds);
    }
    if ($hours == 0) {
        return sprintf("%02d:%02d", $minutes, $seconds);
    }
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
}
