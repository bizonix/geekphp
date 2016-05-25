<?php
/*****************************************************
 *	标记发货功能处理
 *	by winday	
 *	2013-5-17
 */

function taobaoLogisticsOfflineSend($url, $sessions, $appKey, $appSecret,  $recordnumber, $company_code, $tracknumber){

	$paramArr = array(

	    	'method' => 'taobao.logistics.offline.send', 
		   'session' => $sessions, 
	     'timestamp' => date('Y-m-d H:i:s'),			
		    'format' => 'json', 
    	   'app_key' => $appKey, 			
	    		 'v' => '2.0',  	   
		'sign_method'=> 'md5',
		       'tid' => $recordnumber,  
	  'company_code' => $company_code, 
	 	   'out_sid' => $tracknumber

	);
	$sign		= tmall_createSign($paramArr,$appSecret);
	$strParam	= tmall_createStrParam($paramArr);
	$strParam .= 'sign='.$sign;
	$urls	=	$url.$strParam;
	
	$cnt	=	0;	
	while($cnt < 3 && ($result=@tmall_vita_get_url_content($urls))===FALSE) $cnt++;
	$json_data	=	json_decode($result,true);
	return $json_data;

}	
?>
