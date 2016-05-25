<?php
error_reporting(E_ALL);
//include_once "/data/scripts/ebay_order_cron_job/ebay_order_cron_config.php";
//include_once "/data/scripts/ebay_order_cron_job/function_purchase.php";

ini_set('max_execution_time', 1800);

$mctime = time();

if(!defined('WEB_PATH')){
	define("WEB_PATH","/data/web/order.valsun.cn/");
}
//define('SCRIPTS_PATH_CRONTAB', '/data/web/erpNew/order.valsun.cn/crontab/');    
require_once WEB_PATH."crontab/scripts.comm.php";
require_once WEB_PATH_CONF_SCRIPTS."script.ebay.config.php";
include WEB_PATH_LIB_SDK."cndirect/CNCommenScript.php";
//include "/data/web/erpNew/order.valsun.cn/lib/sdk/CN/CNCommenScript.php";
echo "调用CN订单接口,时间".date("Y-m-d H:i:s")."\r\n";
//global $memc_obj;
$orders = getCNOrders();
$orders = json_decode($orders,true);



	$orderids = $orders['OrderID'];

	if(empty($orderids)){
		//$orderids = array('CN100000204','CN100000398','CN100000407','CN100000411','CN100000420','CN100000422');
		//$orderids = Array ('CN100000352','CN100000355','CN100000357','CN100000356');
		echo "没有获取到任何订单！\n";
		//exit;
	}else{
		if(file_exists(WEB_PATH."crontab/cndirect/orderid.txt")){
			$fp = fopen(WEB_PATH."crontab/cndirect/orderid.txt","a+");
			$str = "\r\n\r\n\r\n\r\n******************".date("Y-m-d H:i:s")."*******************\r\n";
			foreach($orderids as $id){
				$str .= ",".$id;
			}
			$str .= "\r\n#############################################################";
			fwrite($fp,$str);
			fclose($fp);
		
		}
	}
	//$orderids = Array ('CN100001503','CN100001505','CN100001506','CN100001509','CN100001510');
	$orderdetails = getCNOrderDetails($orderids);

	
	if(!empty($orderdetails)){

		
		foreach($orderdetails as $val){
			
			$note = mysql_real_escape_string(trim($val['Note']));
			$orderArr = $val['OrderArray'];
			if(empty($orderArr)){
				echo "没有抓取到任何订单明细！\r\n";
				exit;
			}
			$insertOrder = array();
			foreach($orderArr as $key=>$value){
				$order = array();
				$ebay_fedex_remark = array();
				
				/***************BEGIN 订单表数据***************/
				$orderdata = array();
				//$where = "where platform='dresslink'";
				//$plateform = DLdlModel::selecPlatform($where);
				
				//$where = "where account='dresslink.com'";
				//$account = 	DLdlModel::selectAccount($where);
				$orderdata['recordnumber']	        =	mysql_real_escape_string(trim($value['OrderID']));
				$orderdata['platformId']			=	8;
				$orderdata['accountId']	            =	410;
				$orderdata['orderStatus']			=	C('STATEPENDING');
				$orderdata['orderType']			    =	C('STATEPENDING_CONV');     
				$orderdata['ordersTime']		    =	strtotime(trim($value['CreatedTime']));                                 //平台下单时间
				$orderdata['paymentTime']			=	strtotime(trim($value['PaidTime']));
				$orderdata['onlineTotal']			=	$trade['price'];  				                                        //线上总金额
				$orderdata['actualTotal']			=	mysql_real_escape_string(trim($value['Total']));                        //付款总金额  
				$transport							= 	mysql_real_escape_string(trim($value['ShippingModuleCode']));
				$transport							= 	carrier($transport);
				//$trans = $memc_obj->get_extral("trans_system_carrier");
				if($transport=="中国邮政挂号" || $transport=="中国邮政平邮"){
					$orderNote['content'] = base64_decode(trim($value['OrderNote']));
				}
				//$orderdata['transportId'] = $flip_carrierList[$transport];                                                       //运输方式id
				$transportation = CommonModel::getCarrierList();   //所有的
				foreach($transportation as $tranValue){
					if($tranValue['carrierNameCn']==$transport){
						$orderdata['transportId'] = $tranValue['id'];
						break;
					}
					//$transportationList[$tranValue['id']] = $tranValue['carrierNameCn'];
				}
				if(count($value['Items'])==1&&$value['Items'][0]['Quantity']==1){
					$orderdata['orderAttribute'] 	= 1;
				}elseif(count($value['Items'])==1&&$value['Items'][0]['Quantity']>1){
					$orderdata['orderAttribute'] 	= 2;
				}else{
					$orderdata['orderAttribute'] 	= 3;
				}
				$orderdata['isFixed']				=	1;
				//$orderdata['calcWeight']			=	0.125;   							                                     //估算重量
				$orderdata['calcShipping']			=	round_num(mysql_real_escape_string(trim($value['ShipFee'])), 2);         //物流费用    	
				$orderdata['orderAddTime']			=	time();
				$orderdata['isNote']			    =	empty($note) ? 0:1;
				/***************END 订单表数据***************/      
				/***************判断订单是否已存在***************/
				$where = "where recordnumber='{$orderdata['recordnumber']}'";
				$orderinfo = cndlModel::selectOrder($where);
				if($orderinfo){
					echo "订单 {$orderdata['recordnumber']}已存在！\n";
					continue;
				}
				/*$ordersql = array2sql($orderdata);
				$msg = DLdlModel::insertOrder($ordersql);		//插入订单
				if(!$msg){
					echo "订单{$orderdata['recordnumber']}插入失败！\r\n";
					continue;
				}
				$omOrderId = mysql_insert_id();*/
				
				
				/***************BEGIN 订单扩展表数据***************/
				$orderExtDL = array(); //          
				//$orderExtDL['omOrderId']			= 	$omOrderId;
				$orderExtDL['paymentStatus']		=	"Complete";
				$orderExtDL['transId']			    =	mysql_real_escape_string(trim($value['TransactionID']));
				$orderExtDL['paymentMethod']		=	mysql_real_escape_string(trim($value['PaymentMethod']));         
				$orderExtDL['paymentModule']		=	mysql_real_escape_string(trim($value['PaymentModuleCode']));     
				$orderExtDL['shippingMethod']		=	base64_decode(trim($value['ShippingMethod']));  
				$orderExtDL['ShippingModule']		=	mysql_real_escape_string(trim($value['ShippingModuleCode']));  
				$orderExtDL['currency']				=	mysql_real_escape_string(trim($value['Currency']));
				$orderExtDL['feedback']				=	trim($value['Note']);    //客户留言      
				/***************END 订单扩展表数据***************/
				
				/*$sql = array2sql($orderExtDL);
				$msg = DLdlModel::insertOrderExt($sql);
				if(!$msg){
					echo "订单{$orderdata['recordnumber']}订单扩展信息插入失败！\r\n";
					BaseModel::rollback();
				}*/
				
				/***************BEGIN 订单用户表数据***************/
				$orderUserInfo = array();     
				$orderUserInfo['omOrderId']			= 	$omOrderId;
				$orderUserInfo['username']			=	base64_decode(trim($value['ShippingAddress']['Name']));        
				$orderUserInfo['platformUsername']  =	base64_decode(trim($value['CustomerName']));
				$orderUserInfo['email']			    =	mysql_real_escape_string(trim($value['CustomerEmail']));           
				$orderUserInfo['countryName']	 	=	mysql_real_escape_string(trim($value['ShippingAddress']['CountryName']));
				if($orderUserInfo['countryName'] == base64_encode(base64_decode($orderUserInfo['countryName']))){
					$orderUserInfo['countryName'] = base64_decode($orderUserInfo['countryName']);
				}
				
				$orderUserInfo['countrySn']			=	"CN";            
				$orderUserInfo['currency']          =	mysql_real_escape_string(trim($value['Currency']));      	
				$orderUserInfo['state']			    =	base64_decode(trim($value['ShippingAddress']['StateOrProvince']));			// 省
				$orderUserInfo['city']				=	base64_decode(trim($value['ShippingAddress']['CityName']));			        // 市           	
				//$t_street		=	$trade['receiver_state']." ".$trade['receiver_city']." ".$trade['receiver_district']." ".$trade['receiver_address'];	
				//$t_street		=	htmlentities($t_street, ENT_QUOTES, "UTF-8");	
				$orderUserInfo['street']			=	base64_decode(trim($value['ShippingAddress']['Street1']));
				$orderUserInfo['address2']			=	base64_decode(trim($value['ShippingAddress']['Street2']));;
				$orderUserInfo['landline']			=	"";			// 座机电话           
				$orderUserInfo['phone']				=	mysql_real_escape_string(trim($value['ShippingAddress']['Phone']));			            // 手机  
				$orderUserInfo['zipCode']			=	mysql_real_escape_string(trim($value['ShippingAddress']['PostalCode']));				// 邮编  
				/*************END 订单用户表数据***************/
				
				/*$sql = array2sql($orderUserInfo);
				$msg = DLdlModel::insertOrderUserInfo($sql);
				if(!$msg){
					echo "订单{$orderdata['recordnumber']}订单用户信息插入失败！\r\n";
					BaseModel::rollback();
				}*/
				

				//echo "订单 {$order['recordnumber']} 信息添加成功！--".date("Y-M-d H:i:s",$mctime)."--\n";
				$details = $value['Items'];
				
				$obj_order_detail_data = array();
				if(empty($details)){
					echo "订单号{$orderdata['recordnumber']}的料号信息为空！\r\n";
					continue;
				}
				foreach($details as $detail){
				
					/***************BEGIN 订单详细数据***************/
					$orderdata_detail	=	array();       
					$orderdata_detail['omOrderId']	    =	$omOrderId;               
					$orderdata_detail['recordNumber']	=	$orderdata['recordnumber']; 
				
					$orderdata_detail['sku']			=	mysql_real_escape_string(trim($detail['Sku']));; 
					$orderdata_detail['itemPrice']      =	round_num(mysql_real_escape_string(trim($detail['RealPirce'])), 2);		// 
					$orderdata_detail['amount']     	=	mysql_real_escape_string(trim($detail['Quantity']));				//SKU数量
					$orderdata_detail["shippingFee"]	=	mysql_real_escape_string(trim($detail['SkuShipfee'])); 
					//$orderdata_detail["reviews"]	    =	''; 
					$orderdata_detail['createdTime']    =	time(); 
					/*************END 订单详细数据***************/
					/*$sql = array2sql($orderdata_detail);
					$msg = DLdlModel::insertOrderDetail($sql);
					if(!$msg){
						echo "订单{$orderdata['recordnumber']}订单明细信息插入失败！\r\n";
						BaseModel::rollback();
					}*/
					
					/***************BEGIN 订单详细扩展表数据***************/
					$orderDetailExtDL	=	array();               
					$orderDetailExtDL['itemTitle']	   =	mysql_real_escape_string(trim($detail['Title']));
					$categoryName  					   =	mysql_real_escape_string(trim($detail['CategoryName']));                     
					$customCode						   =	mysql_real_escape_string(trim($detail['CustomCode']));
					$material						   =	mysql_real_escape_string(trim($detail['Material']));
					$skuShipfee						   =	mysql_real_escape_string(trim($detail['SkuShipfee']));
					$ShenBaoQuantity				   =	mysql_real_escape_string(trim($detail['ShenBaoQuantity']));
					$ShenBaoUnitPrice				   = 	mysql_real_escape_string(trim($detail['ShenBaoUnitPrice']));
					$salePrice						   =	round_num(mysql_real_escape_string(trim($detail['SalePrice'])), 2);	//实际SKU付款价
					/*************END 订单详细扩展表数据***************/
					/*$sql = array2sql($orderDetailExtDL);
					$msg = DLdlModel::insertOrderDetailExtDL($sql);
					if(!$msg){
						echo "订单{$orderdata['recordnumber']}订单明细扩展信息插入失败！\r\n";
						BaseModel::rollback();
					}*/
					$obj_order_detail_data[] = array('orderDetailData' => $orderdata_detail,			
											'orderDetailExtenData' => $orderDetailExtDL
											);
					$ebay_fedex_remark[$categoryName][] = array('real_price'=>$ShenBaoQuantity,'qty'=>$ShenBaoUnitPrice,'hamcodes'=>$customCode,'detail'=>$material);
				}
				$insertOrder = array(
								'orderData' => $orderdata,
								'orderExtenData' => $orderExtDL,					  
								'orderUserInfoData' => $orderUserInfo,
								'orderDetail' => $obj_order_detail_data,
								'orderNote' => $orderNote
								);
								
				$calcInfo = CommonModel :: calcAddOrderWeight($insertOrder['orderDetail']);//计算重量和包材
				//var_dump($calcInfo); exit;
				$insertOrder['orderData']['calcWeight'] = $calcInfo[0];
				$insertOrder['orderData']['pmId'] = $calcInfo[1];
				//$insertOrder['orderData']['transportId'] = $flip_transportList[get_carrier($insertOrder['orderData']['calcWeight'], $insertOrder['orderUserInfoData']['countryName'])];
				$calcShippingInfo = CommonModel :: calcAddOrderShippingFee($insertOrder,1);//计算运费
				//var_dump($calcShippingInfo); exit;
				//$insert_orderData['orderData']['calcShipping'] = $calcShippingInfo['fee']['fee'];
				$insertOrder['orderData']['channelId'] = $calcShippingInfo['fee']['channelId'];
				
				$insertOrder = AutoModel :: auto_contrast_intercept($insertOrder);
				$omOrderId = OrderAddModel::insertAllOrderRow($insertOrder,'cndl');
				if($omOrderId){
					echo "订单 {$orderdata['recordnumber']} 信息添加成功！ERP订单号为{$omOrderId}--".date("Y-M-d H:i:s",$mctime)."--\n";
				}
				foreach($ebay_fedex_remark as $k=>$v){
					$fedex_remark = array();
					if($carrierList[$orderdata['transportId']]=='FedEx'){
						$fedex_remark['description'] = "[No Brand]". $k."({$v[0]['detail']})";
						$fedex_remark['type'] 		 = 1;
					}elseif($carrierList[$orderdata['transportId']]=='DHL' || $carrierList[$orderdata['transportId']]=='EMS'){
						$fedex_remark['description'] = trim($k);
						$fedex_remark['type'] 		 = 2;
					}else{
						continue;
					}
					$sku_price = 0;
					$qty = 0;
					foreach($v as $v0){
						$sku_price 	+= $v0['real_price'];
						$qty 		+= $v0['qty'];
					}
					//$fedex_remark['ebay_ordersn'] 	= $order['ebay_ordersn'];
					$fedex_remark['price'] 			= round($sku_price/$qty,2);
					$fedex_remark['amount'] 		= $qty;
					$fedex_remark['hamcodes'] 		= $v[0]['hamcodes'];
					if($carrierList[$orderdata['transportId']]=='DHL' ||$carrierList[$orderdata['transportId']]=='EMS'){
						$fedex_remark['price']		= round($sku_price,2);
					}
					$fedex_remark['createdTime'] 	= time();
					$fedex_remark['omOrderId'] 		= $omOrderId;
					$fedex_remark['creatorId'] 		= 253;
					
					//$insert_fedex_sql = "INSERT INTO fedex_remark set ".array2sql($fedex_remark);
					$info = OmAvailableModel::insertRow("om_express_remark"," set ".array2sql($fedex_remark));
					if($info){	
						//echo "----<font color=green> {$order['recordnumber']} 导入海关记录成功！</font><br>";				
					}else{
						//echo $insert_fedex_sql; echo "<br>";
						//echo "----<font color=red>{$order['recordnumber']} 导入海关记录失败！</font><br>";
						$fail_order[] = $orderdata['recordnumber'];
					}
				}
				//echo "订单".$ebay_id."抓取成功！<br>";
				
			//baseModel::commit();
		}

					
	
	}		
		
	}else{
		/**/
		echo "没有获取到任何订单明细信息!\n";
		exit;
	}
if(($orders['ACK'] != "Success")){    //成功获取订单信息
	if($orders['Errors']['ShortMessage'] != ""){
		echo "orders ShortMessage:".$orders['Errors']['ShortMessage']."\n";
	}
	if($orders['Errors']['LongMessage'] != ""){
		echo "orders LongMessage".$orders['Errors']['LongMessage']."\n";
	}
	if($orders['Errors']['ErrorCode'] != ""){
		echo "orders ErrorCode".$orders['Errors']['ErrorCode']."\n";
	}
}

?>