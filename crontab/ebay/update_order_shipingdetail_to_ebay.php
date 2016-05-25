<?php
	//脚本参数检验
	if($argc!=2){
		exit("Usage: /usr/bin/php	$argv[0] eBayAccount\n");
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
	
	$carrierList = array();
	$carrierList['香港小包平邮'] = 'HKPost';
	$carrierList['香港小包挂号'] = 'HKpost';
	$carrierList['中国邮政平邮'] = 'ChinaPost';
	$carrierList['中国邮政挂号'] = 'ChinaPost';
	$carrierList['EUB'] = 'ChinaPost';
	$carrierList['UPS'] = 'UPS';
	$carrierList['DHL'] = 'DHL';
	$carrierList['FedEx'] = 'FedEx';
	$carrierList['USPS'] = 'USPS';
	$carrierList['UPS Ground'] = 'UPS Ground';
	$carrierList['飞腾DHL'] = '飞腾DHL';
	$carrierList['UPS美国专线'] = 'UPS_US';
	$carrierList['SurePost'] = 'SurePost';
	
	//预先判断ebaytoken文件
	$__token_file = WEB_PATH_CONF_SCRIPTS_KEYS_EBAY.'keys_'.$__ebayaccount.'.php';
	if(!file_exists($__token_file)){
		exit($__token_file." does not exists!!!");
	}
	$account = $FLIP_GLOBAL_EBAY_ACCOUNT[$__ebayaccount];
	$delivery_arr = CommonModel::getCarrierListById();
	
	$nowtime	= time();
	$mctime		= $nowtime;
	$start		= strtotime(date('Y-m-d',$nowtime-(10*3600*24)).' 00:00:00');
	$end		= strtotime(date('Y-m-d',$nowtime).' 23:59:59');
	
	$tableName = "om_shipped_order";
	/*$order_sql	= "	select 	a.id,a.transportId,a.combineOrder,c.tracknumber
					from 	om_unshipped_order as a
					left join om_unshipped_order_warehouse as b
					on		a.id = b.omOrderId
					left join om_order_tracknumber as c
					on 		a.id = c.omOrderId
					where	(b.weighTime BETWEEN $start AND $end)
					and 	(a.ShippedTime ='' or a.ShippedTime is null) 
					and		c.tracknumber!=''
					and		a.transportId !='' 
					and 	a.accountId = '".$account."' AND a.storeId = 1 and a.is_delete = 0 ";*/
	$order_sql	= "	select 	a.id 
					from	".$tableName." as a 
					left join 	".$tableName."_warehouse as b 
					on		a.id = b.omOrderId 
					where	(b.weighTime BETWEEN $start AND $end) 
					and     a.platformId = 1 
					and		a.accountId = ".$account." 
					and		a.is_delete = 0 
					and 	a.storeId = 1 
					and     b.storeId = 1 ";
	//echo $order_sql; exit;
	$order_db	= $dbConn->query($order_sql);
	$orders		= $dbConn->fetch_array_all($order_db);
	//echo count($orders); echo "\n"; exit;
	//var_dump($orders); echo "\n"; exit;
	$handle_cnt=count($orders);
	if($handle_cnt<=0 ){
		exit("No order to handel\n");
	}
	/*$global_ebay_carrier=array();
	if( $handle_cnt>0 ){
		$ebay_csql			= "select value,name from ebay_carrier where ebay_user='$user'";
		$_ebay_carrier		= $dbcon->execute($ebay_csql);
		$_ebay_carrier		= $dbcon->getResultArray($_ebay_carrier);
		foreach($_ebay_carrier as $ec){
			$global_ebay_carrier[$ec['name']]=$ec['value'];
		}
		unset($_ebay_carrier);
	}else{
		exit("No order to handel\n");
	}*/
	
	#############类或API 实例化##############
	$api_cs		=new CompleteSaleAPI($__ebayaccount);
	
	$time_start=time();	
	echo "=====[".date('Y-m-d H:i:s',$time_start)."]系统【开始】处理账号【 $__ebayaccount 】订单 上传发货信息====>\n";
	
	$handle_idx=0;
	
	foreach($orders as $order){
		$handle_idx++;
		
		$omOrderId = $order['id'];
		//echo $omOrderId; echo "\n";
		/*if($order['platformId'] != 1){
			echo "订单编号{$omOrderId}不属于ebay平台\n";
			continue;	
		}*/
		$where = " where id = {$omOrderId} and storeId = 1 and is_delete = 0 ";
		$orderList = OrderindexModel::showOrderList($tableName, $where);
		//var_dump($orderList); exit;
		$orderTracknumber = $orderList[$omOrderId]['orderTracknumber'];
		if(empty($orderTracknumber)){
			continue;//无跟踪号不处理
		}
		$orderData = $orderList[$omOrderId]['orderData'];
		$orderExtenData = $orderList[$omOrderId]['orderExtenData'];
		$orderUserInfoData = $orderList[$omOrderId]['orderUserInfoData'];
		$orderDetailList = $orderList[$omOrderId]['orderDetail'];
		
		$countryName	= $orderUserInfoData['countryName'];
		$orderId = $orderExtenData['orderId'];
		$transportId = $orderData['transportId'];
		$tracknumber			= $orderTracknumber[0]['tracknumber'];
		$combineOrder			= $orderData['combineOrder'];
		$carrier = $delivery_arr[$transportId];
		
		$tran_datas = array();
		foreach($orderDetailList as $orderDetail){
			$orderDetailData = $orderDetail['orderDetailData'];
			$orderDetailExtenData = $orderDetail['orderDetailExtenData'];
			$itemid = $orderDetailExtenData['itemId'];
			$tid = $orderDetailExtenData['transId'];
			$sku = $orderDetailData['sku'];
			
			$tran_data=array();
			$tran_data['itemid']				=$itemid;
			$tran_data['tid']					=$tid;
			if($combineOrder == 2){
				$tran_data['orderid']			='';
			}else{
				$tran_data['orderid']			=$orderId;
			}
			$tran_data['ebay_carrier']			=$carrierList[$carrier];
			$tran_data['ebay_tracknumber']		=$tracknumber;
			$tran_datas[] = array('sku'=>$sku,'tran'=>$tran_data);
		}
		//var_dump($tran_datas); exit;
		//$account				= $order['ebay_account'];
		echo "eBay订单号: $ebay_orderid 来自账号: $__ebayaccount \n";
		
		if(!in_array($GLOBAL_EBAY_ACCOUNT[$account],$SYSTEM_ACCOUNTS['ebay'])){
			echo " 非ebay订单 跳过\n";
			echo "[".date('Y-m-d H:i:s')."]-----------$handle_idx/$handle_cnt--------done\n";
			continue;
		}
		if( $carrier == ''){
			echo "carrier为空,跳过\n";
			MarkShippingModel::update_order_shippedmarked_time($omOrderId);
			echo "[".date('Y-m-d H:i:s')."]-----------$handle_idx/$handle_cnt--------done\n";
			continue;						
		}
		if ($carrier=='EUB'){
			echo "carrier为EUB,跳过\n";
			MarkShippingModel::update_order_shippedmarked_time($omOrderId);
			echo "[".date('Y-m-d H:i:s')."]-----------$handle_idx/$handle_cnt--------done\n";
			continue;
		}
		if ($countryName=='United States'){
			if( $tracknumber == ''){
				echo "此单无trackno,但走美国需要上传订单编号作为伪跟踪号!\n";
				$tracknumber = $omOrderId;
			}else if (preg_match('#^00#i',$tracknumber)	&& preg_match('#^\d+$#i',$tracknumber)){
				echo "\n此订单走中国小包平邮的美国订单,trackno:$tracknumber 为伪号码,需要同步到ebay\n";
			}
		}else{
			if( $tracknumber == ''){
				echo "trackno为空,跳过\n";
				MarkShippingModel::update_order_shippedmarked_time($omOrderId);
				echo "[".date('Y-m-d H:i:s')."]-----------$handle_idx/$handle_cnt--------done\n";
				continue;						
			}
			if(	preg_match('#^00#i',$tracknumber)	&& preg_match('#^\d+$#i',$tracknumber)){
				echo "此订单走中国小包平邮的非美国订单,trackno:$tracknumber 为伪号码,跳过\n";
				MarkShippingModel::update_order_shippedmarked_time($omOrderId);
				echo "[".date('Y-m-d H:i:s')."]-----------$handle_idx/$handle_cnt--------done\n";
				continue;
			}
		}
		if(MarkShippingModel::update_order_shippingdetail_to_ebay($tran_datas)){
			MarkShippingModel::update_order_shippedmarked_time($omOrderId);
			echo "[".date('Y-m-d H:i:s')."]-----------$handle_idx/$handle_cnt--------done\n";
		}else{
			echo "[".date('Y-m-d H:i:s')."]-----------$handle_idx/$handle_cnt--------false\n";
		}
		/*if(strpos($combineOrder, '##')!==false){
			MarkShippingModel::update_order_shippingdetail_to_ebay('',$omOrderId,$tracknumber,$carrier);
		}else{
			MarkShippingModel::update_order_shippingdetail_to_ebay($orderId,$omOrderId,$tracknumber,$carrier);
		}*/
	}
	$time_end=time();
	echo "\t\t\t[耗时:".ceil(($time_end-$time_start)/60)."分钟]\n";
	echo "<=====[".date('Y-m-d H:i:s',$time_end)."]系统【结束】处理账号【 $__ebayaccount 】订单 上传发货信息====\n";
?>