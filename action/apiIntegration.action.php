<?php
/**
 * ApiIntegtationAct
 * 功能：用于公共的ajax处理动作
 * @author wcx
 * v 1.0
 * 2014/06/26
 */
class ApiIntegrationAct extends CheckAct {

	public function __construct(){
		parent::__construct();
	}

	/**
	 * 抓取速卖通订单
	 */

	public function act_getAliexpressOrder($shopId,$status='WAIT_SELLER_SEND_GOODS',$createDateStart=0,$createDateEnd=0,$page=1,$pageSize=50){
		// echo $shopId;exit;
		set_time_limit(0);
		if(empty($shopId)){
			self::$errMsg['10008']	= get_promptmsg('10008','店铺');
			return false;
		}
		if($createDateStart && $createDateStart >= $createDateEnd){
			self::$errMsg['20005']	= get_promptmsg('20005');
			return false;
		}
		$platform           = 2;  //速卖通平台
		$companyId 			= get_usercompanyid();
		$getShopWhereArr	= array("id"=>$shopId);
		if(!empty($companyId)) {  //如果用户界面上操作则要验证身份，否则为系统操作
			$getShopWhereArr['belong_company'] = $companyId;
		}
		$shopInfo = M("Shops")->getData("*",$getShopWhereArr);
		if(empty($shopInfo)){
			self::$errMsg['10007']	= get_promptmsg('10007','店铺');
			return false;
		}

	    $aliexpress = A('AliexpressButt');
	    // print_r($aliexpress->time_shift("20150105085433000-0800"));exit;
	    $aliexpress->setToken($shopInfo[0]['shop_account'],$shopInfo[0]['token']);   
	    $orderList = $aliexpress->findOrderListQuery($status,$createDateStart,$createDateEnd,$page,$pageSize); 
	    $orderArr =  array();
	    foreach ($orderList as $k=>$v){
	    	//订单时间转换
	    	$orderTime = $aliexpress->time_shift($v['detail']['gmtCreate']);
	        $simpleDetail 		= array(
	        	"item_name"				=> $v['v']['productList'][0]['productName'],
	        	"item_url"				=> $v['v']['productList'][0]['productSnapUrl'],
	        	"item_img"				=> $v['v']['productList'][0]['productImgUrl'],
	        	"item_count"			=> count($v['v']['productList']),
	        	"item_currency_code"	=> $v['v']['payAmount']['currency']['symbol'],
	        	"item_total_pay"		=> $v['v']['payAmount']['amount'],
	        );
	        $mainOrder	= array(
	        	"user_id"	 				=> get_userid() ? get_userid() : 0,            //订单所属用户的用户ID
	        	"company_id"				=> $shopInfo[0]['belong_company'],			//所属公司
	        	"user_name"	 				=> $shopInfo[0]['creater'],            //订单所属用户的用户名
	        	"simple_detail"				=> json_encode($simpleDetail), 		//订单中简单详细信息
	            "order_id" 					=> $v['detail']['id'],				// string 订单编号
	            "order_status" 				=> $v['detail']['orderStatus'],			// 订单状态
	            "transport_type"			=> $v['v']['productList'][0]['logisticsType'], //运输方式
	            "source_platform" 			=> $platform,		//订单来源平台
	            "shop_id"					=> $shopId,			//所屬店鋪
	            "source_account" 			=> $v['detail']['sellerOperatorLoginId'],			// 订单来源账号
	            "source" 					=> 1,  				// 订单来源 1代表系统抓取
	            "come_from"					=> $shopInfo[0]['belong_company'],
	            "delivery_from"				=> $shopInfo[0]['belong_company'],		//代表微库
	            "gmt_create"				=> $orderTime[0],
	            "create_time" 				=> time(),            // 订单进入系统时间
	        );
			
			$phoneNumber = '';
			if(!empty($v['detail']['receiptAddress']['phoneCountry'])){
				$phoneNumber .= $v['detail']['receiptAddress']['phoneCountry'].'-';
			}
			if(!empty($v['detail']['receiptAddress']['phoneArea'])){
				$phoneNumber .= $v['detail']['receiptAddress']['phoneArea'].'-';
			}
			if(!empty($v['detail']['receiptAddress']['phoneNumber'])){
				$phoneNumber .= $v['detail']['receiptAddress']['phoneNumber'];
			}else{
				$phoneNumber = '';
			}
			//收货地址
			$receiptAddress = array(
				"zip"			=> $v['detail']["receiptAddress"]["zip"],
				"address1"		=> $v['detail']['receiptAddress']['detailAddress'],
				"address2"		=> $v['detail']['receiptAddress']['address2'],
				"address3"		=> '',
				"country"		=> $v['detail']['receiptAddress']['country'],
				"city"			=> $v['detail']['receiptAddress']['city'],
				"county"		=> $v['detail']['receiptAddress']['phoneArea'],
				"phoneNumber"	=> $phoneNumber,
				"mobileNo"		=> $v['detail']['receiptAddress']['mobileNo'],
				"province"		=> $v['detail']['receiptAddress']['province'],
				"contactPerson"	=> $v['detail']['receiptAddress']['contactPerson'],
			);
			//购买者信息
			$buyerInfo	= array(
				"userName"	=> $v['detail']['buyerInfo']['firstName']." ".$v['detail']['buyerInfo']['lastName'],
				"email"		=> $v['detail']['buyerInfo']['email'],
				"country"	=> $v['detail']['buyerInfo']['country'],
			);
			//订单金额
			$orderAmount = array(
				"amount"		=> $v['detail']['orderAmount']['amount'],
				"actualAmount"	=> $v['detail']['orderAmount']['amount']*C("ORDER_FEE")['platfrom_handle_rate'][$platform],
				"currencyCode"	=> $v['detail']['orderAmount']['currencyCode'],
				"symbol"		=> $v['detail']['orderAmount']['currency']['symbol'],
			);
			//订单留言列表
			$orderMsgList = $v['detail']['orderMsgList'];
			//订单中产品信息
			$childOrderList = array();
			foreach ($v['detail']['childOrderList'] as $kk=>$vv){
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
							"skuUrl"		   => $v['v']['productList'][$kk]['productSnapUrl'],
							"itemTitle"		   => $vv['productName'],
							"shippingFee" 	   => $v['v']['productList'][$kk]['logisticsAmount']['amount'],   			 //************新增运费************
						),
					"memo"					=> $v['v']['productList'][$kk]['memo'],
				);
				foreach($productAttributes['sku'] as $kkk=>$vvv){
					$productSimpleDetails['otherAttributes'][$vvv['pName']] = $vvv['pValue'];
				}
				$childOrderList[] = $productSimpleDetails;
			}

	        //修復skuCode
	        
	        $detailOrder	= array(
	        	"source_details"			=> json_encode($v),
	        	"receiptAddress"			=> json_encode($receiptAddress),
				"buyerInfo"					=> json_encode($buyerInfo),
				"childOrderList"			=> json_encode($childOrderList),
				"orderAmount"				=> json_encode($orderAmount),
				"orderMsgList"				=> $orderMsgList,
				"sellerMsgList"				=> $sellerMsgList,
				"orderDeclarationContent"	=> "",
				"shipping_type"				=> "CNPSS",
	        );
	        $order = array(
	        	"mainOrder"			=> $mainOrder,		//存入订单主表信息
	        	"detailOrder"		=> $detailOrder,	//存入订单详情表信息
	        );
	        
	        $order_arr[] = $order;
	    }
	    $pushRes	= A("Order")->act_receiveOrders($order_arr);
	    return $pushRes;
	}

	/**
	 * 将订单通过接口推送到赛维
	 * wcx
	 */
	
	public function act_pushOrdersToSailvan($orderSysIds){
		C(include WEB_PATH.'conf/valsun_conf.php');
		// $orderSysIds = explode(",", $orderSysIds);
		// $orderSysIds = array_filter($orderSysIds);
		$deliveryFrom = 3; //代表赛维公司
		if(empty($orderSysIds)){
			self::$errMsg['10008']	= get_promptmsg('10008','订单');
			return false;
		}
		$orderSysIdStr = implode(',', $orderSysIds);
		$orders = M("Order")->getAllData("*","id in ({$orderSysIdStr}) and delivery_from = {$deliveryFrom}","id");
		if(empty($orders)){
			self::$errMsg['10007']	= get_promptmsg('10007','订单');
			return false;
		}
		$orderOneId = key($orders);
		$companyId = empty($orders[$orderOneId]['company_id']) ? 0 : $orders[$orderOneId]['company_id'];
		$orderArr =  array();
		$orderRet = array();
		foreach ($orders as $key => $value) {
			M("OrderDetails")->setTablePrefix('_'.date('Y_m',$value["create_time"]));
			$orderDetail = M("OrderDetails")->getData("*","id = {$value['id']}");

	    	$v 	= json_decode($orderDetail[0]['source_details'],true);
	    	if(empty($v)){
	    		$orderRet[$value['id']] = array("10007",get_promptmsg("10007","订单详细"));
	    		continue;
	    	}
	        $receiptAddress = array(
	            "zip"           => $v['detail']['receiptAddress']['zip'],
	            "address1"		=> $v['detail']['receiptAddress']['detailAddress'],    //string 街道地址1
	            "address2"		=> $v['detail']['receiptAddress']['address2'],    //string 街道地址2
	            "address3"		=> "",	 //string 街道地址3 *************新增*****
	            "country"		=> $v['detail']['receiptAddress']['country'],    // string 国家   必填
	            "city" 			=> $v['detail']['receiptAddress']['city'], //string 城市
	            "county" 		=> $v['detail']['receiptAddress']['phoneArea'],  //**************新增区县*************
	            "phoneNumber"	=> empty($v['detail']['receiptAddress']['mobileNo']) ? $v['detail']['receiptAddress']['phoneNumber'] : $v['detail']['receiptAddress']['mobileNo'],   //string 联系电话
	            "province" 		=> $v['detail']['receiptAddress']['province'],    // string 省
	            "contactPerson" => $v['detail']['receiptAddress']['contactPerson']    // string  联系人名称
	        );
	        //客户信息
	        $buyerInfo  = array(
	            "lastName" 	=> $v['detail']['buyerInfo']['lastName'], // string 名称  必填
	            "firstName"	=> $v['detail']['buyerInfo']['firstName'],		// string 姓       必填
	            "email"		=> '',//$v['detail']['buyerInfo']['email'],   // string email
	            "country"	=> $v['detail']['buyerInfo']['country']     // string 国家
	        );
	        // 订单数
	        $orderAmount = array(
	            "amount"			=> $v['detail']['orderAmount']['amount'], // 订单的总价
	            "currencyCode"		=> $v['detail']['orderAmount']['currencyCode'], // 订单币种
	            "symbol"			=> $v['detail']['orderAmount']['currency']['symbol']  // 币种简码
	        );
	        // 订单列表
	        $childOrderList    = array();
	        foreach ($v['detail']['childOrderList'] as $kk=>$vv){
	        	//获取订单的skuCode
	        	if(!empty($vv['skuCode'])){
					$skuCode 		= explode("#",$vv['skuCode']);
			        $vv['skuCode'] 	= $skuCode[0];
	        	}
		        if(empty($vv['skuCode'])){
		        	$skus = M("ProductMapSku")->getData("*",array("platform_product_id"=>$vv['productId'],"sku_belong_company"=>$deliveryFrom));
		        	if(empty($skus[0]['sku'])){
		        		$orderRet[$value['id']] = array("10007",get_promptmsg("10007","SKU"));
		        		continue 2;
		        	}
		        	$vv['skuCode'] = $skus[0]['sku'];
		        }
	            $productAttributes = json_decode($vv['productAttributes'],true);
	            $childOrderList[] = array(
	                "lotNum"               => $vv['productCount'],  //物品数量
	                "productAttributes"    => array(
    	                    "sku"			   => $vv['skuCode'],
    	                    "itemPrice"		   => $vv['productPrice']['amount'],
    	                    "itemId"		   => $vv['productId'],				//**************新增itemId**************
    	                    "skuUrl"		   => '',
    	                    "itemTitle"		   => $vv['productName'],
    	                    "shippingFee" 	   => 0,   			 //************新增运费************
    	                )
	            );
	        }
	        $order = array(
	            "companyName" 				=> "weclu",            // string 公司名
	            "orderId" 					=> $value['id'],				// string 订单在系统的编号
	            "paymentType" 				=> $v['v']['paymentType'],			// string paypal账号
	            "tradeID" 					=> "",		// string 交易流水号
	            "gmtCreate" 				=> empty($value['gmt_create']) ? time() : $value['gmt_create'],			// string GTM时间
	            "receiptAddress" 			=> $receiptAddress,  // array 客户收货地址
	            "buyerInfo" 				=> $buyerInfo,            // array 客户信息
	            "orderMsgList" 				=> implode(',',$v['v']['orderMsgList']),  // string 订单评价列表
	            "sellerMsgList" 			=> "", // string 销售评价列表
	            "transportType" 			=> C('VALSUN_SHIPPINGTYPE')[$v['v']['productList'][0]['logisticsServiceName']],			// string 运输方式别名  详情参考 http://developer.valsun.cn/index.php?mod=developerDoc&act=shippingType
	            "orderAmount" 				=> $orderAmount,		// array 订单价格信息
	            "childOrderList" 			=> $childOrderList, // array 订单详情列表，订单中包含哪些物品及数量价格等等
	            "platform"					=> "aliexpress",			//  *****************接口新增字段**************** 用于标记平台
	            "actualShipping" 			=> "0",   		//*******************新增接口**********   运费
	            "paymentTime" 				=> time(),  //**************接口新增*************** 订单支付时间
	        
	        );
	        
	        $orderArr[] = $order;
	    }
	    log::writeLog("orderArr = ".var_export($orderArr,true),"api/third/valsun","push_order","d");
	    if(!empty($orderArr)){
	    	$CompanySynConf = M("CompanySynConf")->getAllData("*","platform_id=9 and company_id={$companyId}");
	    	if(empty($CompanySynConf)){
	    		self::$errMsg['10007'] = get_promptmsg(10007,"配置");
	    		return false;
	    	}
			A("ValsunButt")->setConfig($CompanySynConf[0]['app_key'],$CompanySynConf[0]['app_token']);  
			$valsunRet = A("ValsunButt")->pushOrders($orderArr);
			$valsunRet = json_decode($valsunRet,true);

			// $valsunRet = array(array("orderId"=>7524,"errcode"=>0,"msg"=>"success","andit"=>array(true,"推送成功！")));
			// 修改订单的状态
			if(is_array($valsunRet)){
				foreach ($valsunRet as $key => $value) {
					if($value['errcode'] == '0'){
						$updateRet = A("Order")->act_updateOrderHandleStatus(array($value['orderId']),3);
						foreach ($updateRet as $k => $v) {
							$orderRet[$k] = $v;
						}
					}else{
						$orderRet[$value['orderId']] = array($value['errcode'],$value['msg']);
						A("Order")->act_updateOrderHandleStatus(array($value['orderId']),2,$value['msg']);
					}
				}
			}
		}
		log::writeLog("valsunRet = ".json_encode($orderRet),"api/third/valsun","push_order","d");
		return $orderRet;
	}

	/**
	 * 将订单通过接口推送到赛维 最新接口
	 * wcx
	 */
	
	public function act_pushOrdersToValsun($orderSysIds){
		C(include WEB_PATH.'conf/valsun_conf.php');
		C(include WEB_PATH.'conf/weclu_conf.php');
		// $orderSysIds = explode(",", $orderSysIds);
		// $orderSysIds = array_filter($orderSysIds);
		$deliveryFrom = 3; //代表赛维公司
		if(empty($orderSysIds)){
			self::$errMsg['10008']	= get_promptmsg('10008','订单');
			return false;
		}
		$orderSysIdStr = implode(',', $orderSysIds);
		$orders = M("Order")->getAllData("*","id in ({$orderSysIdStr}) and delivery_from = {$deliveryFrom}","id");
		if(empty($orders)){
			self::$errMsg['10007']	= get_promptmsg('10007','订单');
			return false;
		}
		$orderOneId = key($orders);
		$companyId = empty($orders[$orderOneId]['company_id']) ? 0 : $orders[$orderOneId]['company_id'];
		$orderArr =  array();
		$orderRet = array();
		foreach ($orders as $key => $value) {
			//5.回收站 14 取消交易 40 未知状态
			if(in_array($value['handle_status'], array(5,14,40))){
				$orderRet[$value['id']] = array("30004",get_promptmsg("30004"));
	    		continue;
			}
			M("OrderDetails")->setTablePrefix('_'.date('Y_m',$value["create_time"]));
			$orderDetail = M("OrderDetails")->getData("*","id = {$value['id']}");

	    	$v 	= $orderDetail[0];
	    	if(empty($v)){
	    		$orderRet[$value['id']] = array("10007",get_promptmsg("10007","订单详细"));
	    		continue;
	    	}

	    	$v['receiptAddress'] = json_decode($v['receiptAddress'],true);
	    	$v['buyerInfo'] 	 = json_decode($v['buyerInfo'],true);
	    	$v['orderAmount'] 	 = json_decode($v['orderAmount'],true);
	    	$v['childOrderList'] = json_decode($v['childOrderList'],true);
	    	$v['orderMsgList']   = json_decode($v['orderMsgList'],true);

	        $receiptAddress = array(
	            "zip"           => $v['receiptAddress']['zip'],
	            "address1"		=> $v['receiptAddress']['address1'],    //string 街道地址1
	            "address2"		=> $v['receiptAddress']['address2'],    //string 街道地址2
	            "address3"		=> $v['receiptAddress']['address3'],	 //string 街道地址3 *************新增*****
	            "country"		=> $v['receiptAddress']['country'],    // string 国家   必填
	            "city" 			=> $v['receiptAddress']['city'], //string 城市
	            "county" 		=> $v['receiptAddress']['phoneArea'],  //**************新增区县*************
	            "phoneNumber"	=> empty($v['receiptAddress']['mobileNo']) ? $v['receiptAddress']['phoneNumber'] : $v['receiptAddress']['mobileNo'],   //string 联系电话
	            "province" 		=> $v['receiptAddress']['province'],    // string 省
	            "contactPerson" => $v['receiptAddress']['contactPerson']    // string  联系人名称
	        );
	        //客户信息
	        $buyerInfo  = array(
	            //"lastName" 	=> $v['buyerInfo']['userName'], // string 名称  必填
	            "firstName"	=> $v['buyerInfo']['userName'],		// string 姓       必填
	            "email"		=> '',//$v['buyerInfo']['email'],   // string email
	            "country"	=> $v['buyerInfo']['country']     // string 国家
	        );
	        // 订单数
	        $orderAmount = array(
	            "amount"			=> $v['orderAmount']['amount'], // 订单的总价
	            "currencyCode"		=> $v['orderAmount']['currencyCode'], // 订单币种
	            "symbol"			=> $v['orderAmount']['symbol']  // 币种简码
	        );
	        // 订单列表
	        $childOrderList    = array();
	        foreach ($v['childOrderList'] as $kk=>$vv){
	        	//判断sku是否存在于weclu中
	        	if(in_array(C("WECLU_SKU"), $vv['productAttributes']['sku'])){
	        		M("Order")->updateData($value['id'],array("delivery_from"=>"1"));
	        		A("Order")->act_pushOrders((string)$value['id']);
	        		continue 2;
	        	}
	        	//获取订单的skuCode
		        if(empty($vv['productAttributes']['sku'])){
		        	$skus = M("ProductMapSku")->getData("*",array("platform_product_id"=>$vv['productAttributes']['itemId'],"sku_belong_company"=>$deliveryFrom));
		        	if(empty($skus[0]['sku'])){
		        		$orderRet[$value['id']] = array("10007",get_promptmsg("10007","SKU"));
		        		continue 2;
		        	}
		        	$vv['productAttributes']['sku'] = $skus[0]['sku'];
		        }
	            $productAttributes = json_decode($vv['productAttributes'],true);
	            $childOrderList[] = array(
	                "lotNum"               => $vv['lotNum'],  //物品数量
	                "productAttributes"    => array(
    	                    "sku"			   => $vv['productAttributes']['sku'],
    	                    "itemPrice"		   => $vv['productAttributes']['itemPrice'],
    	                    "itemId"		   => $vv['productAttributes']['itemId'],				//**************新增itemId**************
    	                    "skuUrl"		   => $vv['productAttributes']['skuUrl'],
    	                    "itemTitle"		   => $vv['productAttributes']['itemTitle'],
    	                    "shippingFee" 	   => $vv['productAttributes']['shippingFee'],   			 //************新增运费************
    	                )
	            );
	        }
	        $order = array(
	            "companyName" 				=> "weclu",            // string 公司名
	            "orderId" 					=> $value['id'],				// string 订单在系统的编号
	            "paymentType" 				=> '',			// string paypal账号
	            "tradeID" 					=> "",		// string 交易流水号
	            "gmtCreate" 				=> empty($value['gmt_create']) ? time() : $value['gmt_create'],			// string GTM时间
	            "receiptAddress" 			=> $receiptAddress,  // array 客户收货地址
	            "buyerInfo" 				=> $buyerInfo,            // array 客户信息
	            "orderMsgList" 				=> implode(',',$v['orderMsgList']),  // string 订单评价列表
	            "sellerMsgList" 			=> "", // string 销售评价列表
	            "transportType" 			=> $v['shipping_type'],			// string 运输方式别名  详情参考 http://developer.valsun.cn/index.php?mod=developerDoc&act=shippingType
	            "orderAmount" 				=> $orderAmount,		// array 订单价格信息
	            "childOrderList" 			=> $childOrderList, // array 订单详情列表，订单中包含哪些物品及数量价格等等
	            "platform"					=> C('VALSUN_DE_PLATFORM')[$value['source_platform']],			//  *****************接口新增字段**************** 用于标记平台
	            "actualShipping" 			=> "0",   		//*******************新增接口**********   运费
	            "paymentTime" 				=> time(),  //**************接口新增*************** 订单支付时间
	        
	        );
	        
	        $orderArr[] = $order;
	    }
	    log::writeLog("orderArr = ".var_export($orderArr,true),"api/third/valsun","push_order","d");
	    if(!empty($orderArr)){
	    	$CompanySynConf = M("CompanySynConf")->getAllData("*","platform_id=9 and company_id=1");
	    	if(empty($CompanySynConf)){
	    		self::$errMsg['10007'] = get_promptmsg(10007,"配置");
	    		return false;
	    	}
			A("ValsunButt")->setConfig($CompanySynConf[0]['app_key'],$CompanySynConf[0]['app_token']);  
			$valsunRet = A("ValsunButt")->pushOrders($orderArr);
			$valsunRet = json_decode($valsunRet,true);

			// $valsunRet = array(array("orderId"=>7524,"errcode"=>0,"msg"=>"success","andit"=>array(true,"推送成功！")));
			// 修改订单的状态
			if(is_array($valsunRet)){
				foreach ($valsunRet as $key => $value) {
					if($value['errcode'] == "0" || $value['errcode'] == "81001"){
						$updateRet = A("Order")->act_updateOrderHandleStatus(array($value['orderId']),3);
						foreach ($updateRet as $k => $v) {
							$orderRet[$k] = $v;
						}
					}else{
						$orderRet[$value['orderId']] = array($value['errcode'],$value['msg']);
						A("Order")->act_updateOrderHandleStatus(array($value['orderId']),2,$value['msg']);
					}
				}
			}
		}
		log::writeLog("valsunRet = ".json_encode($orderRet),"api/third/valsun","push_order","d");
		return $orderRet;
	}

	/**
	 * 获取赛维的产品
	 * wcx
	 */
	public function act_getProductsTemp(){
		if(empty($_POST)){
			return '未获取到信息';
		}
		$products = $_POST;
		$userAddress 	= $_SERVER["REMOTE_ADDR"];
		$data = array(
			"products_data"	=> json_encode($products),
			"add_time"		=> time(),
			"add_user_address"	=> empty($userAddress) ? 'unknown' : $userAddress,
			"provider"			=> 'sailvan'
		);
		$user = M('TmpProducts')->insertData($data);
		if(empty($user)){
			return "receiver failure";
		}
		return "receiver success";
	}
}
