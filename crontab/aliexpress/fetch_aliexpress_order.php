<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";

Core::getInstance();

$shops = M("Shops")->getAllData("*","platform=2");
$endTime        = time()+3600*8;
$startTime      = $endTime-7200*16;
$startTime	= date("m/d/Y H:i:s",$startTime);
$endTime	= date("m/d/Y H:i:s",$endTime);
foreach ($shops as $key => $value) {
    if(!$value['shop_account']) continue;
    $token = json_decode($value['token'],true);
    if(empty($token) || empty($token['appKey']) || empty($token['appSecret']) || empty($token['refreshToken'])) continue;
    $res = A("ApiIntegration")->act_getAliexpressOrder($value['id'],'WAIT_SELLER_SEND_GOODS');
    //if(!$res) print_r(A("ApiIntegration")->act_getLastErrorMsg());
    //else
    log::writeLog("feth_order_res = ".json_encode($res),"orders/aliexpress/".$value['shop_account'],"system_feth_order",'d');
}
