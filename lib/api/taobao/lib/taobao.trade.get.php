<?php

function taobaoTradeGet($url,$appSecret,$session,$appKey,$tid){
	$paramArr	=	array(
								'method' => 'taobao.trade.get',
							   'session' => $session,		
							 'timestamp' => date('Y-m-d H:i:s'),			
								'format' => 'json',				
							   'app_key' => $appKey,			
									 'v' => '2.0',				
							'sign_method'=> 'md5',				
								'fields' =>  'buyer_memo, seller_memo, alipay_no,alipay_id,buyer_message',  
								'tid'	=>	$tid
	);

	$sign		=	tmall_createSign($paramArr,$appSecret);
	$strParam	=	tmall_createStrParam($paramArr);
	$strParam	.=	'sign='.$sign;
	$urls		=	$url.$strParam;
						
	$cnt	=	0;	
	while($cnt < 3 && ($result=@tmall_vita_get_url_content($urls))===FALSE) $cnt++;
	$trade_data	=	json_decode($result, true);
	return $trade_data;
}
?>