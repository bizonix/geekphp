<?php
//echo date("Y-m-d H:i:s",1430928000);exit;
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();

$startTime = time()-3600*24*30;
$endTime = time();
$res = A('Statistics')->insertSalesSku($startTime,$endTime);
log::writeLog(json_encode($res),'crontab/system/statics','sku','d');
