<?php
//	by winday 2013-5-17
/*--------------------------------------------------------
 *	taobao.logistics.companies.get
 *	查询淘宝支持的快递公司信息
 */


function taobaoLogisticsCompaniesGet($url,$session,$appSecret,$appKey)
{

	$paramArr	=	array(
				'method' => 'taobao.logistics.companies.get',  
			   'session' => $session,			
			 'timestamp' => date('Y-m-d H:i:s'),			
				'format' => 'json',				
			   'app_key' => $appKey,					
					 'v' => '2.0',					   
			'sign_method'=> 'md5',						
				'fields' =>  'id,code,name,reg_mail_no',  
	);

	$sign		=	tmall_createSign($paramArr,$appSecret);
	$strParam	=	tmall_createStrParam($paramArr);
	$strParam	.=	'sign='.$sign;
	$urls		=	$url.$strParam;

	$cnt	=	0;	
	while($cnt < 3 && ($result=@tmall_vita_get_url_content($urls))===FALSE) $cnt++;
	$json_data	=	json_decode($result,true);
	return $json_data;

}
?>