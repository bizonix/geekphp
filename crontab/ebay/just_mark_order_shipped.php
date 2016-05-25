<?php
	//脚本参数检验
	if($argc<2){
		exit("Usage: /usr/bin/php	$argv[0] eBayAccount \n");
	}
	//账号检验
	$__ebayaccount=trim($argv[1]);
	if(!preg_match('#^[\da-zA-Z]+$#i',$__ebayaccount)){
		exit("Invalid ebay account: $__ebayaccount!");
	}
	if(!defined('WEB_PATH')){
		define("WEB_PATH","/data/web/order.valsun.cn/");
	}
	require_once WEB_PATH."crontab/scripts.comm.php";
	require_once WEB_PATH_CONF_SCRIPTS."script.ebay.config.php";
	require_once WEB_PATH_LIB_SDK_EBAY."CompleteSaleAPI.php";
	
	$omAvailableAct = new OmAvailableAct();
	$where = 'WHERE is_delete=0 ';
	$where .= 'AND platformId in(1,5) ';
	$GLOBAL_EBAY_ACCOUNT = $omAvailableAct->act_getTNameList2arrById('om_account', 'id', 'account', $where);
	
	$FLIP_GLOBAL_EBAY_ACCOUNT = array_flip($GLOBAL_EBAY_ACCOUNT);
	//var_dump($FLIP_GLOBAL_EBAY_ACCOUNT); echo "\n"; exit;
	if(!in_array($__ebayaccount,$GLOBAL_EBAY_ACCOUNT)){
		exit("$__ebayaccount is not support now !");
	}
	
	//预先判断ebaytoken文件
	$__token_file = WEB_PATH_CONF_SCRIPTS_KEYS_EBAY.'keys_'.$__ebayaccount.'.php';
	if(!file_exists($__token_file)){
		exit($__token_file." does not exists!!!");
	}
	
	//$global_ebay_carrier=array();
	//$no_express_delivery_arr = CommonModel::getTransCarrierInfo();
	$delivery_arr = CommonModel::getCarrierListById();
	//var_dump($delivery_arr); echo "\n"; exit;
	/*$ebay_csql			= "select value,name from ebay_carrier where ebay_user='$user'";
	$_ebay_carrier		= $dbcon->execute($ebay_csql);
	$_ebay_carrier		= $dbcon->getResultArray($_ebay_carrier);
	foreach($_ebay_carrier as $ec){
		$global_ebay_carrier[$ec['name']]=$ec['value'];
	}
	unset($_ebay_carrier);*/
	
	$account = $FLIP_GLOBAL_EBAY_ACCOUNT[$__ebayaccount];
	
	//取出所有在移动后 就需要被标记发货的 ebay_id
	/*$mark_sql	= "	SELECT	 	ems.id,ems.account,eo.ebay_currency,eo.ebay_ordersn,eo.ebay_orderid,eo.ebay_combine,eo.ebay_countryname,eo.ebay_carrier,eo.ebay_tracknumber
					FROM 		om_mark_shipping ems 
					LEFT JOIN	om_unshipped_order eo 
					ON			ems.omOrderId=eo.id 
					WHERE		ems.account='".$account."' AND ems.status=0";*/
	
	$mark_sql	= "	SELECT	 	ems.omOrderId,ems.account 
					FROM 		om_mark_shipping ems 
					WHERE		ems.account='".$account."' AND ems.status=0";
	$mark_ebayid	= $dbConn->query($mark_sql);
	$mark_ebayid	= $dbConn->fetch_array_all($mark_ebayid);
	//var_dump($mark_ebayid); echo "\n"; exit;
	
	if(empty($mark_ebayid)){
		exit("No order to handel\n");
	}
	
	/*$handle_cnt=count($mark_ebayid);
	
	$mark_order_shipped=array();
	
	if( $handle_cnt>0 ){		
		foreach($mark_ebayid as $me){			
			$mark_order_shipped[]=$me;
		}
	}else{
		exit("No order to handel\n");
	}
	
	$mark_ebayid=null;unset($mark_ebayid);*/
	
	#############类或API 实例化##############
	$api_cs = new CompleteSaleAPI($__ebayaccount);
	
	$time_start=time();
	$mctime = time();
	
	echo "=====[".date('Y-m-d H:i:s',$time_start)."]系统【开始】处理账号【 $__ebayaccount 】订单的只标发货====>\n";
	
	$handle_cnt=count($mark_ebayid);
	$handle_idx=0;
	//var_dump($mark_order_shipped); echo "\n"; exit;
	$tableName = "om_shipped_order";
	foreach($mark_ebayid as $order){
		$omOrderId = $order['omOrderId'];
		$where = " where id = {$omOrderId} and storeId = 1 and is_delete = 0 ";
		$orderList = OrderindexModel::showOrderList($tableName, $where);
		if(!$orderList){
			echo "订单编号 {$omOrderId} 查不到发货表信息\n";
			continue;	
		}
		$orderData = $orderList[$omOrderId]['orderData'];
		$orderExtenData = $orderList[$omOrderId]['orderExtenData'];
		$orderUserInfoData = $orderList[$omOrderId]['orderUserInfoData'];
		$orderDetailList = $orderList[$omOrderId]['orderDetail'];
		$orderId = $orderExtenData['orderId'];
		$transportId = $orderData['transportId'];
		$carrier = $delivery_arr[$transportId];
		
		$tran_datas = array();
		foreach($orderDetailList as $orderDetail){
			$orderDetailData = $orderDetail['orderDetailData'];
			$orderDetailExtenData = $orderDetail['orderDetailExtenData'];
			$itemid = $orderDetailExtenData['itemId'];
			$tid = $orderDetailExtenData['transId'];
			$sku = $orderDetailData['sku'];
			
			$tran_data=array();
			$tran_data['itemid']	=$itemid;
			$tran_data['tid']		=$tid;
			$tran_data['orderid']	=$orderId;
			$tran_datas[] = array('sku'=>$sku,'tran'=>$tran_data);
		}
		
		$handle_idx++;
		if (empty($orderId)){
			echo "线下导入订单不需要标记\n";
			$mark_res = true;
		}else if (!in_array($orderUserInfoData['countryName'], array('United States','US','Puerto Rico')) && $orderUserInfoData['currency']=='USD'){
			echo "在美国站点购买运输到非美国上传订单编号:".$omOrderId."\n";
			$mark_res = MarkShippingModel::just_mark_order_shipped($tran_datas);
			if (in_array($carrier, array('香港小包平邮', '中国邮政平邮'))){
				echo "平邮在美国站点{$carrier}购买运输到非美国币种为美元,上传订单编号:".$omOrderId."\n";
				$mark_res = MarkShippingModel::just_mark_order_shipped($tran_datas);
			}else{
				echo "挂号在美国站点{$carrier}购买运输到非美国币种为美元,上传订单编号:".$omOrderId."\n";
				$mark_res = MarkShippingModel::just_mark_order_shipped($tran_datas);
			}
			/**/
		}else if(in_array($orderUserInfoData['countryName'], array('United States','US','Puerto Rico'))){
			//普通账号
			/*if(in_array($__ebayaccount, array('enicer','charmday88','wellchange','voguebase55','bestinthebox','easebon','365digital','befdi','befdimall','betterdeals255','dealinthebox','digitalzone88','doeon','enjoy24hours','itshotsale77','keyhere','niceforu365','niceinthebox','starangle88','sunwebhome','sunwebzone'))){
				if (in_array($order['ebay_carrier'], array('香港小包平邮', '中国邮政平邮'))){
					echo "平邮运输到美国{$order['ebay_carrier']}上传订单编号:".$order['ebay_id']."\n";
					$mark_res = update_order_shippingdetail_to_ebay($order['ebay_orderid'],$order['ebay_ordersn'],$order['ebay_id'],'other');
				}else{
					echo "挂号运输到美国{$order['ebay_carrier']}上传订单编号:".$order['ebay_id']."\n";
					$mark_res = update_ebayid_shippingdetail_to_ebay($order['ebay_orderid'],$order['ebay_ordersn'],$order['ebay_id'],'other');
				}
			}*/
			//EUB账号
			/*if(in_array($__ebayaccount, array('betterdeals255','keyhere','befdimall','doeon','charmday88','digitalzone88','enjoy24hours','sunwebhome','enicer','befashion','niceforu365','dealinthebox','sunwebzone','wellchange','360beauty','365digital','itshotsale77','befdi','elerose88','cafase88','niceinthebox','bestinthebox','starangle88','zealdora','choiceroad','voguebase55','dresslink','happydeal88','easytrade2099','easyshopping678','work4best','eshop2098','fiveseason88','easebon','estore2099','mysoulfor','newcandy789','estore456','eseasky68','infourseas','unicecho','vobeau','swzeagoo','easyshopping095','beromantic520','easydealhere','freemart21cn'))){
				echo "EUB运输到美国{$order['ebay_carrier']}上传订单编号:".$order['ebay_id']."\n";
				//$mark_res = update_order_shippingdetail_to_ebay($order['ebay_orderid'],$order['ebay_ordersn'],$order['ebay_id'],'other');
				$mark_res = just_mark_order_shipped($order['ebay_orderid'],$order['ebay_ordersn']);
			}else*/
			if(in_array($__ebayaccount, array('estore456'))){
				echo "EUB运输到美国或者波多黎各{$carrier}上传订单编号:".$omOrderId."\n";
				//$mark_res = update_order_shippingdetail_to_ebay($order['ebay_orderid'],$order['ebay_ordersn'],$order['ebay_id'],'other');
				$mark_res = MarkShippingModel::just_mark_order_shipped($tran_datas);
			}else{
				echo "运输到美国上传订单编号:".$omOrderId."\n";
				$mark_res = MarkShippingModel::just_mark_order_shipped($tran_datas);
			}
		}else{
			echo "标记eBay订单号:".$omOrderId."\n";
			$mark_res = MarkShippingModel::just_mark_order_shipped($tran_datas);
		}
		if($mark_res === true){//标记成功 就删除队列
			$returnStatus0 = array('ShippedTime'=>time());
			if(MarkShippingModel::pop_mark_shipping_order($omOrderId, $account)){
				OrderindexModel::updateOrder($tableName,$returnStatus0,$where);
			}
		} 
		echo "[".date('Y-m-d H:i:s')."]-----------$handle_idx/$handle_cnt--------done\n";
	}
	$time_end=time();
	echo "\t\t\t[耗时:".ceil(($time_end-$time_start)/60)."分钟]\n";
	echo "<=====[".date('Y-m-d H:i:s',$time_end)."]系统【结束】处理账号【 $__ebayaccount 】订单的只标发货====\n";
?>
