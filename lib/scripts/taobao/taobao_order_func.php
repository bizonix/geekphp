<?php
	//判断评价状态
	function getRate($str1,$str2)
	{	
		if($str1=='true' && $str2=='true')
		   $getRate = "双方已评";
		else if($str1=='false' && $str2=='true')
		   $getRate = "对方已评价";					
		else if($str1=='true' && $str2=='false')
		   $getRate = "我已评价";	
		else
			$getRate = "双方未评价";
		return $getRate;
	}
	
	//判断交易类型	
	function getTypes($str)
	{	
		if($str=='fixed')
		   $getType = "一口价";
		else if($str=='auction')
		   $getType = "拍卖";
		else if($str=='guarantee_trade')
		   $getType = "一口价、拍卖";		   
		else if($str=='auto_delivery')
		    $getType = "自动发货";
		else if($str=='independent_simple_trade')
		    $getType = "旺店入门版交易";
		else if($str=='independent_shop_trade')
		    $getType = "旺店标准版交易";
			
		else if($str=='ec')
		    $getType = "直冲";
		else if($str=='cod')
		    $getType = "货到付款";
		else if($str=='fenxiao')
		    $getType = "分销";
		else if($str=='game_equipment')
		    $getType = "游戏装备";
		else if($str=='shopex_trade')
		    $getType = "ShopEX交易";
		else if($str=='netcn_trade')
		    $getType = "万网交易";
		else if($str=='external_trade')
		    $getType = "统一外部交易";																											

		return $getType;
	}	
	
	//判断交易状态
	function getStatus($str)
	{	
		if($str=='WAIT_BUYER_PAY')
		   $getStatus = "等待买家付款";
		else if($str=='WAIT_SELLER_SEND_GOODS')
		   $getStatus = "买家已付款,等待卖家发货";
		else if($str=='WAIT_BUYER_CONFIRM_GOODS')
		   $getStatus = "卖家已发货,等待买家确认收货";		   
		else if($str=='TRADE_BUYER_SIGNED')
		    $getStatus = "买家已签收,货到付款专用";
		else if($str=='TRADE_FINISHED')
		    $getStatus = "交易成功";
		else if($str=='TRADE_CLOSED')
		    $getStatus = "交易关闭";						
		else
			$getStatus = "交易被淘宝关闭";
		return $getStatus;
	}
	//获取数据兼容file_get_contents与curl
	function tmall_vita_get_url_content($url) {
		if(function_exists('file_get_contents')) {
			$file_contents = file_get_contents($url);
		} else {
			$ch = curl_init();
			$timeout = 2; 
			curl_setopt ($ch, CURLOPT_URL, $url);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$file_contents = curl_exec($ch);
			curl_close($ch);
		}
		return $file_contents;
	}
	
	//签名函数 
	function tmall_createSign ($paramArr) { 
	    global $appSecret; 
	    $sign = $appSecret; 
	    ksort($paramArr); 
	    foreach ($paramArr as $key => $val) { 
	       if ($key !='' && $val !='') { 
	           $sign .= $key.$val; 
	       } 
	    } 
	    $sign = strtoupper(md5($sign.$appSecret));
	    return $sign; 
	}

	//组参函数 
	function tmall_createStrParam ($paramArr) { 
	    $strParam = ''; 
	    foreach ($paramArr as $key => $val) { 
	       if ($key != '' && $val !='') { 
	           $strParam .= $key.'='.urlencode($val).'&'; 
	       } 
	    } 
	    return $strParam; 
	} 

	//解析xml函数
	function tmall_getXmlData ($strXml) {
		$pos = strpos($strXml, 'xml');
		if ($pos) {
			$xmlCode=simplexml_load_string($strXml,'SimpleXMLElement', LIBXML_NOCDATA);
			$arrayCode=tmall_get_object_vars_final($xmlCode);
			return $arrayCode ;
		} else {
			return '';
		}
	}
	
	function tmall_get_object_vars_final($obj){
		if(is_object($obj)){
			$obj=get_object_vars($obj);
		}
		if(is_array($obj)){
			foreach ($obj as $key=>$value){
				$obj[$key]=tmall_get_object_vars_final($value);
			}
		}
		return $obj;
	}


	
	/********************************************
	 *	 获取快递code信息
	 *	 @param  $Logistic string  物流公司名(erp系统里的)
	 */

	function getLogisticCode($Logistic){
		$code	=	"";
		switch($Logistic){
			case "EMS":
					$code	=	"EMS";
					break;
			case "中国邮政平邮":
					$code	=	"POST";
					break;
			case "FedEx":
					$code	=	"FEDEX";
					break;
			case "顺丰快递":
					$code	=	"SF";
					break;
			case "韵达快递":
					$code	=	"YUNDA";
					break;
			case "申通快递":
					$code	=	"STO";
					break;
			case "中通快递":
					$code	=	"ZTO";
					break;
			case "圆通快递":
					$code	=	"YTO";
					break;
			case "天天快递":
					$code	=	"TTKDEX";
					break;
			case "中国邮政挂号":
					$code	=	"POSTB";
					break;
			default:
					$code	=	"";
					break;
		}
		return $code;
	}
	
	//获取账号id、平台id
	function get_account_id($accountName) {
		$info = array();
		$omAvailableAct = new OmAvailableAct();                
		$where  = " WHERE account = '{$accountName}' AND is_delete = 0 ";
		$res	=  $omAvailableAct->act_getTNameList('om_account', 'id,platformId', $where);

		$info = array(
			'accountid'  => $res[0]['id'],
			'platformid' => $res[0]['platformId'],
		);
		return $info;          
	}
?>