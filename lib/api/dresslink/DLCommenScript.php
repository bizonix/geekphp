<?php

	//1、内网测试环境
	//define('REQUEST_URL','https://test.api.dresslink.com/api.php');
	//define('CERT_ID',"sandbox-c7f4a4ed-7daa-48b9-87c4-a7d5d3c28eb9");

	//2、外网t测试环境
	//define('REQUEST_URL','https://t.api.dresslink.com/api.php');
	//define('CERT_ID',"sandboxt-c7f4a4ed-7daa-48b9-87c4-a7d5d3c28eb9");

	//3、外网正式环境：
	define('REQUEST_URL','https://api.dresslink.com/api.php');
	define('CERT_ID',"c7f4a4ed-7daa-48b9-87c4-a7d5d3c28eb9");


	define('DEV_ID',"cba7fa76-bd45-474a-8455-5852bacb51ad");
	define('APP_ID',"SHENZHEN-3d95-4c0c-a609-7f3201df721f");

	
	
	/*
	* @auther heminghua 
	* 获取DL平台的订单
	* 参数无 
	* 返回JSON数据
	*/
	function getDLOrders(){
		$headers	=build_http_headers('order','getOrderIDs');
		$return_data=curl_request($headers,null);
		return $return_data;
	}
	/*
	* @auther heminghua 
	* 获取DL平台的订单明细信息
	* 参数$source_data DL 平台订单号数组 
	* 返回JSON数据
	*/
	function getDLOrderDetails($source_data){
		$data			=array();	
		$headers		=build_http_headers('order','getOrderDetails');
		//$source_data	=array('DL00000001','DL00000002','DL00000003','DL00000004','DL00000005','DL00000006','DL00000007','DL00000008','DL00000009');	
		$pagination_data	=array( 'PerPage'=>30,
								'CurrPage'=>1);
		while(true){
			$data['pagination'] =$pagination_data;
			$data['data']		=$source_data;
			$return_data		=curl_request($headers,array('erp_data'=>json_encode($data)));
			
			$return_data		=json_decode($return_data,true);
			$return[]				= $return_data;
			$HasMoreOrders		=$return_data['HasMoreOrders'];
			$CurrPage			=$return_data['PaginationResult']['CurrPage'];
			//echo "<pre>";
			//print_r($return_data);		
			if($HasMoreOrders==false||$HasMoreOrders==0)break;
			$pagination_data['CurrPage']=$CurrPage+1;	
		}
		return $return;
	}
	/*
	* @auther heminghua 
	* 获取DL平台的订单状态接口
	* 参数$source_data DL 平台订单号数组 
	* 返回JSON数据
	*/	
	function DLOrderStatus($source_data){
		$headers	=build_http_headers('order','getOrderStatus');
		//$source_data=array('DL00013250'=>'4','DL00013269'=>'3');
		$data		=json_encode($source_data);	
		$list_data	=array('data'=>$data);	
		$return_data=curl_request($headers,$list_data);
		return $return_data;
	}

	function adjustSkuPrice($source_data){
		$headers	=build_http_headers('item','price');	
		//$source_data=array('7640'=>'8.49','234234234'=>'23.6','16117_P'=>'6.9','3544_W_M'=>'1.568');
		$data		=json_encode($source_data);
		$list_data	=array('data'=>$data);
		
		$return_data=curl_request($headers,$list_data);
	}
	function adjustSkuWeight($source_data){
		$headers	=build_http_headers('item','weight');
		//$source_data=array('16187'=>'300','234234234'=>'23.6','3544_B_XL'=>'12.894','16036'=>'151');
		$data		=json_encode($source_data);
		$list_data	=array('data'=>$data);	
		$return_data=curl_request($headers,$list_data);
		return $return_data;
	}
	
	function adjustSkuStock($source_data){
		$headers	=build_http_headers('item','stock');
		//$source_data=array('15836_DBL'=>'1','15836_R'=>'1','15836_G'=>'1');
		//$source_data=array('15836'=>'T');
		$data		=json_encode($source_data);
		$list_data	=array('data'=>$data);
		
		$return_data=curl_request($headers,$list_data);
		return $return_data;
	}
	
	function curl_request($headers,$list_data){
		$ch			=curl_init();
		curl_setopt($ch, CURLOPT_URL, REQUEST_URL);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $list_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		
		$response   = curl_exec($ch);
		
		var_dump(curl_error($ch));
		curl_close($ch);
		return $response;
	}
	

	function build_http_headers($service,$action){
		return	array (
			//set the keys
			'X-DLCN-API-DEV-NAME: '.DEV_ID,
			'X-DLCN-API-APP-NAME: '.APP_ID,
			'X-DLCN-API-CERT-NAME: '.CERT_ID,
			//the name of the call we are requesting
			'X-DLCN-API-SERVICE-NAME: '.$service,	
			'X-DLCN-API-CALL-NAME: '.$action,	
		);
	}
	function carrier($ebay_carrier){
		if($ebay_carrier == 'dhlfixed' || $ebay_carrier == 'dhlperweight' || $ebay_carrier == 'dhl'){
			return 'DHL';
		}else if ($ebay_carrier=='fedex'){
			return 'FedEx';
		}else if ($ebay_carrier=='chinapostreg'){
			return '中国邮政挂号';
		}else if ($ebay_carrier=='chinapost' || $ebay_carrier=='Chinapost'){
			return '中国邮政平邮';
		}else if($ebay_carrier=='ems'){
			return 'EMS';
		}else if($ebay_carrier=='emszones'){
			return 'EMS';
		}else if($ebay_carrier=='sfexpress'){
			return '顺丰快递';
		}else if($ebay_carrier=='stoexpress'){
			return '申通快递';
		}else{
			return $ebay_carrier;
		}
	}
?>