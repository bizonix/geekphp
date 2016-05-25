<?php
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();

$time = time()-3600*24*60;
//获取定订单的总数
$ordersTotal = M("Order")->getDataCount("seller_ship_status = 0 and order_status = 'WAIT_SELLER_SEND_GOODS' and handle_status IN (3,6,7,8,9,10,11,15,16,17,18) and create_time > ".$time);
//设置每页的数据
$perPage = 100;		
$page = ceil($ordersTotal/$perPage);  

$trackNumberConf = array(499013790,599013790,699013790,799013790,899013790,399013790,299013790,199013790);
for ($i=1;$i<=$page;$i++){
	$orders = M("Order")->getData("id,order_id,source_platform,tracking_number,transport_type","seller_ship_status = 0 and order_status = 'WAIT_SELLER_SEND_GOODS' and handle_status IN (3,6,7,8,9,10,11,15,16,17,18) and create_time > ".$time,' order by id asc ',1,$perPage);
	
	foreach($orders as $k=>$v){
		if(empty($v["tracking_number"])){
			continue;
		}
		startSellerShippment($v,$trackNumberConf,0);
	}
}

function startSellerShippment($v,$trackNumberConf,$j=0){

	switch ($v["tracking_number"]) {
		case 'YANWEN_JYT':
			$trackingNumber = 'RI'.($trackNumberConf[$j]+$v["id"]).'CN';
			break;
		case 'CHP':
			$trackingNumber = 'RU'.($trackNumberConf[$j]+$v["id"]).'CH';
			break;
		case 'SEP':
			$trackingNumber = 'RE'.($trackNumberConf[$j]+$v["id"]).'SE';
			break;
		default:
			$trackingNumber = $v["tracking_number"];
			break;
	}
	$res = A("Order")->act_sellerShippment($v["source_platform"],$v["order_id"],$trackingNumber,$v["transport_type"],'','all','');
	$errMsg = A("Order")->act_getLastErrorMsg();
	if(isset($errMsg[0]) && $errMsg[0] == "30001"){
		if($j<7){
			startSellerShippment($v,$trackNumberConf,++$j);
		}
	}
}