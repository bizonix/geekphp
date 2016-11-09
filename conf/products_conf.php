<?php
/**
*功能：定义常用变量
*版本：2015-03-29
*作者：wcx
*拿来当作产品状态配置文件
*/

if (!defined('WEB_PATH')) exit();
//全局配置信息
return  array(
	'PRODUCTS_STATUS'	=> array(
		"1" => "在线",
		"2" => "暂时停售",
		"3" => "停售",
	),
	'PRODUCTS_ISNEW'	=> array(
		"0" => "老品",
		"1" => "新品",
	),
	
		
);

?>