<?php
/**
*功能：定义常用变量
*版本：2014-12-29
*作者：wcx
*拿来当作订单状态配置文件
*/

if (!defined('WEB_PATH')) exit();
//全局配置信息
return  array(
	'VALSUN_TOKEN'	=> array(
		
	),
	'VALSUN_SHIPPINGTYPE'	=> array(
			'China Post Ordinary Small Packet Plus'		=> 'CNPSS',
			'China Post Registered Air Mail'			=> 'CNPSR',
			'Singapore Post'							=> 'CNPSR',
			'HongKong Post Air Mail'					=> 'CNPSR',
			'ePacket'									=> 'CNPTE',
			'DHL'										=> 'CNDHL',
			'Fedex IP'									=> 'FEDEX',
	),

	VALSUN_DE_PLATFORM		=> array(
		"1"		=> 'ebay',
		"2"		=> 'smt',
		"3"		=> 'amazon',
		"4"		=> 'wish',
	),
		
);

?>