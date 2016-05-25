<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();

$loop		= true;
$page		= 1;
$nowtime 	= time();
$ebay_start	= date('Y-m-d\TH:i:s', get_ebay_timestamp($nowtime-(3600*3)));
$ebay_end	= date('Y-m-d\TH:i:s', get_ebay_timestamp($nowtime));

$shops = M("Shops")->getAllData("*",array("platform" => 1));
foreach ($shops as $key => $value) {
	A("EbayButt")->setToken($value['shop_account'],$value['token']);
	$spiderlists = A("EbayButt")->spiderOrderId($ebay_start, $ebay_end);
	$orderids = array();
	foreach ($spiderlists AS $spiderlist){
		$orderids[] = $spiderlist['ebay_orderid'];
	}
	$orderList = A("Ebaybutt")->spiderOrderLists($orderids);
	$pushRes = handelEbayOrders($orderList,$value);
	log::writeLog("feth ebay order res = ".json_encode($pushRes),"orders/eaby/".$value['shop_account'],"sys_fetch_ebay_order",'d');
}

function handelEbayOrders($orderList,$value){
	$orders = array();
	if(!empty($orderList['GetOrdersResponse']['OrderArray']['Order']['OrderID'])){
		$orders = array($orderList['GetOrdersResponse']['OrderArray']['Order']);
	}else{
		$orders = $orderList['GetOrdersResponse']['OrderArray']['Order'];
	}
	$orderArr =  array();
	foreach ($orders as $orderInfo){
		$isNote = 0; //默认为没有留言订单
		//顾客留言
		$customerMessage = '';
		/**/
		if ($orderInfo['CheckoutStatus']['Status']!='Complete') {
			continue;
		}
		if (!isset($orderInfo['PaidTime']) || empty($orderInfo['PaidTime'])){
			continue;
		}
		if (isset($orderInfo['ShippedTime'])&&!empty($orderInfo['ShippedTime'])){
			//continue;
		}

		/**/
		if(!empty($orderInfo['ExternalTransaction']['BuyerCheckoutMessage'])){
			$isNote				= 1;
			$customerMessage	= @str_rep($orderInfo['ExternalTransaction']['BuyerCheckoutMessage']);
			$customerMessage	= str_replace('<![CDATA[','',$customerMessage);
			$customerMessage	= str_replace(']]>','',$customerMessage);
		}
		//modify by add 2014.10.18
		if(empty($customerMessage) && !empty($orderInfo['BuyerCheckoutMessage'])){
			$customerMessage = $orderInfo['BuyerCheckoutMessage'];
		}
		
		//获取第一个Transaction,多个料号和单个料号的格式不一样
		$orderAttribute = 1; //1为单料订单
		$firstTransaction 	= $orderInfo['TransactionArray']['Transaction'];
		if(!empty($firstTransaction) && empty($orderInfo['TransactionArray']['Transaction']['Buyer'])){
			$orderAttribute 	= 3; //多料号订单
			$firstTransaction = $orderInfo['TransactionArray']['Transaction'][0];
		}
		//站点
		$site = $firstTransaction['Item']['Site'];
		if(empty($site)){
			$site = $firstTransaction['TransactionID']; 
		}

		if(1 == 'US'){
			//美國默认走EUB
			$transportType 	= 'EPacket';
			$shippingType 	= 'CNPTE';
		}else{
			//非美国国家默认中国邮政挂号
			$transportType 	= 'ChinaAirPost';
			$shippingType 	= 'CNPSR';
		}
		/*if($value['belong_company'] == 1){
			$deliveryFrom = 2; //如果是环环的，自动往维库中推送
		}else{
			$deliveryFrom = $value['belong_company'];
		}*/

		$mainOrder	= array(
			"user_id"	 				=> 0,            //订单所属用户的用户ID
			"company_id"				=> $value['belong_company'],			//所属公司
			"user_name"	 				=> $value['creater'],            //订单所属用户的用户名
			"simple_detail"				=> '', 		//订单中简单详细信息
			"order_id" 					=> $orderInfo['OrderID'],				// string 订单编号
			"order_status" 				=> $orderInfo['OrderStatus'],			// 订单状态
			"transport_type"			=> '', //运输方式
			"source_platform" 			=> 1,		//订单来源平台
			"shop_id"					=> $value['id'],			//所屬店鋪
			"source_account" 			=> $value['shop_account'],			// 订单来源账号
			"source" 					=> 1,  				// 订单来源 1代表系统抓取
			"come_from"					=> $value['belong_company'],
			"delivery_from"				=> $value['belong_company'],		//代表微库
			"gmt_create"				=> strtotime($orderInfo['CreatedTime']),
			"create_time" 				=> time(),            // 订单进入系统时间
		);
		//收货地址
		$receiptAddress = array(
			"zip"			=> $orderInfo['ShippingAddress']['PostalCode'],
			"address1"		=> str_rep($orderInfo['ShippingAddress']['Street1']),
			"address2"		=> str_rep($orderInfo['ShippingAddress']['Street2']),
			"address3"		=> '',
			"country"		=> str_rep($orderInfo['ShippingAddress']['Country']),
			"city"			=> str_rep($orderInfo['ShippingAddress']['CityName']),
			"county"		=> '',
			"phoneNumber"	=> $orderInfo['ShippingAddress']['Phone'],
			"mobileNo"		=> '',
			"province"		=> str_rep($orderInfo['ShippingAddress']['StateOrProvince']),
			"contactPerson"	=> $v['Order']["ShippingDetail"]["name"],
		);
		//购买者信息
		$buyerInfo	= array(
			"userName"	=> mysql_real_escape_string(str_rep($orderInfo['ShippingAddress']['Name'])),
			"email"		=> $firstTransaction['Buyer']['Email'],
			"country"	=> str_rep($orderInfo['ShippingAddress']['Country']),
		);
		//订单金额
		$orderAmount = array(
			"amount"		=> $orderInfo['AmountPaid'],
			"actualAmount"	=> $orderInfo['AmountPaid'],
			"currencyCode"	=> $orderInfo['AmountSaved attr']['currencyID'],
			"symbol"		=> '$',
		);
		//订单留言列表
		$orderMsgList = $customerMessage;
		//购买者留言
		$sellerMsgList = '';

		//订单明细表数据
		if($orderAttribute == 1){
			//单料号订单，转为多料号数组一并处理
			$transactionArray = array($firstTransaction);
		}else if($orderAttribute == 3){
			$transactionArray = $orderInfo['TransactionArray']['Transaction'];
		}
		$childOrderList = array();
		$simpleDetail	= array();
		foreach($transactionArray as $transKey=>$transaction){
			//检查是否多属性刊登
			$onlineSku = $transaction['Item']['SKU'];
			if(isset($transaction['Variation']['SKU'])){
				$onlineSku = $transaction['Variation']['SKU'];
			}
			$skus = explode('#',$onlineSku);
			$sku  = strtoupper($skus[0]);
			//根据url获取图片地址
			$skuUrl = $transaction['Variation']['VariationViewItemURL'];
			$imgUrl = '';
			if($skuUrl){
				$contents = file_get_contents($skuUrl);
				preg_match_all('/<img id="icImg".*src=\"(.*?)\"/',$contents,$imageRes);
				$imgUrl = $imageRes[1][0];
			}
			$otherAttributes = array();
			foreach($transaction['Variation']['VariationSpecifics']['NameValueList'] as $attrs){
				$otherAttributes[$attrs['Name']] = $attrs['Value'];
			}
			$childOrderList[] = array(
				"lotNum"               => $transaction['QuantityPurchased'],  //物品数量
				"productAttributes"    => array(
						"sku"			   => $sku,
						"itemPrice"		   => $transaction['TransactionPrice'],
						"itemId"		   => $transaction['Item']['ItemID'],				//**************新增itemId**************
						"skuUrl"		   => $skuUrl,
						"itemTitle"		   => str_rep($transaction['Item']['Title']),
						"shippingFee" 	   => $transaction['ActualShippingCost'],   			 //************新增运费************
					),
				"memo"					   => $customerMessage,
				"otherAttributes"		   => $otherAttributes,
			);
			
			if($transKey == 0){
				$simpleDetail 		= array(
					"item_name"				=> str_rep($transaction['Item']['Title']),
					"item_url"				=> $skuUrl,
					"item_img"				=> $imgUrl,
					"item_count"			=> $transaction['QuantityPurchased'],
					"item_currency_code"	=> '$',
					"item_total_pay"		=> $orderInfo['AmountPaid'],
				);
				$mainOrder['simple_detail'] = json_encode($simpleDetail);
			}
		}
		
		$detailOrder	= array(
			"source_details"			=> json_encode($orderInfo),
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
		
		$orderArr[] = $order;
	}
	$pushRes	= A("Order")->act_receiveOrders($orderArr);
	return $pushRes;
}