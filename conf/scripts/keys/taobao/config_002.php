<?php
/***************************************************
 *	淘宝账号配置信息
 *	by zhongyantai	2013-4-15
 *	哲果旗舰店(taobao店)
 */
//fresh_token	"620092718b067545f2e88b9ef432526662acefa6c5d674b1044743567"

$session		=	"620292719da932737f548abc901fc3a3931ZZ666cb3f7951044743567";		//access token
$start_created	=	date("Y-m-d H:i:s",strtotime("-1 day"));	//查询交易创建时间开始
$end_created	=	date("Y-m-d H:i:s");						//查询交易创建时间结束
$status			=	"WAIT_SELLER_SEND_GOODS";		//交易状态	  WAIT_SELLER_SEND_GOODS  WAIT_BUYER_CONFIRM_GOODS
$buyer_nick		=	"";						//买家淘宝昵称 
$type			=	"";						//交易类型列表
$rate_status	=	"";						//评价状态
$tag			=	""; 
$page_no		=	1; 
$page_size		=	50; 

$account		=	"zeagoo";		
$url			=	'http://gw.api.taobao.com/router/rest?';  //正式环境提交URL
$appKey			=	'21460636'; //填写自己申请的AppKey
$appSecret		=	'df0cb97ac64f603c799082dde8966c6b'; //填写自己申请的$appSecret
$defalut_carrier=	'韵达快递';	

?>