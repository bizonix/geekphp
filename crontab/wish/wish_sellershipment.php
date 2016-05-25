<?php
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();
$time = time()-3600*24*30;
$whereData = "source_platform=4 and company_id = come_from and seller_ship_status = 0 and shop_id > 0 and order_status = 'APPROVED' and handle_status IN (3,6,7,8,9,10,11,12,15,16,17,18) and create_time > ".$time;
//获取定订单的总数
$ordersTotal = M("Order")->getDataCount($whereData);
//设置每页的数据
$perPage = 100;		
$page = ceil($ordersTotal/$perPage);  

for ($i=1;$i<=$page;$i++){
	$orders = M("Order")->getData("id,order_id,source_platform,tracking_number,transport_type",$whereData,' order by id asc ',1,$perPage);
	
	foreach($orders as $k=>$v){
		if(empty($v["tracking_number"])){
			continue;
		}
		startSellerShippment($v,0);
	}
}

function startSellerShippment($v,$j=0){

	$trackingNumber = $v["tracking_number"];
	$res = A("Order")->act_sellerShippment($v["source_platform"],$v["order_id"],$trackingNumber,$v["transport_type"],'','all','');
	$errMsg = A("Order")->act_getLastErrorMsg();
	log::writeLog("\r\n source_platform={$v["source_platform"]} | order_id={$v["order_id"]} | transport_type={$v["transport_type"]} | trackingNumber={$trackingNumber} \r\n res = ".json_encode($errMsg),"crontab/wish/sellershippment","auto_SellerShippment","d");
}