<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";

Core::getInstance();
$whereArr = array("platform" => 4);
if(!empty($argv[1])){
	$whereArr['belong_company'] = $argv[1];
}
$shops = M("Shops")->getAllData("*",$whereArr);
foreach ($shops as $key => $value) {
	A("WishButt")->setConfig($value['shop_account'] , $value['token']);
	$res = A("WishButt")->retrieveUnfulfilledOrders();
	$res = json_decode($res,true);
	if(empty($res["data"])){
		continue;
	}
	$orderList = $res["data"];
	$orderArr =  array();
	foreach ($orderList as $k=>$v){
		//订单时间转换
		$orderTime = str_replace("T"," ",$v['Order']['order_time']);
		$orderTime = strtotime($orderTime)+3600*8;
		$simpleDetail 		= array(
			"item_name"				=> $v['Order']['product_name'],
			"item_url"				=> '',
			"item_img"				=> $v['Order']['product_image_url'],
			"item_count"			=> $v['Order']['quantity'],
			"item_currency_code"	=> '$',
			"item_total_pay"		=> ($v['Order']['price']+$v['Order']['shipping'])*$v['Order']['quantity'],
		);

		if(in_array($v['Order']["ShippingDetail"]["country"],array('US','PR'))){
			//美國默认走EUB
			$transportType 	= 'EPacket';
			$shippingType 	= 'CNPTE';
		}else{
			//非美国国家默认中国邮政挂号
			$transportType 	= 'ChinaAirPost';
			$shippingType 	= 'CNPSR';
		}
		// if($value['belong_company'] == 1){
		// 	$deliveryFrom = 3; //如果是环环的，自动往赛维推送
		// }else{
			$deliveryFrom = $value['belong_company'];
		// }

		$mainOrder	= array(
			"user_id"	 				=> 0,            //订单所属用户的用户ID
			"company_id"				=> $value['belong_company'],			//所属公司
			"user_name"	 				=> $value['creater'],            //订单所属用户的用户名
			"simple_detail"				=> json_encode($simpleDetail), 		//订单中简单详细信息
			"order_id" 					=> $v['Order']['order_id'],				// string 订单编号
			"order_status" 				=> $v['Order']['state'],			// 订单状态
			"transport_type"			=> $transportType, //运输方式
			"source_platform" 			=> 4,		//订单来源平台
			"shop_id"					=> $value['id'],			//所屬店鋪
			"source_account" 			=> $value['shop_account'],			// 订单来源账号
			"source" 					=> 1,  				// 订单来源 1代表系统抓取
			"come_from"					=> $value['belong_company'],
			"delivery_from"				=> $deliveryFrom,		//代表微库
			"gmt_create"				=> $orderTime,
			"create_time" 				=> time(),            // 订单进入系统时间
		);

		//收货地址
		$receiptAddress = array(
			"zip"			=> $v['Order']["ShippingDetail"]["zipcode"],
			"address1"		=> $v['Order']["ShippingDetail"]["street_address1"],
			"address2"		=> $v['Order']["ShippingDetail"]["street_address2"],
			"address3"		=> $v['Order']['shipping_details'],
			"country"		=> $v['Order']["ShippingDetail"]["country"],
			"city"			=> $v['Order']["ShippingDetail"]["city"],
			"county"		=> $v['Order']["ShippingDetail"]["zipcode"],
			"phoneNumber"	=> $v['Order']["ShippingDetail"]["phone_number"],
			"mobileNo"		=> '',
			"province"		=> $v['Order']["ShippingDetail"]["state"],
			"contactPerson"	=> $v['Order']["ShippingDetail"]["name"],
		);
		if($v['Order']["ShippingDetail"]["country"] == 'PR' && empty($v['Order']["ShippingDetail"]["state"])){
		    $receiptAddress['province'] = $v['Order']["ShippingDetail"]["city"];
		}
		//购买者信息
		$buyerInfo	= array(
			"userName"	=> $v['Order']["ShippingDetail"]["name"],
			"email"		=> '',
			"country"	=> $v['Order']["ShippingDetail"]["country"],
		);
		//订单金额
		$orderAmount = array(
			"amount"		=> ($v['Order']['price']+$v['Order']['shipping'])*$v['Order']['quantity'],
			"actualAmount"	=> ($v['Order']['cost']+$v['Order']['shipping_cost'])*$v['Order']['quantity'],
			"currencyCode"	=> 'USD',
			"symbol"		=> '$',
		);
		//订单留言列表
		$orderMsgList = $v['Order']['ship_note'];
		//购买者留言
		$sellerMsgList = $v['Order']['refunded_reason'];
		//订单中产品信息
		$childOrderList = array();
		//获取订单的skuCode
		if(!empty($v['Order']['sku'])){
			$skuCode 			= explode("#",$v['Order']['sku']);
			$v['Order']['sku'] 	= $skuCode[0];
		}
		$childOrderList[] = array(
			"lotNum"               => $v['Order']['quantity'],  //物品数量
			"productAttributes"    => array(
					"sku"			   => $v['Order']['sku'],
					"itemPrice"		   => $v['Order']['price'],
					"itemId"		   => $v['Order']['product_id'],				//**************新增itemId**************
					"skuUrl"		   => "https://www.wish.com/c/{$v['Order']['product_id']}",
					"itemTitle"		   => $v['Order']['product_name'],
					"shippingFee" 	   => $v['Order']['shipping'],   			 //************新增运费************
				),
			"memo"					   => $v['Order']['ship_note'],
			"otherAttributes"		   => array(
				"color"		=> $v['Order']['color'],
				"size"		=> $v['Order']['size'],
			),
		);
		
		$detailOrder	= array(
			"source_details"			=> json_encode($v),
			"receiptAddress"			=> json_encode($receiptAddress),
			"buyerInfo"					=> json_encode($buyerInfo),
			"childOrderList"			=> json_encode($childOrderList),
			"orderAmount"				=> json_encode($orderAmount),
			"orderMsgList"				=> $orderMsgList,
			"sellerMsgList"				=> $sellerMsgList,
			"shipping_type"				=> $shippingType,
			"orderDeclarationContent"	=> "",
		);
		$order = array(
			"mainOrder"			=> $mainOrder,		//存入订单主表信息
			"detailOrder"		=> $detailOrder,	//存入订单详情表信息
		);
		
		$order_arr[] = $order;
	}
	$pushRes	= A("Order")->act_receiveOrders($order_arr);
	log::writeLog("feth wish order res = ".json_encode($pushRes),"orders/wish/".$value['shop_account'],"sys_fetch_wish_order",'d');
	//return $pushRes;
}