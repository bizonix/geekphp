<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";

Core::getInstance();
/*$CompanySynConf = M("CompanySynConf")->getAllData("*","platform_id=9");
foreach($CompanySynConf as $v){
	$appKey		= $v['app_key'];
	$appToken	= $v['app_token'];
	if(!$appKey || !$appToken) continue;
	//获取配置信息appkey  token
	A("ValsunButt")->setConfig($appKey,$appToken);  */
	A("ValsunButt")->setConfig(C("VALSUN_CONF")['appKey'],C("VALSUN_CONF")['appToken']);
	//$whereData = "company_id = ".$v['company_id']." and delivery_from = 3 and handle_status IN (3,6,7,8,9,10,11,15,16,17,18) and delivery_time > ".(time()-3600*24*5);
	$whereData = "company_id = 3 and delivery_from = 3 and handle_status IN (3,6,7,8,9,10,11,15,16,17,18) and delivery_time > ".(time()-3600*24*5);
	//获取定订单的总数
	$ordersTotal = M("Order")->getDataCount($whereData);
	//设置每页的数据
	$perPage = 100;		
	$page = ceil($ordersTotal/$perPage);   
	for ($i=1;$i<=$page;$i++){
		$ordersId = M("Order")->key("id")->getData("id,tracking_number,order_id,source_platform,new_order_sys_id,create_time",$whereData,' order by id asc ',$i,$perPage);
		$endOrders = array();
		$shippingType = array();
		foreach($ordersId as $vv){
			$endOrders[]	= $vv["id"];
			M("OrderDetails")->setTablePrefix('_'.date('Y_m',$vv["create_time"]));
			$orderDetails = M("OrderDetails")->getSingleData("shipping_type",array("id"=>$vv['id']));
			$shippingType[$vv["id"]] = $orderDetails['shipping_type'];
		}
		$valsunRet = A("ValsunButt")->synOrdersStatusAndTrackNumber($endOrders);
		$valsunRes = json_decode($valsunRet,true);
		if($valsunRes['errCode'] != 0){
			log::writeLog("syn_order_res = ".$valsunRet,"orders/valsun/3","system_syn_order",'d');
			continue;
		}
		$orders = array();
		foreach ($valsunRes['data'] as $kkk => $vvv) {
			$handleStatus = 0;
			if(empty($ordersId[$kkk]['tracking_number']) && !in_array($shippingType[$kkk], array('CNPSS','CHPTS'))){
				$trackingNumber = empty($vvv[0][0]['tracknumber']) ? "" : $vvv[0][0]['tracknumber'];
			}else{
				$trackingNumber = '';
			}
			switch ($vvv[0][0]['statusCode']) {
				case "0":
					$handleStatus = 15;
					break;
				case "1":
					$handleStatus = 9;
					break;
				case "2":
					$handleStatus = 12;
					break;
				case "3":
					$handleStatus = 6;
					break;
				case "4":
					$handleStatus = 14;
					break;
				case "5":
					$handleStatus = 16;
					break;
				case "6":
					$handleStatus = 10;
					break;
				case "7":
					$handleStatus = 13;
					break;
				case "8":
					$handleStatus = 5;
					break;
				case "9":
					$handleStatus = 6;
					break;
				case "10":
					$handleStatus = 2;
					break;
				case "11":
					$handleStatus = 17;
					break;
				case "12":
					$handleStatus = 18;
					break;
				case "13":
					$handleStatus = 11;
					break;
				default:
					$handleStatus = 0;
					break;
			}
			$updateData = array('id' => $kkk,"handle_status" => $handleStatus,'tracking_number' => $trackingNumber);			
// 			$updateData = array("handle_status" => $handleStatus,'delivery_time'=>time(),"update_time"=>time());			
			$orders[] = $updateData;
			// $updateRes = M("Order")->updateDataWhere($updateData,array("order_id" => $ordersId[$kkk]['order_id'],"delivery_from" => "3","source_platform" => $ordersId[$kkk]['source_platform']));
// 			$updateRes = M("Order")->updateData($ordersId[$kkk]['new_order_sys_id'],$updateData);
// 			if($updateRes){
// 				log::writeLog("syn_order_res Success {$kkk}=> ".json_encode($vvv[0][0]),"orders/valsun/3","system_syn_order",'d');
// 			}else{
// 				log::writeLog("syn_order_res Error {$kkk}=> ".json_encode($vvv[0][0]),"orders/valsun/3","system_syn_order",'d');
// 			}
		}
		if(!empty($orders)){
		    $updateRes = A('Order')->synOrderSomeInfo($orders);
		    log::writeLog("syn_order_res = ".json_encode($updateRes),"orders/valsun/3","system_syn_order",'d');
		}
	}

//}
