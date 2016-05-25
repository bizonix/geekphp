<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();
A("ValsunButt")->setConfig(C("VALSUN_CONF")['appKey'],C("VALSUN_CONF")['appToken']);
$whereData = "company_id = 3 and delivery_from = 3 and handle_status IN (1,2) and create_time > ".(time()-3600*24*5);
//获取定订单的总数
$ordersTotal = M("Order")->getDataCount($whereData);
//设置每页的数据
$perPage = 100;		
$page = ceil($ordersTotal/$perPage);   
for ($i=1;$i<=$page;$i++){
	$orders = M("Order")->getData("id",$whereData,' order by id asc ',1,$perPage);
	
	$endOrders = array();
	foreach($orders as $vv){
		$endOrders[]	= $vv["id"];
	}

	$ret = A("ApiIntegration")->act_pushOrdersToValsun($endOrders);
	log::writeLog("valsunRet = ".json_encode($ret),"crontab/valsun/push/3","system_push_order","d");
}
