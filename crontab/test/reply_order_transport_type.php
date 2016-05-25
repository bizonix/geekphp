<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";

Core::getInstance();
$orders = M("Order")->getAllData("*","1");
$carriers = M("Carrier")->getAllData("*","1");
foreach($orders as $k=>$v){
	$PlatformCarrier = M("PlatformCarrier")->getData("serviceName",array("platformId"=>$v["source_platform"],"displayName"=>$v['transport_type']));
	if(!empty($PlatformCarrier)){
		$updateData		= array(
			"transport_type"	=> $PlatformCarrier[0]['serviceName'],
		);
		$res = M("Order")->updateData($v["id"],$updateData);
		if($res){
			echo $v["id"]." 更新成功，{$v['transport_type']} ==> {$PlatformCarrier[0]['serviceName']} \r\n";
		}else{
			echo $v['id']." 更新失败！";
		}
	}
}