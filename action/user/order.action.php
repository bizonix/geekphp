<?php
/**
 * 类名：OrderAct
 * 功能：订单管理
 * 版本：v1.0
 * 作者：wcx
 * 时间：2014/12/16
 * errCode：
 */
class OrderAct extends CheckAct {
	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * 功能：对接分销商订单到分销系统
	 * by wcx
	 */
	public function act_receiveOrders($orderdatas='', $email = 'system'){
		// print_r($orderdatas);exit;
		set_time_limit(0);
		if(empty($orderdatas)){
		    $orderdatas    =   $_REQUEST['orderdatas'];
 	 	   // $orderdatas    = '[{"buyerInfo":{"lastName":"","email":"","firstName":"","country":""},"receiptAddress":{"zip":"43062","phoneNumber":"6144050413","province":"Ohio","address1":"11773+cable+rd+sw","address2":"","contactPerson":"Nacole+Carey","city":"Pataskala","country":"US"},"childOrderList":[{"lotNum":"1","productAttributes":{"itemPrice":"2.6","sku":"SV003307_B","itemTitle":"Women\'s Sexy Leopard Lingerie Sleepwear Nightwear Clubwear Babydoll Mini Dress  SV003307 One Size Vestidos","skuUrl":""}}],"gmtCreate":"1414495436","paymentType":"","tradeID":"544f08f552d7880f93510b6f","orderMsgList":"","sellerMsgList":"","companyName":"tomtop","transportType":"CNPTE","orderId":"105s'.time().'11"}]';
			$orderdatas    =   json_decode($orderdatas,true);
			//var_dump($orderdatas);exit;
			$email = $_REQUEST['email'];
		}
	    $source    =   1;
		$retData	=	array();
		if(empty($orderdatas)){
			self::$errMsg[10007]   =   get_promptmsg(10007,"订单");
			return false;
		}
	    foreach ($orderdatas as $k=>$v){
	    	$mainData  		=   $v['mainOrder'];
	        $detailOrder    =   $v['detailOrder'];
	        if(empty($mainData) || empty($detailOrder)) continue;
	        $exist =   M("Order")->getData("id,create_time",array("order_id"=>$mainData['order_id'],"company_id"=>$mainData['company_id'],"source_platform"=>$mainData['source_platform'],"is_delete"=>0));

	        if(empty($exist[0]['id'])){//判断订单是否已经存在 ，或者取消，删除
    	        //订单插入
	            M("OrderDetails")->setTablePrefix('_'.date('Y_m',$mainData["create_time"]));
	            M("Common")->begin();
	            $ret1      			 = M("Order")->insertData($mainData);
	            if(!$ret1){
	            	log::write("order insert data ".M("Order")->getErrorMsg());
	            }
    	        $detailOrder['id']   = M("Order")->getLastInsertId();
    	        $ret2      =   M("OrderDetails")->insertData($detailOrder);
    	        if(!$ret2){
	            	log::write("OrderDetails insert data ".M("OrderDetails")->getErrorMsg());
	            }
	        }else{
	            //更新
	            $time =   trim($exist[0]['create_time']);
	            $id   =   trim($exist[0]['id']);
	            M("OrderDetails")->setTablePrefix('_'.date('Y_m',$time));
	            M("Common")->begin();
	            $updateData['update_time']	= time();
	            $updateData['update_user']	= 'system';
	            $updateData['order_status']	= $mainData['order_status'];
	            $ret1 =   M("Order")->updateData($id,$updateData);
	            //$ret2 =   M("OrderDetails") ->updateData($id,$detailOrder);
	        }
	        if($ret1&&$ret2){
				M("Common")->commit();
				$retData[$mainData['order_id']]	=	array('200',get_promptmsg(200,"插入订单"));
	        }else{
	        	M("Common")->rollback();
	            $lastMsg	=	$this->act_getLastErrorMsg();
				$retData[$mainData['order_id']]	=	empty($lastMsg)?array('123123','插入订单失败！'):$lastMsg;
	        }
	    }
	    return $retData;
	}
	/**
	 * 获取订单
	 * @param int $companyId
	 * by wcx
	 */
	public function act_getOrderList($companyId=0){
		if(!$companyId) $companyId = get_usercompanyid();
		if(empty($companyId)){
			self::$errMsg['10008']	= get_promptmsg(10008,"公司ID");
			return false;
		}
		$where 	= "company_id = '{$companyId}'";
		$count 	= M("Order")->getDataCount($where);
		$p 		= new Page ($count,10);
		$orders =  M("Order")->getData("*",$where,"order by id desc",$this->page,$this->perpage);
		$page 	= $p->fpage();
		//获取所有涉及到的公司
		$relationCompany = M("CompanyRelation")->getAllData("*","belong_company = $companyId or to_company = $companyId");
		$deliveryFrom  	= array();
		$comeFrom 		= array();
		foreach ($relationCompany as $value) {
			if(!empty($value['belong_company'])){
				$comeFrom[$value['belong_company']] 			= $value['belong_company'];
			}
			if(!empty($value['to_company'])){
				$deliveryFrom[$value['to_company']]	= $value['to_company'];
			}
		}
		$companyIds = array_merge($deliveryFrom,$comeFrom);
		$companyIdsStr = implode(",", $companyIds);
		if($companyIdsStr){
			$companys	= M("Company")->getAllData('id,cn_name',"id IN ({$companyIdsStr})",'id');
		}else{
			$companys = array();
		}
		//获取店铺
		$shops		= M("Shops")->getAllData("id,shop_account","belong_company={$companyId}","id");
		//获取平台
		$platforms 	= M("Platform")->getAllData("id,platform_cn_name","1","id");
		//状态下订单统计
		//获取未标记发货
		$unsellerShipCount = M("Order")->getDataCount(array("company_id"=>$companyId,"seller_ship_status"=>0));
		$orderStatics = MC("SELECT COUNT(*) AS nums,handle_status FROM we_order WHERE company_id = {$companyId} GROUP BY handle_status");
		$endOrderStatics = array();
		foreach ($orderStatics as $key => $value) {
			$endOrderStatics[$value['handle_status']] = $value['nums'];
		}
		$retList = array(
			"orderList"		=> $orders,
			"count"			=> $count,
			"page"			=> $page,
			"companys"		=> $companys,
			"deliveryFrom"	=> $deliveryFrom,
			"comeFrom"		=> $comeFrom,
			"shops"			=> $shops,
			"platforms"		=> $platforms,
			"orderStatics"	=> $endOrderStatics,
			"unsellerShipCount"	=> $unsellerShipCount,
		);
		return $retList;
	}

	/**
	 * 根据条件查询订单
	 * @param int $companyId
	 * by wcx
	 */
	public function act_queryOrderList($shopId=0,$comeFrom=0,$deliveryFrom=0,$orderStatus='',$handleStatus='',$orderSysId=0,$orderId=0,$platform=0,$sellerShipStatus='#'){
		$companyId = get_usercompanyid();
		if(empty($companyId)){
			self::$errMsg['10008']	= get_promptmsg(10008,"公司ID");
			return false;
		}
		$where['company_id'] = $companyId;
		if(!empty($shopId)) $where['shop_id'] = $shopId;
		if(!empty($comeFrom)) $where['come_from'] = $comeFrom;
		if(!empty($deliveryFrom)) $where['delivery_from'] = $deliveryFrom;
		if(!empty($orderStatus)) $where['order_status'] = $orderStatus;
		if(!empty($handleStatus)) $where['handle_status'] = $handleStatus;
		if(!empty($orderSysId)) $where['id'] = $orderSysId;
		if(!empty($orderId)) $where['order_id'] = $orderId;
		if(!empty($platform)) $where['source_platform'] = $platform;
		if($sellerShipStatus !== null && $sellerShipStatus !== '') $where['seller_ship_status'] = $sellerShipStatus;
		$count 	= M("Order")->getDataCount($where);
		$p 		= new Page ($count,10);
		// echo $this->page;exit;
		$orders =  M("Order")->getData("*",$where,"order by id desc",$this->page,$this->perpage);
		$page 	= $p->fpage();
		//获取所有涉及到的公司
		$relationCompany = M("CompanyRelation")->getAllData("*","belong_company = $companyId or to_company = $companyId");
		$deliveryFrom  	= array();
		$comeFrom 		= array();
		$companyIds 	= array();
		foreach ($relationCompany as $value) {
			if(!empty($value['belong_company'])){
				$comeFrom[$value['belong_company']] 			= $value['belong_company'];
			}
			if(!empty($value['to_company'])){
				$deliveryFrom[$value['to_company']] 			= $value['to_company'];
			}
		}
		if(!empty($deliveryFrom)){
			foreach ($deliveryFrom as $k => $v) {
				if(!empty($v)){
					@$companyIds[$k] = $v;
				}
			}
		}
		if(!empty($comeFrom)){
			foreach ($comeFrom as $k => $v) {
				if(!empty($v)){
					@$companyIds[$k] = $v;
				}
			}
		}
		if($companyIds){
			$companyIdsStr = implode(",", $companyIds);
			$companys	= M("Company")->getAllData('id,cn_name',"id IN ({$companyIdsStr})",'id');
		}else{
			$companys = array();
		}
		//获取平台
		$platforms 	= M("Platform")->getAllData("id,platform_cn_name","1","id");
		//获取店铺
		$shops		= M("Shops")->getAllData("id,shop_account","belong_company={$companyId}","id");
		//获取未标记发货
		$unsellerShipCount = M("Order")->getDataCount(array("company_id"=>$companyId,"seller_ship_status"=>0));
		//状态下订单统计
		$orderStatics = MC("SELECT COUNT(*) AS nums,handle_status FROM we_order WHERE company_id = {$companyId} GROUP BY handle_status");
		$endOrderStatics = array();
		foreach ($orderStatics as $key => $value) {
			$endOrderStatics[$value['handle_status']] = $value['nums'];
		}
		$retList = array(
			"orderList"		=> $orders,
			"count"			=> $count,
			"page"			=> $page,
			"companys"		=> $companys,
			"deliveryFrom"	=> $deliveryFrom,
			"comeFrom"		=> $comeFrom,
			"shops"			=> $shops,
			"orderStatics"	=> $endOrderStatics,
			"platforms"		=> $platforms,
			"unsellerShipCount" => $unsellerShipCount,
		);
		return $retList;
	}

	/**
	 * 獲取訂單詳細信息
	 * @param  [type] $fileName [description]
	 * @param  [type] $content  [description]
	 * @return [type]           [description]
	 */
	public function act_getOrderDetail($fileName, $content) {
	    $handle = fopen(WEB_PATH.'html/files/'.$fileName, 'a+');
	    fwrite($handle, "\n".$content);
	    fclose($handle);
	}

	/**
	 * 修改订单的处理状态
	 * add wcx
	 * $orderSysIdArr  格式 array("1231233","423423");
	 * $handleStatus  1|2|3|4|5
	 */
	
	public function act_updateOrderHandleStatus($orderSysIdArr,$handleStatus,$note=''){
		if(empty($orderSysIdArr)){
			self::$errMsg['10008'] = get_promptmsg('10008','订单号');
			return false;
		}
		$retData = array();
		foreach ($orderSysIdArr as $value) {
			if(empty($value)){
				$retData[$value] = array('10008',get_promptmsg('10008','订单号'));
				continue;
			}
			$data = array(
				"handle_status"	=> $handleStatus,
				"update_user" 	=> get_username(),
				'delivery_time'	=> time(),
				"update_time"	=> time(),
			);
			if(!empty($note)){
				$data['note']	= $note;
			}
			$whereData = array("id"=>$value);
			$companyId = get_usercompanyid();
			if($companyId){
				$whereData['company_id'] = $companyId;
			}
			$updateRet = M("Order")->updateDataWhere($data,$whereData);
			if(!empty($updateRet)){
				//添加事件
				$orders = M("Order")->getSingleData("id,order_id,company_id,source_platform,come_from,delivery_from,handle_status,note",$whereData);
				A("Event")->registerEvent("Order","handleStatus",$orders,"Event:synOrderStatus");
				$retData[$value] = array('200',get_promptmsg('200'));
			}else{
				$retData[$value] = array('10001',get_promptmsg('10001'));
			}
		}
		return $retData;
	}

	/**
	 * 修改订单的状态
	 * add wcx
	 * $ordersArr  格式 array(array("orderId","status"));
	 */
	public function act_updateOrderStatus($ordersArr){
		if(empty($ordersArr)){
			self::$errMsg['10008'] = get_promptmsg('10008','订单');
			return false;
		}
		foreach ($ordersArr as $value) {
			if(empty($value) || count($value) != 2){
				if(empty($ordersArr)){
					self::$errMsg['10008'] = get_promptmsg('10008','订单状态');
					return false;
				}
			}
			$data = array(
				"orderStatus"	=> $handleStatus,
				"update_user" 	=> get_username(),
				"update_time"	=> time()
			);
			$whereData = array("order_id"=>$value[0]);
			$companyId = get_usercompanyid();
			if($companyId){
				$whereData['company_id'] = $companyId;
			}
			$updateRet = M("Order")->updateDataWhere($data,$whereData);
			if(!empty($updateRet)){
				$retData[$value] = array('200',get_promptmsg('200'));
			}else{
				$retData[$value] = array('10001',get_promptmsg('10001'));
			}
		}
		return $retData;
	}
	/**
	 * 功能：订单标记发货
	 * wcx
	 */
	public function act_sellerShippment($platformId,$orderId,$trackingNumber,$transportType,$description,$sendType,$transportUrl){
		if(empty($platformId)) {
			self::$errMsg[10007] = get_promptmsg(10007,"平台");
			return false;
		}
		if(empty($orderId)) {
			self::$errMsg[10007] = get_promptmsg(10007,"订单号");
			return false;
		}
		if(empty($trackingNumber)){
			self::$errMsg[10007] = get_promptmsg(10007,"跟踪号");
			return false;
		}
		if(empty($transportType)){
			self::$errMsg[10007] = get_promptmsg(10007,"运输方式");
			return false;
		}
		if(empty($sendType)){
			self::$errMsg[10007] = get_promptmsg(10007,"发货类型");
			return false;
		}
		if($sendType == "other" && empty($transportUrl)){
			self::$errMsg[10007] = get_promptmsg(10007,"第三方网址");
			return false;
		}
		$prefix = C("DB_PREFIX");
		//echo "select shops.shop_account,shops.token from {$prefix}order as order left join {$prefix}shops as shops on order.shop_id=shops.id where order.order_id='{$orderId}' and source_platform={$platformId}";exit;
		$shopConfig = MC("select orders.id,shops.shop_account,shops.token from {$prefix}order as orders left join {$prefix}shops as shops on orders.shop_id=shops.id where orders.order_id='{$orderId}' and source_platform={$platformId}");
		if($platformId == 2){
			//速卖通标记发货
			A("AliexpressButt")->setToken($shopConfig[0]["shop_account"],$shopConfig[0]["token"]);
			$res = A("AliexpressButt")->sellerShipment($transportType, $trackingNumber, $sendType, $orderId, $description,$transportUrl);
			$errMsg = A("Order")->act_getLastErrorMsg();
			if($res || (isset($errMsg[0]) && $errMsg[0] == "30001")){
				$updateRes = M("Order")->updateData($shopConfig[0]['id'],array("tracking_number" => $trackingNumber,"order_status" => "WAIT_BUYER_ACCEPT_GOODS","seller_ship_status" => ($sendType == "all") ? 1 : 2,"seller_ship_time"=>time()));
			}
		}elseif($platformId == 4){
			//wish标记发货
			A("WishButt")->setConfig($shopConfig[0]['shop_account'] , $shopConfig[0]['token']);
			$res = A("WishButt")->fulFillOrders($orderId, $transportType, $trackingNumber);
			$errMsg = A("Order")->act_getLastErrorMsg();
			if($res || (isset($errMsg[0]) && $errMsg[0] == "30001")){
				M("Order")->updateData($shopConfig[0]['id'],array("tracking_number" => $trackingNumber,"order_status" => "SHIPPED","seller_ship_status" => 1,"seller_ship_time"=>time()));
			}
		}
		return $res;

	}

	/**
	 * 功能：删除订单
	 * wcx
	 */
	public function act_deleteOrders($ordersId){
		$ordersId = explode(",", $ordersId);
		if(empty($ordersId[0])){
			self::$errMsg['10008'] = get_promptmsg('10008','订单号');
			return false;
		}
		$retArr = array();
		foreach ($ordersId as $key => $value) {
			$order = M('Order')->getSingleData('handle_status',"id = {$value}");
			//已发货订单不允许删除
			if(!empty($order) && !in_array($order['handle_status'], array('12'))){
				$updateData = M("Order")->deleteData($value);
				if($updateData){
					$retArr[$value]	= array("200","删除成功！");
				}
			}else{
				$retArr[$value]	= array("10001","该订单不允许删除！");
			}
		}
		return $retArr;
	}

	/**
	 * 功能：推送订单发货
	 * wcx
	 */
	public function act_pushOrders($orderSysIds){
		$orderSysIds = explode(",", $orderSysIds);
		$orderSysIds = array_filter($orderSysIds);
		$orderSysIdStr = implode(",", $orderSysIds);
		$orderDatas = M("Order")->getData("*","id in ({$orderSysIdStr})");
		$retdata		 = array();

		$sailvanOrderArr = array();
		foreach ($orderDatas as $k => $v) {
			if($v["company_id"] == "3" && $v["delivery_from"] == "3"){	//代表赛维公司
				$sailvanOrderArr[]	= $v['id'];
			}else{
				break;
			}
		}
		//将订单推向赛维
		if(count($sailvanOrderArr)){
			$retdata = A("ApiIntegration")->act_pushOrdersToValsun($sailvanOrderArr);
		}else{
			//将订单处理推送发货
			if(count($orderDatas)){
			    $autoSend = array();
				foreach ($orderDatas as $v) {
					if($v["company_id"] == $v["delivery_from"]){
						$res = M("Order")->updateData($v['id'],array("handle_status"=>3,"delivery_time"=>time()));
					}else{
						$mainData = $v;
						//删除敏感信息
						unset($mainData["id"]);
						$mainData['company_id'] = $mainData['delivery_from'];
						$mainData['come_from'] = $v["company_id"];
						$mainData['handle_status'] = '1';
						$mainData['shop_id'] = '0';
						$mainData['user_id'] = '0';
						$mainData['user_name'] = 'unkown';

						M("OrderDetails")->setTablePrefix('_'.date('Y_m',$mainData["create_time"]));
						$detailOrder = M("OrderDetails")->getSingleData("*","id = {$v['id']}");
						//拦截物流运送不到国家
						$receiptAddress = json_decode($detailOrder['receiptAddress'],true);
						if(in_array($receiptAddress['country'], array('AM'))){
							$retdata[$v['id']] = array("90002","运送方式运送不到该国家");
							continue;
						}
						M("Common")->begin();
						$exist = M("Order")->getData("id,create_time",array("order_id"=>$mainData['order_id'],"company_id"=>$mainData['company_id'],"source_platform"=>$mainData['source_platform'],"is_delete"=>0));
				        if(empty($exist[0]['id'])){//判断订单是否已经存在 ，或者取消，删除
			    	        //订单插入
			    	        $mainData['new_order_sys_id'] = $v['id'];
				            $ret1 	= M("Order")->insertData($mainData);
				            if(!$ret1){
				            	M("Common")->rollback();
				            	log::write("order push data ".M("Order")->getErrorMsg());
				            }
			    	        $detailOrder['id']   =   M("Order")->getLastInsertId();
			    	        $ret2 = M("OrderDetails")->insertData($detailOrder);
			    	        if(!$ret2){
			    	        	M("Common")->rollback();
				            	log::write("OrderDetails push data ".M("OrderDetails")->getErrorMsg());
				            }else{
			    	        	//添加关联
			    	        	$whereData = array(
			    	        		"belong_company" 	=> $v["company_id"],
			    	        		"to_company"		=> $v["delivery_from"],
			    	        	);
			    	        	$relationData = $whereData;
			    	        	$relationData["add_time"] = time();
			    	        	M("CompanyRelation")->replaceDataWhere($relationData,$whereData);
				            }
				        }else{
				        	//修改
				        	$mainData['new_order_sys_id'] = $v['id'];
				        	$ret1 = M("Order")->updateData($exist[0]['id'],$mainData);
				        	if(!$ret1){
				            	M("Common")->rollback();
				            	log::write("order push update data ".M("Order")->getErrorMsg());
				            }
			    	        unset($detailOrder['id']);
			    	        $ret2	= M("OrderDetails")->updateData($exist[0]['id'],$detailOrder);
			    	        if(!$ret2){
			    	        	M("Common")->rollback();
				            	log::write("OrderDetails push update data ".M("OrderDetails")->getErrorMsg());
				            }else{
				            	//添加关联
			    	        	$whereData = array(
			    	        		"belong_company" 	=> $v["company_id"],
			    	        		"to_company"		=> $v["delivery_from"],
			    	        	);
			    	        	$relationData = $whereData;
			    	        	$relationData["add_time"] = time();
			    	        	M("CompanyRelation")->replaceDataWhere($relationData,$whereData);
				            }
				            $detailOrder['id'] = $exist[0]['id'];
				        }
				        if($ret1 && $ret2){
							$res = M("Order")->updateData($v['id'],array("handle_status"=>3,"delivery_time"=>time(),'new_order_sys_id'=>$detailOrder['id']));
							if(!$res){
								M("Common")->rollback();
								continue;
							}
							M("Common")->commit();
							//赛维订单自动纳入推送队列
							if($mainData['delivery_from'] == 3){
							    $autoSend[] = $detailOrder['id'];
							}
				        }
					}
					
					if($res){
						$retdata[$v['id']] = array(0,"success");
					}else{
						$retdata[$v['id']] = array(90001,"订单推送失败");
					}
				}
				//注册赛维订单自动推送事件
				if(!empty($autoSend)){
				    A("Event")->registerEvent("Order","autoPush",$autoSend,"ApiIntegration:act_pushOrdersToValsun");
				}
			}
		}

		return $retdata;
	}

	/**
	 * 功能：合并订单
	 * wcx
	 */
	public function act_mergeOrders($orderSysIds){
		$orderSysIds 	= explode(",", $orderSysIds);
		$orderSysIds 	= array_filter($orderSysIds);
		$orderSysIdStr 	= implode(",", $orderSysIds);
		$orderDatas 	= M("Order")->getData("*","id in ({$orderSysIdStr})");
		if(empty($orderDatas)){
			self::$errMsg[10007]	= get_promptmsg(10007,"订单");
			return false;
		}
		$OrderArr 		= array();
		$orderIds 		= array();
		$newOrderArr 	= array();
		$retdata		= array();
		$childOrderList = array();
		$orderAmount	= array();
		$simple_detail	= array();

		foreach ($orderDatas as $k => $v) {
			if(!in_array($v['handle_status'], array(1))){
				$retdata[$v['id']] = array("30005",get_promptmsg(30005));
				return false;
			}
			M("OrderDetails")->setTablePrefix('_'.date('Y_m',$v["create_time"]));
			$details 		= M("OrderDetails")->getData("*",array("id"=>$v['id']));
			$orderIds[] 	= $v['id'];
			if($k == 0){
				$newOrderArr['mainOrder']					= $v;
				$newOrderArr["detailOrder"]					= $details[0];
				$newOrderArr["mainOrder"]['order_id']		= 'MG-'.time().rand(1,1000);
				$newOrderArr["mainOrder"]['handle_status']	= '1';
				$childOrderList								= json_decode($details[0]['childOrderList'],true);
				$orderAmount								= json_decode($details[0]['orderAmount'],true);
				$simple_detail								= json_decode($v['simple_detail'],true);
			}else{
				$detailChildOrderList		= json_decode($details[0]['childOrderList'],true);
				if(!empty($childOrderList) && !empty($detailChildOrderList)){
					$childOrderList = array_merge($childOrderList,$detailChildOrderList);
				}
				foreach ($detailChildOrderList as $skuList) {
					$tmpOrderAmount = intval($skuList['lotNum']) * ($skuList['productAttributes']['itemPrice']+$skuList['productAttributes']['shippingFee']);
					$orderAmount['amount'] += $tmpOrderAmount;
					$orderAmount['actualAmount'] = $tmpOrderAmount*(1 - C('ORDER_FEE')['platfrom_handle_rate'][$v['source_platform']]);
					$simple_detail['item_total_pay'] = $orderAmount['amount'];
					$simple_detail['item_count']	 += $skuList['lotNum'];
				}
			}
		}
		$newOrderArr["mainOrder"]['merge_orders']			= implode(",", $orderIds);
		$newOrderArr['mainOrder']['simple_detail']			= json_encode($simple_detail);
		$newOrderArr['detailOrder']['childOrderList']		= json_encode($childOrderList);
		$newOrderArr['detailOrder']['orderAmount']			= json_encode($orderAmount);
		$newOrderArr['detailOrder']['create_time']			= time();
		unset($newOrderArr["mainOrder"]['id']);
		$order_arr[] = $newOrderArr;
		$pushRes	 = $this->act_receiveOrders($order_arr);
		if($pushRes[$newOrderArr["mainOrder"]['order_id']][0] == '200'){
			foreach ($orderDatas as $k => $v) {
				$updateData = array(
					"update_time"	=> time(),
					"update_user"	=> get_username(),
					"merge_orders"	=> $newOrderArr["mainOrder"]['order_id'],
					"handle_status"	=> 5,
				);
				$res = M("Order")->updateData($v['id'],$updateData);
			}
		}
		
		return $pushRes;
	}
	
	/**
	 * 同步订单的信息
	 */
	public function synOrderSomeInfo($ordersInfo){
	    if(empty($ordersInfo)){
	        self::$errMsg['10008'] = get_promptmsg('10008','订单');
	        return false;
	    }
	    $retData = array();
	    $hasSend = array();  //收集已发货订单
	    foreach ($ordersInfo as $order) {
	        if(empty($order['id'])){
	            continue;
	        }
	        $data = array("update_time"	=> time());
	        $whereData = array("id"=>$order['id']);
	        if(isset($order['handle_status']) && !empty($order['handle_status'])){
	            $data['handle_status'] = $order['handle_status'];
	            if($order['handle_status'] == 12) {
	            	$data['delivery_time'] = time();
	            }
	        }
	        if(isset($order['update_user']) && !empty($order['update_user'])){
	            $data['update_user'] = $order['update_user'];
	        }
	        if(isset($order['note']) && !empty($order['note'])){
	            $data['note'] = $order['note'];
	        }
	        if(isset($order['tracking_number']) && !empty($order['tracking_number'])){
	            $data['tracking_number'] = $order['tracking_number'];
	        }
	        if(isset($order['simple_detail']) && !empty($order['simple_detail'])){
	            $data['simple_detail'] = $order['simple_detail'];
	        }
	        
	        $updateRet = M("Order")->updateDataWhere($data,$whereData);
	        if(!empty($updateRet)){
	        	$orderInfo = M("Order")->getSingleData("id,order_id,company_id,source_platform,come_from,delivery_from,handle_status,note,delivery_time,tracking_number,simple_detail,new_order_sys_id",$whereData);
	            //收集已发货订单
	            if($orderInfo['handle_status'] == "12"){
	                $hasSend[] = $order['id'];
	            }
	            //添加事件
	            A("Event")->registerEvent("Order","handleStatus",$orderInfo,"Event:synOrderStatus");
	            $retData[$order['id']] = array('200',get_promptmsg('200'));
	        }else{
	            $retData[$order['id']] = array('10001',get_promptmsg('10001'));
	        }
	        //收集已发货订单，用于统计费用
	        if(!empty($hasSend)){
	            A('Statistics')->importStatics($hasSend);
	        }
	    }
	    return $retData;
	}

}
