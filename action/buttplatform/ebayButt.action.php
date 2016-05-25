<?php
/*
 * ebay平台对接接口
 * add by: linzhengxiang @date 20140618
 */
class EbayButtAct extends CheckAct{
	
	private $authorize = array();
	private $currentApi;
	public function __construct(){
		parent::__construct();
		F('xmlhandle');
		include_once WEB_PATH."lib/api/ebay/eBaySession.php";
	}
	
	public function setToken($account,$token){
		######################以后扩展到接口获取 start ######################
 		$siteID =0;
 		$production  = false;
 		$compatabilityLevel = 765;
		if(empty($account) || empty($token)){
			return false;
		}
		if(!is_array($token)){
			$token = json_decode($token,true);
		}
 		/*$devID		= "c979de22-fe99-4d93-b417-940c637d38bb";
 		$appID		= "Shenzhen-f583-48e8-95ed-0f88fabff4ee";
 		$certID		= "45c0312b-ed8d-4274-b037-1107e1d63d25";
 		$serverUrl	= "https://api.ebay.com/ws/api.dll";
 		$userToken 	= 'AgAAAA**AQAAAA**aAAAAA**MojnUQ**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AEkICjD5OKpQ6dj6x9nY+seQ**J+gBAA**AAMAAA**j/TkaXLF/w3zv+FnwuaFIedPpZ/Q7K45cB9aIzu3mWMLFY9h/wewKQh6AGmYBnYOGjAnDxkee8g0JCus8arGJU338tnJ8rxzEdGx9BrFVaRGI8+1vzZAYW04lu3PTlotvan0mIP6H2OzbrQ7871ob3N7KaqSBcDeYYQGLwbHX8n+rK26Dl8umlZQW8aKSNb2qk3ZFB3HqlnDk65WgbxUpQrQalcvA+J0sEoNO6ThIQNJttTmtVPsF4cx5lBJmx7peWrHJvcv6ABiD6QmtC78OAa/j/68iZ2mD+CDgU/OhlC17S2DpdzHTHpL8A2X88y1KSL7VRKpUB77MS+MgybSVrNkMkI4eeVktjkal+OFAHnXfnWOfevc8UJRqSMSeyBv54+hoi+llEpsqcrVBPxkMGbjoD3zv3wOpHb+NOSU/DKCXRP5qIc0rF4kqSL72MDHu4SJCA4Oc4mPrQwQ2grqIAwq675zsPC1Bt3TDrvyEtfNfBAiydQKrmv1h5TvbvSDAuvIkDNMJi7TtG0Kl7cJ/7SBO+RhQX0Xyp+PaXlfMEKAfubSSFIlwoiwivm+sg0YcB2TC8Fi35vkO3sFFkfzVTPE8NGTfJ9NZOnkToAzBMCSd3NoJ50ZNjCMGzaJqYJdnsmhxSzfDxmud48RT3e7QxYsqZrRflbMsAFIICt0U5EzuoD52DX8XrMdH9bUU9+Woy5fkYl8YG6x7QAQSl++CTcHKzFfwATbLHtAdJuhg9jJ30aPD97MM3HCnZv+16xO';*/
		######################以后扩展到接口获取  end  ######################
		/*$keyname    = WEB_PATH.'/conf/scripts/keys/ebay/keys_'.$account.'.php';
		if (file_exists($keyname)) {
			include $keyname;
		} else {
			exit ("未找对应的key文件!");
		}*/
		$this->authorize = array(	
									'userToken'				=>$token['userToken'],
									'devID'					=>$token['devID'],
									'appID'					=>$token['appID'],
									'certID'				=>$token['certID'],
									'serverUrl'				=>$token['serverUrl'],
									'compatabilityLevel'	=>$compatabilityLevel,
									'siteID'				=>$siteID,
									'account'				=>$account,
							);
	}
	
	/**
	 * 设置站点ID
	 * @param intval $id
	 * @author lzx
	 */
	public function setSiteId($id){
		$this->authorize['siteID'] = intval($id);
	}
	
	/**
	 * 根据开始和结束时间，抓取订单抓取号
	 * @param datetime $starttime
	 * @param datetime $endtime
	 * @return bool
	 * @author lzx
	 */
	public function spiderOrderId($starttime, $endtime){
		$OrderObject = F('ebay.package.GetOrders');
		$OrderObject->setRequestConfig($this->authorize);
		$page = 1;
		$hasmore = true;
		$simplelists = array();
		while ($hasmore){
			$receivelists = $OrderObject->getOrderIds($starttime, $endtime, $page);
			$receivelists = XML_unserialize($receivelists);
			if (!isset($receivelists['GetOrdersResponse']['Ack'])||$receivelists['GetOrdersResponse']['Ack']=='Failure'){
				self::$errMsg[10095] = get_promptmsg(10095);
				break;
			}
			if ($receivelists['GetOrdersResponse']['PaginationResult']['TotalNumberOfPages']<$page){
				self::$errMsg[10096] = get_promptmsg(10096, $page, $receivelists['GetOrdersResponse']['PaginationResult']['TotalNumberOfPages']);
				break;
			}
			$page++;
			$hasmore	= $receivelists['GetOrdersResponse']['HasMoreOrders']=='true' ? true : false;
			//modify by andy
			$orders = $receivelists['GetOrdersResponse']['OrderArray']['Order'];
			if(!empty($orders['OrderID'])){
				$orders = array($orders);
			}
			if(!empty($GLOBALS['debug_output_getorders_result'])){
				echo 'debug_output_getorders_result:<pre>';print_r($orders);exit;
			}
			foreach( $orders as $simpleorder){
				/*参考变量
				 * $orderid = $simpleorder['OrderID'];
				$eBayPaymentStatus = $simpleorder['CheckoutStatus']['eBayPaymentStatus'];
				$OrderStatus = $simpleorder['CheckoutStatus']['Status'];
				$PaidTime = $simpleorder['PaidTime'];
				$ShippedTime = isset($simpleorder['ShippedTime']) ? $simpleorder['ShippedTime'] : '';*/
				if(empty($GLOBALS['enable_shipped_download'])){
					if ($simpleorder['CheckoutStatus']['Status']!='Complete') {
						//echo "orderid:{$simpleorder['OrderID']}---->inComplete\n";
						continue;
					}
					if (!isset($simpleorder['PaidTime'])||empty($simpleorder['PaidTime'])){
						//echo "orderid:{$simpleorder['OrderID']}---->no pay\n";
						continue;
					}
					if (isset($simpleorder['ShippedTime'])&&!empty($simpleorder['ShippedTime'])){
						//echo "orderid:{$simpleorder['OrderID']}---->shipped\n";
						continue;
					}
					$hasmore = false;
				}
				
				/*//如果要抓取刷单的这里需要做修改
				if ($simpleorder['CheckoutStatus']['eBayPaymentStatus']!='NoPaymentFailure') {
					break;
				}*/
				$Transaction = $simpleorder['TransactionArray']['Transaction'];
				$recordNumber = isset($Transaction[0]) ? $Transaction[0]['ShippingDetails']['SellingManagerSalesRecordNumber'] : $Transaction['ShippingDetails']['SellingManagerSalesRecordNumber'];
				$simplelists[] = array('ebay_orderid'=>$simpleorder['OrderID'], 'ebay_account'=>$this->authorize['account'],'recordNumber'=>$recordNumber);
			}
		}
		return $simplelists;
	}
	
	/**
	 * 根据订单号抓取订单列表
	 * @param array $orderids
	 * @return array
	 * @author lzx
	 */
	public function spiderOrderLists($orderids){
		$OrderObject = F('ebay.package.GetOrders');

		$OrderObject->setRequestConfig($this->authorize);
		$receivelists = $OrderObject->getOrderLists($orderids);//if($_GET['test']){echo 'result:<pre>';print_r($receivelists);exit;}
		$receivelists = XML_unserialize($receivelists);
		return $receivelists;
	}
	/**测试
	 * 
	 */
	public function runOrigin($fun,$account,$param=''){
		$ebayApi = F('ebay.package.'.$fun);
		$this->currentApi = $ebayApi;
		$ebayApi->setTokenInfo($account);
		$fun = lcfirst($fun);
		if(method_exists($ebayApi, $fun)){
			$data = $ebayApi->$fun($param);
			$data = XML_unserialize($data);
			return $data;
		}
	}
	public function getOriginobj($fun,$param){
		$numargs = func_num_args()-1;
		$arg_list = func_get_args();
		$func = $arg_list[0];
        unset($arg_list[0]);
        if($numargs<=1){
        	$data = XML_unserialize($this->currentApi->$fun());
        }else{
			if ($numargs == 1) {
				$param1 = $arg_list[1];
		    }else{
		    	$param = array_values($arg_list);
		    }
		    $data = XML_unserialize($this->currentApi->$fun($param));
		}
		return $data;
	}

	/**
	 * 获取收款邮箱
	 * @param number $itemId 产品id
	 * @return string 收款邮箱
	 * @author czq
	 */
	public function getPayPalEmailAddress($itemId){
		$OrderItemObject 	= F('ebay.package.GetItem');
		$OrderItemObject->setRequestConfig($this->authorize);
		$paypalInfo			= $OrderItemObject->getPayPalEmailAddress($itemId);
		$paypalInfo = XML_unserialize($paypalInfo);
		
		$try = 1;
		while ($paypalInfo['GetItemResponse']['Ack'] == 'Failure' && $try<5){
			$paypalInfo			= $OrderItemObject->getPayPalEmailAddress($itemId);
			$paypalInfo = XML_unserialize($paypalInfo);
			$try++;
		}
		
		return isset($paypalInfo['GetItemResponse']['Item']['PayPalEmailAddress']) ? $paypalInfo['GetItemResponse']['Item']['PayPalEmailAddress'] : '';	
	}
	
	public function paypalRefund($refundInfo){
		$httpParsedResponseAr    = F('ebay.package.PaypalRefund');
		$httpParsedResponseAr->setRequestConfig($this->authorize);
		$ppRtnInfo    = $httpParsedResponseAr->curlRefund($refundInfo);
		return $ppRtnInfo;
	}
	/**
	 * 标记发货，未上传跟踪号
	 * @param array $trans
	 * @return boolean
	 * @author czq
	 */
	public function markOrderShipped($trans){
		$completeSaleObj = F('ebay.package.CompleteSale');
		$completeSaleObj->setRequestConfig($this->authorize);
		$resve = $completeSaleObj->markOrderShipped($trans);
		$resve = XML_unserialize($resve);
		return $resve;
		/*if($resve['CompleteSaleResponse']['ACK'] == 'Success'){
			return true;
		}
		return false;*/
	}
	
	/**
	 * 上传跟踪号
	 * @param array $trans
	 * @return  boolean
	 * @author czq
	 */
	public function uploadTrackNo($trans){
		$completeSaleObj = F('ebay.package.CompleteSale');
		$completeSaleObj->setRequestConfig($this->authorize);
		$resve = $completeSaleObj->update_order_shippingdetail_to_ebay($trans);
		$resve = XML_unserialize($resve);
		return $resve;
		/*if($resve['CompleteSaleResponse']['ACK'] == 'Success'){
			return true;
		}
		return false;*/
	}
	/**
	 * 获取配置信息
	 * @param array $trans
	 * @return  boolean
	 * @author czq
	 */
	public function geteBayDetails($detailname){
		$completeSaleObj = F('ebay.package.GeteBayDetails');
		$completeSaleObj->setRequestConfig($this->authorize);
		$resve = $completeSaleObj->geteBayDetails($detailname);
		return XML_unserialize($resve);
	}
	
}
?>	