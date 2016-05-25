<?php
    //脚本参数检验
    /*if($argc!=3){
    	exit("Usage: /usr/bin/php	$argv[0] eBayAccount minutes_ago \n");
    }*/
	if(!defined('WEB_PATH')){
		define("WEB_PATH","/data/web/order.valsun.cn/");
	}
    //define('SCRIPTS_PATH_CRONTAB', '/data/web/erpNew/order.valsun.cn/crontab/');    
    require_once WEB_PATH."crontab/scripts.comm.php";
	require_once WEB_PATH_CONF_SCRIPTS."script.ebay.config.php";
    require_once WEB_PATH_LIB_SDK_ALIEXPRESS."Aliexpress.class.php";
    //速卖通账号与ERP后台账号的映射表
    require_once WEB_PATH_CONF_SCRIPTS_KEYS_ALIEXPRESS."config/common.php";
	require_once WEB_PATH_LIB_SCRIPTS_ALIEXPRESS."aliexpress_order_func.php";
    
    /*$defaultstoreid	=	76;
    $user	=	"vipchen";*/
    $aliexpress_user	=	trim($argv[1]);
    //$aliexpress_user	=	"cn1001656836";
    
    if(!array_key_exists($aliexpress_user, $erp_user_mapping)){
    	echo "error：账号不存在: ".$aliexpress_user."\n";
    	exit;
    }
    $logfile	=	SCRIPT_ROOT_LOG."aliexpress/sync_order_".$aliexpress_user."_".date("Y-m-d").".log";
    
    $account	=	$erp_user_mapping[$aliexpress_user];
    $log		=	"\n\n\nDate: ".date("Y-m-d H:i:s"). " 开始速卖通(".$aliexpress_user.")的订单同步\n";
    @file_put_contents($logfile, $log, FILE_APPEND);
    
    //加载个性化配置信息
    $configFile = WEB_PATH_CONF_SCRIPTS_KEYS_ALIEXPRESS."config/config_{$aliexpress_user}.php";
    if (file_exists($configFile)){
    	include_once $configFile;
    }else{
    	$log	=	"error： 未找对应的config文件!\n";
    	@file_put_contents($logfile, $log, FILE_APPEND);
    	exit;
    }
	
	$omAvailableAct = new OmAvailableAct();
	$GLOBAL_EBAY_ACCOUNT = $omAvailableAct->act_getTNameList2arrById('om_account', 'id', 'account', " WHERE is_delete = 0 AND platformId = 2 ");
	//echo $account; echo "\n";
    $FLIP_GLOBAL_EBAY_ACCOUNT = array_flip($GLOBAL_EBAY_ACCOUNT);
	
	if(!isset($FLIP_GLOBAL_EBAY_ACCOUNT[$account])){
		exit ("$account is not in TABLE om_account!\n");
	}
	//var_dump($FLIP_GLOBAL_EBAY_ACCOUNT); exit;
    //特殊处理， 不需要拦截， 直接进入到淘代销订单文件夹
    $taotao_account	=	array(	//taotaoAccount
    	"cn1501642501",
    	"cn1501654678",
    	"cn1501654797",
    	"cn1501655651",
    	"cn1501656206",
    	"cn1501656494",
    	"cn1501657160",
    	"cn1501657334",
    	"cn1501657572",
    	"cn1501686293",
    );
    
    $aliexpress = new Aliexpress();
    $aliexpress->setConfig($appKey,$appSecret,$refresh_token);
    $aliexpress->doInit();
	
    //$orderList = $aliexpress->findOrderById('1125817402');	//1125257007
    //$orderList	=	$aliexpress->listLogisticsService();
    //echo json_encode($orderList);
    //exit;
    
    $orderList = $aliexpress->findOrderListQuery();
    $totalDataNum	=	sizeof($orderList);
    //print_r($orderList);
    //var_dump($orderList);
    //echo json_encode($orderList);
	
    echo $log	=	"-----此次共拉取到 ".$totalDataNum." 条数据\n";    
    //echo $log;
    //exit;
	
    @file_put_contents($logfile, $log, FILE_APPEND);
    $index	=	0;
    if($totalDataNum > 0){
    	foreach($orderList as $order){
            //print_r($order);exit;         
    		$orderDetail2	=	$order['v'];
    		$order	=	$order['detail']; 
           // print_r($order);exit;
    
    		//只同步已付款24小时后未发货的订单
    		//订单是美国时间（实行夏令时， 比上海时间慢12小时）
    		$pay_time	=	time_shift($order['gmtPaySuccess']);
    		$left_time	=	$pay_time[1]-$pay_time[0];
    		if($left_time <= 86400)	continue;
    		//-------------------------------
			
    	    $omAvailableAct = new OmAvailableAct();            
            $where  = " WHERE recordNumber = '{$order["id"]}' AND is_delete = 0 ";
        	$res	=  $omAvailableAct->act_getTNameList('om_unshipped_order', 'id', $where);
    		if(!empty($res)){
    			echo $log	=	"--{$order['id']}--系统已经存在这个订单\n";
    			@file_put_contents($logfile, $log, FILE_APPEND);
    			continue;
    		}
			
			$insertOrder = array();
            /***************BEGIN 订单表数据***************/
            //$unshipedOrder = array();
     	    $orderdata['recordNumber']	        =	$order['id'];
            $orderdata['platformId']			=	2; //Aliexpress's platformId is 2
            $orderdata['accountId']	            =	$FLIP_GLOBAL_EBAY_ACCOUNT[$account];
            $orderdata['orderStatus']			=	C('STATEPENDING');
			if(!in_array($aliexpress_user, $taotao_account)){ //非淘代销订单， 需要进行拦截
           		$orderdata['orderType']			    =	C('STATEPENDING_CONV');
			}else{
				$orderdata['orderType']			    =	C('STATEPENDING_CONSIGNMENT');	
			}
			$gmtCreate = time_shift($order['gmtCreate']);
    		$orderdata['ordersTime']		    =	$gmtCreate[0];
    		$orderdata['paymentTime']			=	$pay_time[0];
            $orderdata['onlineTotal']			=	$order['initOderAmount']['amount'];  //线上总金额
    		$orderdata['actualTotal']			=	$orderDetail2['payAmount']['amount'];//付款总金额    	
            $orderdata['calcShipping']			=	$order['logisticsAmount']['amount']; //物流费用    	
    		$orderdata['orderAddTime']			=	time();
			$orderdata['isFixed']				=	1;
            /***************END 订单表数据***************/            
            
            /***************BEGIN 订单扩展表数据***************/
            $orderExtAli = array(); //          
            $orderExtAli['declaredPrice']		=	$order['orderAmount']['amount'];  
            $orderExtAli['paymentStatus']		=	$order['fundStatus'];  
            $orderExtAli['transId']			    =	$order['id'];//$orderdetail["id"]; // 交易id;;
            //$orderExtAli[PayPalPaymentId"]	=	'';
            //$orderExtAli["site"]			    =	'';
            $orderExtAli['orderId']			    =	$orderDetail2['orderId'];
            $orderExtAli['platformUsername']	=	$order['buyerSignerFullname'];;            
            $orderExtAli['currency']			=	$order['orderAmount']['currencyCode'];          
            $orderExtAli['PayPalEmailAddress']	=	$order['buyerInfo']['email'];;
            $orderExtAli['eBayPaymentStatus']	=	$order['orderStatus']; //订单状态;            
            /***************END 订单扩展表数据***************/
            
            /***************BEGIN 订单用户表数据***************/
            $orderUserInfo = array();           
            $orderUserInfo['username']			=	$order['receiptAddress']['contactPerson'];            
            $orderUserInfo['platformUsername']  =	$order['buyerSignerFullname'];
            $orderUserInfo['email']			    =	$order['buyerInfo']['email'];            
            $orderUserInfo['countryName']	 	=	get_country_name($order["receiptAddress"]["country"]);
            $orderUserInfo['countrySn']			=	$order['receiptAddress']['country'];         
            $orderUserInfo['currency']          =	$order['orderAmount']['currencyCode'];      	
    		$orderUserInfo['state']			    =	$order['receiptAddress']['province'];
    		$orderUserInfo['city']				=	$order['receiptAddress']['city'];           	
            $orderUserInfo['street']			=	$order['receiptAddress']['detailAddress'];
    		$orderUserInfo['address2']			=	isset($order['receiptAddress']['address2']) ? $order['receiptAddress']['address2'] : "";
            $orderUserInfo['zipCode']			=	$order['receiptAddress']['zip'];            
    		if(isset($order['receiptAddress']['phoneNumber'])){
    			if(isset($order['receiptAddress']['phoneArea'])){
    				$orderUserInfo['landline'] = $order['receiptAddress']['phoneCountry'].'-'.$order['receiptAddress']['phoneArea'].'-'.$order['receiptAddress']['phoneNumber'];
    				$orderUserInfo['phone'] = isset($order['receiptAddress']['mobileNo']) ? $order['receiptAddress']['mobileNo']: "";
    			}else{
    				$orderUserInfo['landline'] = $order['receiptAddress']['phoneNumber'];
    				$orderUserInfo['phone'] = isset($order['receiptAddress']['mobileNo']) ? $order['receiptAddress']['mobileNo']: "";
    			}
    		}else{
    			$orderUserInfo['phone'] = $order['receiptAddress']['mobileNo'];
    		}
           /*************END 订单用户表数据***************/
		   
            $carrier	=	array();
            $item_notes	=	array();
            $noteb		=	array();            
            foreach($orderDetail2['productList'] as $product){
            	$item_notes[$product['orderId']]	=	htmlentities($product['memo'], ENT_QUOTES); //买家留言
            	if(!empty($product['memo'])){
            		$noteb[]	=	$item_notes[$product['orderId']];
            	}
            	if(!in_array($product['logisticsServiceName'],$carrier)){
            		$carrier[] = $product['logisticsServiceName'];
            	}
            }
			if(!empty($noteb)){
					
			}
			$isNote = 0;
			if(!empty($noteb)){
				$isNote = 1;
				$orderdata['orderType'] = C('STATEPENDING_MSG');
			}
			$orderdata['isNote']	    =	$isNote;
            $orderExtAli['feedback']	=	implode(" ", $noteb);
            if(count($carrier) == 1){
            	$carrier_name = get_carrier_name($carrier[0]);
            	//$orderdata['carrier']	=	$carrier_name;
            }
            
            $transportId = '';
            $transportList = CommonModel::getCarrierList();
            foreach($transportList as $key => $trans) {
                if($trans['carrierNameCn'] == $carrier_name) {
                    $transportId = $trans['id'];
                    break;
                }
            }
			$expressArr = CommonModel::getCarrierInfoById(1);
			//var_dump($expressArr);
			if(in_array($transportId, $expressArr)){
				$orderdata['orderType'] = C('STATEPENDING_HASARRIVED');
			}
            $orderdata['transportId'] =	$transportId; //运输方式ID  
           // print_r($orderdata);
           // exit;
			
			$insertOrder = array('orderData' => $orderdata,
								'orderExtenData' => $orderExtAli,					  
								'orderUserInfoData' => $orderUserInfo
								);
			
			$orderweight	=	"";
			$obj_order_detail_data = array();
			foreach($orderDetail2['productList'] as $orderdetail){
				//明细表
                $orderdata_detail	=	array();       
                //$orderdata_detail['omOrderId']	    =	$insertId;//$order["id"];                 
				$orderdata_detail['recordNumber']	=	$order['id'];    			 
				$orderdata_detail['sku']			=	substr($orderdetail['skuCode'],0,stripos($orderdetail['skuCode'],'#')); 
				$orderdata_detail['itemPrice']      =	$orderdetail['productUnitPrice']['amount']; 
				$orderdata_detail['amount']     	=	$orderdetail['productCount']; 
				//$orderdata_detail["shippingFee"]	=	''; 
				//$orderdata_detail["reviews"]	    =	''; 
				$orderdata_detail['createdTime']    =	time();    				
            	
				//明细扩展表
                $orderDetailExtAli	=	array();               
                $orderDetailExtAli['itemTitle']	        =	$orderdetail['productName']; 
                $orderDetailExtAli['itemURL']	        =	$orderdetail['productSnapUrl'];                      
                $orderDetailExtAli['itemId']	        =	$orderdetail['productId'];
                $orderDetailExtAli['transId']	        =	$orderdetail['orderId']; // 交易id;
                $orderDetailExtAli['note']	            =	$item_notes[$orderdetail['orderId']]; 
                       
                /*print_r($orderdata_detail);
                print_r($orderDetailExtAli); */
				/*array('recordNumber'=>$tran_recordnumber,
				'itemPrice'=>$tran_price,
				'sku'=>strtoupper($odSKU),
				'amount'=>$QuantityPurchased,
				'shippingFee'=>$goodsshippingcost,
				'createdTime'=>$mctime,
				)*/
				/*array('itemId'=>$tran_itemid,
				 'transId'=>$tran_id,
				 'itemTitle'=>$odItemTitle,
				 'itemURL'=>'',
				 'shippingType'=>$oShipingService,
				 'FinalValueFee'=>$FinalValueFee,
				 'FeeOrCreditAmount'=>$oFeeOrCreditAmount,
				 'ListingType'=>$ListingType,
				 'note'=>$oBuyerCheckoutMessage,
				 //'attribute'=>$attribute,
				 //'is_suffix'=>$is_suffix
				 )*/
				$obj_order_detail_data[] = array('orderDetailData' => $orderdata_detail,			
											'orderDetailExtenData' => $orderDetailExtAli
											);
			}
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
			
			if(!in_array($aliexpress_user, $taotao_account)){ //非淘代销订单， 需要进行拦截
				$insertOrder = AutoModel :: auto_contrast_intercept($insertOrder);
				/*$interceptInfo = CommonModel :: auto_contrast_intercept($insertOrder);
				//print_r($interceptInfo); exit;
				$insertOrder['orderData']['orderStatus'] = $interceptInfo['orderStatus'];
				$insertOrder['orderData']['orderType'] = $interceptInfo['orderType'];*/
			}
					
			//print_r($orderData); exit;
			if(OrderAddModel :: insertAllOrderRow($insertOrder)){
				//echo 'insert success!' . "\n";
				echo $log	=	"-----".date("Y-m-d H:i:s").", 新增订单{$order["id"]}成功\r\n";
			}else{
				echo $log	=	"-----".date("Y-m-d H:i:s").", 新增订单{$order["id"]}失败\r\n";
				echo OrderAddModel :: $errMsg;
			}
			@file_put_contents($logfile, $log, FILE_APPEND);
            //$omAvailableAct->commit();
     		$index++;
    	}
    }
    
    //非淘代销订单， 需要进行拦截
    /*if(!in_array($aliexpress_user, $taotao_account)){
    	$sql	= "select * from ebay_order as a where ebay_user='$user' and ebay_account = '$account' and ebay_combine!='1' and ebay_status = '595'  ";
    	$sql	= $dbConn->execute($sql);
    	$sql	= $dbConn->getResultArray($sql);
    	auto_contrast_intercept($sql);
    }*/
    
    $log	=	"End ".date("Y-m-d H:i:s")."-----------------此次共新增 ".$index." 条数据\n\n\n";
    @file_put_contents($logfile, $log, FILE_APPEND);
    
    //$error	=	var_export($dbConn->error);
    //echo $error;
    exit;
?>