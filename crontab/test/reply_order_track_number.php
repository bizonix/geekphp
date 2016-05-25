<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";

Core::getInstance();
$time = time()-3600*10;
$orders = M("Order")->getAllData("id,create_time","seller_ship_status=1  and seller_ship_time > {$time}");
foreach($orders as $k=>$v){
		M("OrderDetails")->setTablePrefix('_'.date('Y_m',$v["create_time"]));
		$type = M("OrderDetails")->getSingleData("shipping_type","id={$v['id']}");
		if($type['shipping_type'] != "CNPSS"){
			continue;
		}
		$number = 499013790+$v['id'];
		$updateData		= array(
			"tracking_number"	=> 'RI'.$number.'CN',
		);
		$res = M("Order")->updateData($v["id"],$updateData);
		if($res){
			echo $v["id"]." 更新成功，{$v['id']} ==> {$number} \r\n";
		}else{
			echo $v['id']." 更新失败！";
		}
}