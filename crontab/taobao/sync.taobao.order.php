<?php
$taobao_user	=	trim($argv[1]);
if(empty($taobao_user)){
	echo "empty user!\n";
	exit;
}
ini_set('max_execution_time', 1800);
if(!defined('WEB_PATH')){
	define("WEB_PATH","/data/web/order.valsun.cn/");
}
//define('SCRIPTS_PATH_CRONTAB', '/data/web/erpNew/order.valsun.cn/crontab/');    
require_once WEB_PATH."crontab/scripts.comm.php";
require_once WEB_PATH_CONF_SCRIPTS."script.ebay.config.php";
//$taobao_user	= '001';
$configFile	=	WEB_PATH_CONF_SCRIPTS_KEYS_TAOBAO."config_".$taobao_user.".php";
if (file_exists($configFile)){
	include_once $configFile;
}else{
	echo	"error： 未找对应的config文件!\n";
	exit;
}

require_once WEB_PATH_LIB_SDK_TAOBAO.'lib/taobao.trade.get.php';
require_once WEB_PATH_LIB_SDK_TAOBAO.'lib/taobao.trades.sold.get.php';
require_once WEB_PATH_LIB_SCRIPTS_TAOBAO.'taobao_order_func.php';

$omAvailableAct = new OmAvailableAct();
$GLOBAL_EBAY_ACCOUNT = $omAvailableAct->act_getTNameList2arrById('om_account', 'id', 'account', " WHERE is_delete = 0 AND platformId in(12,13) ");
//echo $account; echo "\n";
$FLIP_GLOBAL_EBAY_ACCOUNT = array_flip($GLOBAL_EBAY_ACCOUNT);
	
//$debug_mode	=	'false';	//调试模式

//获取第一页数据	//2013-05-14 01:01:00  
//$json_data	=	taobaoTradesSoldGet($url,$session,$appSecret,$appKey,"WAIT_SELLER_SEND_GOODS",1,$page_size,"","","wanchi1224");
$json_data	=	taobaoTradesSoldGet($url,$session,$appSecret,$appKey,$status,1,$page_size);

//print_r(get_account_id($account));die;
//var_dump($json_data);die;
//exit;
//分页获取后面的数据
$total_page	=	1;
if(isset($json_data['trades_sold_get_response']['total_results'])){
	$total	=	intval($json_data['trades_sold_get_response']['total_results']);
	if($total > $page_size){
		$total_page	=	ceil($total/$page_size);
	}
	if($total == 0){
		echo "notice: no data, exit\n";
		exit;
	}

}else{

	echo "notice: no data, exit\n";
	exit;
}
//$TaobaoAct = new TaobaoAct();

//查询快递信息
/*$checkcarrier	= array();
$carrierlists = CommonModel::getCarrierList();
//var_dump($carrierlists); exit;
if($carrierlists){
	foreach($carrierlists AS $carrierlist){
		$checkcarrier[] = "{$carrierlist['carrierNameCn']}";
	}
}*/
$checkcarrier = CommonModel::getCarrierListById();
$flip_checkcarrier = array_flip($checkcarrier);
//var_dump($flip_checkcarrier); exit;
$total	=	0;	//总订单条数
$error_data		= array();
for($cur_page = 1; $cur_page <= 1; $cur_page++){
	if($cur_page > 1){
		$json_data	=	taobaoTradesSoldGet($url,$session,$appSecret,$appKey,$status,$cur_page,$page_size);
	}

	//出错处理
	if(isset($json_data['error_response'])){
		echo "error: ".$json_data['error_response']['msg']. " error code:". $json_data['error_response']['code']."\n";
	}else{
		//数据入库
		$data	=	$json_data['trades_sold_get_response']['trades']['trade'];
		foreach($data as $trade){
			$insertOrder 	=   array();
			$trade_data		=	taobaoTradeGet($url,$appSecret,$session,$appKey,$trade['sid']);
			$recordnumber	=	$trade['sid'];			//淘宝订单号
			
			//$omAvailableAct = new OmAvailableAct();            
            $where  = " WHERE recordNumber = '$recordnumber' AND is_delete = 0 ";
        	$res	=  $omAvailableAct->act_getTNameList('om_unshipped_order', 'id', $where);
    		if(!empty($res)){
    			$error_data[] = "交易ID{$recordnumber}的已经存在与系统中!\n";
				continue;
    		}
	
			if(!empty($recordnumber)){
				$account_info = array();
				$account_info = get_account_id($account);
				if(empty($account_info)){
					$error_data[] = "交易ID{$recordnumber}的已经存在与系统中!\n";
					continue;
				}
				
				/***************BEGIN 订单表数据***************/
				$orderdata = array();
				$orderdata['recordNumber']	        =	$recordnumber;
				$orderdata['platformId']			=	$account_info['platformid'];
				$orderdata['accountId']	            =	$account_info['accountid'];
				$orderdata['orderStatus']			=	C('STATEPENDING');
				$orderdata['orderType']			    =	C('STATEPENDING_CONV');            
				$orderdata['ordersTime']		    =	strtotime($trade['created']);		// 成交时间
				$orderdata['paymentTime']			=	strtotime($trade['pay_time']);		// 付款时间
				$orderdata['onlineTotal']			=	$trade['price'];  				    //线上总金额
				$orderdata['actualTotal']			=	$trade['payment'];                  //付款总金额  
				$orderdata['transportId']			=	$flip_checkcarrier[$defalut_carrier];                   //运输方式id
				$orderdata['isFixed']				=	1;
				$orderdata['calcWeight']			=	0.125;   							//估算重量
				$orderdata['calcShipping']			=	round_num($trade['post_fee'], 2);   //物流费用    	
				$orderdata['orderAddTime']			=	time();
				$orderdata['isNote']			    =	isset($trade_data['trade_get_response']['trade']['buyer_message']) ? 1:0;
				/***************END 订单表数据***************/
				
				/***************BEGIN 订单扩展表数据***************/
				$orderExtTaobao = array(); //          
				$orderExtTaobao['paymentStatus']		=	"Complete";  
				$orderExtTaobao['transId']			    =	$recordnumber;   // 交易id;;
				$orderExtTaobao['platformUsername']		=	$trade['buyer_nick'];            
				$orderExtTaobao['currency']				=	"RMB";  
				$orderExtTaobao['feedback']				=	isset($trade_data['trade_get_response']['trade']['buyer_message']) ? $trade_data['trade_get_response']['trade']['buyer_message']:"";    //客户留言 
				//$ebay_noteb								=	isset($trade_data['trade_get_response']['trade']['seller_memo']) ? $trade_data['trade_get_response']['trade']['seller_memo']:"";		// 卖家订单备注		        
				/***************END 订单扩展表数据***************/
				
				/***************BEGIN 订单用户表数据***************/
				$orderUserInfo = array();           
				$orderUserInfo['username']			=	$trade['receiver_name'];            
				$orderUserInfo['platformUsername']  =	$trade['buyer_nick'];
				$orderUserInfo['email']			    =	"";            
				$orderUserInfo['countryName']	 	=	"China";
				$orderUserInfo['countrySn']			=	"CN";            
				$orderUserInfo['currency']          =	"RMB";      	
				$orderUserInfo['state']			    =	$trade['receiver_state'];			// 省
				$orderUserInfo['city']				=	$trade['receiver_city'];			// 市           	
				$t_street							=	$trade['receiver_state']." ".$trade['receiver_city']." ".$trade['receiver_district']." ".$trade['receiver_address'];	
				$t_street							=	htmlentities($t_street, ENT_QUOTES, "UTF-8");	
				$orderUserInfo['street']			=	$t_street;
				$orderUserInfo['address2']			=	"";
				$orderUserInfo['landline']			=	$trade['receiver_phone'];			// 座机电话           
				$orderUserInfo['phone']				=	$trade['receiver_mobile'];			// 手机  
				$orderUserInfo['zipCode']			=	$trade['receiver_zip'];				// 邮编  
			   /*************END 订单用户表数据***************/
		
				$cn_carrier	 =	array('顺丰快递','韵达快递','申通快递','中通快递','汇通快递','圆通快递','天天快递','同城速递','国内快递','EMS经济小包','EMS快递');
											
				if(!in_array($defalut_carrier, $checkcarrier) && !in_array($defalut_carrier,$cn_carrier)){
					$error_data[] = "交易ID{$recordnumber}的邮寄方式有误--------{$defalut_carrier}";
					continue;
				}
				
				//新增购物明细（每个sku一条数据）
				$orders	=	$trade['orders']['order'];
				$orderweight	=	0;
				$sku_infos = array();
				$obj_order_detail_data = array();
				foreach($orders as $order){
					/***************BEGIN 订单详细数据***************/
					$orderdata_detail	=	array();       
					$orderdata_detail['omOrderId']	    =	$o_insertId;               
					$orderdata_detail['recordNumber']	=	$recordnumber; 
					$sku =	$order['outer_sku_id'];				//SKU
					if(isset($order['outer_iid']) && !isset($order['outer_sku_id'])){
						$sku	=	$order['outer_iid'];
					}
					$sku_infos[]		=   $sku;
					$orderdata_detail['sku']			=	$sku; 
					$orderdata_detail['itemPrice']      =	round_num($order['price'], 2);		//淘宝产品标价 
					$orderdata_detail['amount']     	=	$order['num'];					//SKU数量
					//$orderdata_detail["shippingFee"]	=	''; 
					//$orderdata_detail["reviews"]	    =	''; 
					$orderdata_detail['createdTime']    =	time(); 
					/*************END 订单详细数据***************/
					
					/***************BEGIN 订单详细扩展表数据***************/
					$orderDetailExtTaobao	=	array();               
					$orderDetailExtTaobao['itemTitle']	   =	$order['title']."#".$order['sku_properties_name']."#";	//产品名称; 
					$orderDetailExtTaobao['itemURL']	   =	$order['pic_path'];                      
					$orderDetailExtTaobao['itemId']	       =	$order['sku_id'];
					$orderDetailExtTaobao['transId']	   =	$recordnumber; // 交易id;
					$orderDetailExtTaobao['note']	       =	round_num($order['payment'], 2);	//实际SKU付款价 
					/*************END 订单详细扩展表数据***************/
					
					$obj_order_detail_data[] = array('orderDetailData' => $orderdata_detail,			
													'orderDetailExtenData' => $orderDetailExtTaobao
													);
				}
				
				//包含HH555料号的订单移动到淘宝待审核 
				if(in_array('HH555', $sku_infos) /*|| strpos($ebay_noteb, 'ERP审核订单')!==false*/){
					$orderdata['orderType'] = C('STATEPENDING_LYNXPEND');
				}
				
				$insertOrder = array('orderData' => $orderdata,
									'orderExtenData' => $orderExtTaobao,					  
									'orderUserInfoData' => $orderUserInfo
									);
				//var_dump($obj_order_detail_data); exit;
				$insertOrder['orderDetail'] = $obj_order_detail_data;
				//var_dump($obj_order_detail_data); echo "<br>";
				$calcInfo = CommonModel :: calcAddOrderWeight($obj_order_detail_data);//计算重量和包材
				//var_dump($calcInfo); exit;
				$insertOrder['orderData']['calcWeight'] = $calcInfo[0];
				$insertOrder['orderData']['pmId'] = $calcInfo[1];
				if(count($insertOrder['orderDetail']) > 1){
					$insertOrder['orderData']['orderAttribute'] = 3;
				}else if(isset($insertOrder['orderDetail'][0]['orderDetailData']['amount']) && $insertOrder['orderDetail'][0]['orderDetailData']['amount'] > 1){
					$insertOrder['orderData']['orderAttribute'] = 2;
				}
				$calcShippingInfo = CommonModel :: calcAddOrderShippingFee($insertOrder,1);//计算运费
				//var_dump($calcShippingInfo); exit;
				//$insert_orderData['orderData']['calcShipping'] = $calcShippingInfo['fee']['fee'];
				$insertOrder['orderData']['channelId'] = $calcShippingInfo['fee']['channelId'];
				
				$insertOrder = AutoModel :: auto_contrast_intercept($insertOrder);
				//print_r($interceptInfo); exit;
				/*$insertOrder['orderData']['orderStatus'] = $interceptInfo['orderStatus'];
				$insertOrder['orderData']['orderType'] = $interceptInfo['orderType'];*/
						
				//print_r($orderData); exit;
				if(OrderAddModel :: insertAllOrderRow($insertOrder)){
					echo "-----".date("Y-m-d H:i:s").", 新增订单{$orderdata["recordNumber"]}成功\r\n";
				}else{
					echo "-----".date("Y-m-d H:i:s").", 新增订单{$orderdata["recordNumber"]}失败\r\n";
				}
				$total++;
			}	
		}
	}
}

echo "DATE: ".date("Y-m-d H:i:s"). "-----------------------------------\n";
echo "\n".implode("\n", $error_data);
echo "\n订单导入完成, 共导入".$total."条订单\n";
exit;
?>