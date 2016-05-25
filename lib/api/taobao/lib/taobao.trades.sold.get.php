<?php
//	by winday 2013-4-16
/*--------------------------------------------------------
 *	taobao.trades.sold.get
 *	按条件查询订单列表
 */

/**********************************
 *
 *
 *
 *
 */
function taobaoTradesSoldGet($url,$session,$appSecret,$appKey,$status,$page_no=1,$page_size=100,
		$start_created="",$end_created="",
		$buyer_nick="",$type="",$rate_status="",$tag="")
{

	$paramArr	=	array(
				'method' => 'taobao.trades.sold.get',  
			   'session' => $session,			
			 'timestamp' => date('Y-m-d H:i:s'),			
				'format' => 'json',				
			   'app_key' => $appKey,					
					 'v' => '2.0',					   
			'sign_method'=> 'md5',						
				'fields' =>  'seller_nick,buyer_nick,title,type,refund_status,created,iid,price,pic_path,num,tid,buyer_message,sid,shipping_type,alipay_no,payment,discount_fee,adjust_fee,snapshot_url,status,seller_rate,buyer_rate,buyer_memo,seller_memo,pay_time,end_time,modified,buyer_obtain_point_fee,point_fee,real_point_fee,total_fee,post_fee,buyer_alipay_no,receiver_name,receiver_state,receiver_city,receiver_district,receiver_address,receiver_zip,receiver_mobile,receiver_phone,consign_time,buyer_email,commission_fee,seller_alipay_no,seller_mobile,seller_phone,seller_name,seller_email,available_confirm_fee,has_postFee,received_payment,cod_fee,timeout_action_time,orders,sku_id,sku_properties_name,item_meal_name,outer_iid,outer_sku_id,buyer_alipay_no,buyer_email',  


				'status' => $status,			//交易状态
			'buyer_nick' => $buyer_nick,		//买家淘宝昵称
				  'type' => $type,				//交易类型列表
		   'rate_status' => $rate_status,		//评价状态
				   'tag' => $tag,				//卖家对交易的自定义分组标签，目前可选值为：time_card（点卡软件代充），fee_card（话费软件代充） 
			   'page_no' => $page_no,			//页码
			  'page_size'=> $page_size			//每页条数。取值范围:大于零的整数; 默认值:40;最大值:100 
	);

	if(!empty($start_created) && !empty($end_created)){
		$paramArr['start_created']	=		$start_created;
		$paramArr['end_created']	=		$end_created;
	}
	$sign		=	tmall_createSign($paramArr,$appSecret);
	$strParam	=	tmall_createStrParam($paramArr);
	$strParam	.=	'sign='.$sign;
	//构造Url
	$urls		=	$url.$strParam;
		
	//连接超时自动重试（批量获取订单号）
	$cnt	=	0;	
	while($cnt < 3 && ($result=@tmall_vita_get_url_content($urls))===FALSE) $cnt++;
	$json_data	=	json_decode($result,true);
	return $json_data;

}
?>