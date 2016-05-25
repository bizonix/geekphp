<?php
	error_reporting(E_ALL);
	//define('REQUEST_URL','https://test.api.dresslink.com/api.php');
	define('REQUEST_URL','https://t.api.dresslink.com:444/api.php');
	define('DEV_ID',"cba7fa76-bd45-474a-8455-5852bacb51ad");
	define('APP_ID',"SHENZHEN-3d95-4c0c-a609-7f3201df721f");
	define('CERT_ID',"c7f4a4ed-7daa-48b9-87c4-a7d5d3c28eb9");
	
	
	#######Begin 订单详细接口########	 
	$data			=array();	
	$headers		=build_http_headers('order','getOrderDetails');
	$source_data	=array('DL00000001','DL00000002','DL00000003','DL00000004','DL00000005','DL00000006','DL00000007','DL00000008','DL00000009');	
	$pagination_data	=array( 'PerPage'=>2,
							'CurrPage'=>1);
	while(true){
		$data['pagination'] =$pagination_data;
		$data['data']		=$source_data;
		$return_data		=curl_request($headers,array('erp_data'=>json_encode($data)));
		
		$return_data		=json_decode($return_data,true);
		$HasMoreOrders		=$return_data['HasMoreOrders'];
		$CurrPage			=$return_data['PaginationResult']['CurrPage'];
		echo "<pre>";
		print_r($return_data);		
		if($HasMoreOrders==false||$HasMoreOrders==0)break;
		$pagination_data['CurrPage']=$CurrPage+1;	
	}
	
	#######End 订单详细接口########
	
		
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
?>