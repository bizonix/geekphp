<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";

Core::getInstance();
M("OrderDetails")->setTablePrefix('_2015_02');
$orderDetails = M("OrderDetails")->getAllData("*","id>10071 and id<10094");
foreach($orderDetails as $k=>$v){
	$sourceDetails 	= json_decode($v['source_details'],true);
	//收货地址
	$receiptAddress = array(
		"zip"			=> $sourceDetails['detail']["receiptAddress"]["zip"],
		"address1"		=> $sourceDetails['detail']['receiptAddress']['detailAddress'],
		"address2"		=> $sourceDetails['detail']['receiptAddress']['address2'],
		"address3"		=> '',
		"country"		=> $sourceDetails['detail']['receiptAddress']['country'],
		"city"			=> $sourceDetails['detail']['receiptAddress']['city'],
		"county"		=> $sourceDetails['detail']['receiptAddress']['phoneArea'],
		"phoneNumber"	=> $sourceDetails['detail']['receiptAddress']['phoneNumber'],
		"mobileNo"		=> $sourceDetails['detail']['receiptAddress']['mobileNo'],
		"province"		=> $sourceDetails['detail']['receiptAddress']['province'],
		"contactPerson"	=> $sourceDetails['detail']['receiptAddress']['contactPerson'],
	);
	//购买者信息
	$buyerInfo	= array(
		"userName"	=> $sourceDetails['detail']['buyerInfo']['firstName']." ".$sourceDetails['detail']['buyerInfo']['lastName'],
		"email"		=> $sourceDetails['detail']['buyerInfo']['email'],
		"country"	=> $sourceDetails['detail']['buyerInfo']['country'],
	);
	//订单金额
	$orderAmount = array(
		"amount"		=> $sourceDetails['detail']['orderAmount']['amount'],
		"currencyCode"	=> $sourceDetails['detail']['orderAmount']['currencyCode'],
		"symbol"		=> $sourceDetails['detail']['orderAmount']['currency']['symbol'],
	);
	//订单留言列表
	$orderMsgList = $sourceDetails['detail']['orderMsgList'];
	//订单中产品信息
	$childOrderList = array();
	foreach ($sourceDetails['detail']['childOrderList'] as $kk=>$vv){
		//获取订单的skuCode
		if(!empty($vv['skuCode'])){
			$skuCode 		= explode("#",$vv['skuCode']);
			$vv['skuCode'] 	= $skuCode[0];
		}
		$productAttributes = json_decode($vv['productAttributes'],true);
		$productSimpleDetails = array(
			"lotNum"               => $vv['productCount'],  //物品数量
			"productAttributes"    => array(
					"sku"			   => $vv['skuCode'],
					"itemPrice"		   => $vv['productPrice']['amount'],
					"itemId"		   => $vv['productId'],				//**************新增itemId**************
					"skuUrl"		   => $sourceDetails['v']['productList'][$kk]['productSnapUrl'],
					"itemTitle"		   => $vv['productName'],
					"shippingFee" 	   => $sourceDetails['v']['productList'][$kk]['logisticsAmount']['amount'],   			 //************新增运费************
				),
			"memo"					=> $sourceDetails['v']['productList'][$kk]['memo'],
		);
		foreach($productAttributes['sku'] as $kkk=>$vvv){
			$productSimpleDetails['otherAttributes'][$vvv['pName']] = $vvv['pValue'];
		}
		$childOrderList[] = $productSimpleDetails;
	}

	$updateData		= array(
		"receiptAddress"	=> json_encode($receiptAddress),
		"buyerInfo"			=> json_encode($buyerInfo),
		"childOrderList"	=> json_encode($childOrderList),
		"orderAmount"		=> json_encode($orderAmount),
		"orderMsgList"		=> json_encode($orderMsgList),
		"sellerMsgList"		=> '',
		"orderDeclarationContent"	=> "",
	);
	$res = M("OrderDetails")->updateData($v["id"],$updateData);
	if($res){
		echo $v["id"]."	success \r\n";
	}else{
		echo $v["id"]." error \r\n";
	}
}