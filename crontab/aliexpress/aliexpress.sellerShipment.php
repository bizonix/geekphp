<?php
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();

$time = time()-3600*24*5;
//获取定订单的总数
$whereData   = "source_platform=2 and seller_ship_status = 0 and shop_id > 0 and order_status = 'WAIT_SELLER_SEND_GOODS' and handle_status IN (3,6,7,8,9,10,11,12,15,16,17,18) and create_time > ".$time;
$ordersTotal = M("Order")->getDataCount($whereData);
//设置每页的数据
$perPage = 100;		
$page = ceil($ordersTotal/$perPage);
$trackNumberConf = array(499013790,599013790,699013790,799013790,899013790,399013790,299013790,199013790);
for ($i=1;$i<=$page;$i++){
	$orders = M("Order")->getData("id,order_id,source_platform,tracking_number,transport_type,create_time",$whereData,' order by id asc ',1,$perPage);
	foreach($orders as $k=>$v){
		M("OrderDetails")->setTablePrefix('_'.date('Y_m',$v["create_time"]));
		$orderDetails = M("OrderDetails")->getSingleData("shipping_type",array("id"=>$v['id']));
		$v['shipping_type'] = $orderDetails['shipping_type'];
		if(empty($v["tracking_number"]) && !in_array($v["shipping_type"], array('CNPSS','CHPTS'))){
			continue;
		}
		startSellerShippment($v,$trackNumberConf,0);
	}
}

function startSellerShippment($v,$trackNumberConf,$j=0){
	$transportUrl = 'http://www.wclop.com';
	$sendType	  = '';
	if(in_array($v["shipping_type"], array('CNPSS','CHPTS'))){
		$sendType = 'other';
		if(in_array($v["transport_type"], array('YANWEN_JYT','CPAM'))){

			$trackingNumber = 'RI'.($trackNumberConf[$j]+$v["id"]).'CN';

		}elseif($v["transport_type"] == 'CHP'){

			$trackingNumber = 'RU'.($trackNumberConf[$j]+$v["id"]).'CH';

		}elseif($v["transport_type"] == 'SEP'){

			$trackingNumber = 'RE'.($trackNumberConf[$j]+$v["id"]).'SE';

		}elseif($v["transport_type"] == 'SGP'){
			$trackingNumber = 'RB'.($trackNumberConf[$j]+$v["id"]).'SG';
		}elseif($v["transport_type"] == 'EMS_ZX_ZX_US'){  //EUB跟踪号
			$trackingNumber = 'LN'.($trackNumberConf[$j]+$v["id"]).'CN';
		}
	}
	if(empty($trackingNumber)){
		$trackingNumber = $v["tracking_number"];
		$sendType 		= $v["transport_type"];
	}

	$res = A("Order")->act_sellerShippment($v["source_platform"],$v["order_id"],$trackingNumber,$sendType,'','all',$transportUrl);
	$errMsg = A("Order")->act_getLastErrorMsg();
	log::writeLog("\r\n source_platform={$v["source_platform"]} | order_id={$v["order_id"]} | transport_type={$v["transport_type"]} | trackingNumber={$trackingNumber} \r\n res = ".json_encode($errMsg),"crontab/smt/sellershippment","auto_SellerShippment","d");
	if(isset($errMsg[0]) && $errMsg[0] == "30002"){
		if($j<7){
			startSellerShippment($v,$trackNumberConf,++$j);
		}
	}
	
}