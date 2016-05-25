<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();
//获取配置信息appkey  token
A("ValsunButt")->setConfig(C("VALSUN_CONF")['appKey'],C("VALSUN_CONF")['appToken']);
$whereData = "company_id = 3 and delivery_from = 3 and handle_status IN (3,6,7,8,9,10,11,12,15,16,17,18) and delivery_time > ".(time()-3600*24*5);
$ordersTotal = M("Order")->getDataCount($whereData);
$perPage = 10;		
$page = ceil($ordersTotal/$perPage); 
for ($i=1;$i<=$page;$i++){
	$orders = M("Order")->key("id")->getData("id,simple_detail,order_id,source_platform",$whereData,' order by id asc ',$i,$perPage);
	$orderIds = array_keys($orders);
	$retData = A("ValsunButt")->getOrderDetails($orderIds);
	foreach($retData as $k=>$v){
		$simpleDetail = json_decode($orders[$k]["simple_detail"],true);
		$simpleDetail["orderFee"] = $v["orderFee"];
		$updateData = array("simple_detail"=>json_encode($simpleDetail),"update_time"=>time());
		$res = M("Order")->updateDataWhere($updateData,array("order_id" => $orders[$k]['order_id'],"delivery_from" => "3","source_platform" => $orders[$k]['source_platform']));
		if($res){
			echo "$k Success \r\n";
		}else{
			echo "$k Error \r\n";
		}
	}
}
