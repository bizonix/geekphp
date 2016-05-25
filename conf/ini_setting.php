<?php
error_reporting(E_ALL & ~E_NOTICE);//去除系统的通知Notice
ini_set('date.timezone','Asia/Shanghai'); 										//时区
ini_set('session.cache_expire',  60);
ini_set('session.use_trans_sid', 0);
ini_set('session.use_cookies',   1);
ini_set('session.auto_start',    0);
//set_magic_quotes_runtime(0);													//只影响sql出入库时,get post 要在php.ini改magic_quotes_gpc
session_cache_limiter('private,must-revalidate');								//让返回保存资料
//$lifeTime_minute = 2*3600;
//ini_set("session_set_cookie_params",$lifeTime);
//ini_set("session.gc_maxlifetime", "$lifeTime_minute");
//session_cache_expire($lifeTime_minute);  										//session 保存期一天
header('Content-type: text/html; charset=utf-8'); 								//编码

?>