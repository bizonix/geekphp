<?php
/*
 * 订单系统常量
 */
define('OVERWEIGHT', 1.85);



/*    订单状态常量定义     */
define('EXCEPTION', 800);			//异常订单
define('EXCEPTION_WHANDEL', 801);	//异常待处理

define('SHIPMENTS', 900);			//待发货
define('SHIPMENTS_WPRINT', 901);			//待发货 -- 待打印
define('SHIPMENTS_HASPRINT', 902);			//待发货 -- 已打印
define('SHIPMENTS_WGETGOODS', 903);			//待发货 -- 待配货

define('CANCELED', 400);		//取消订单
define('EXP_SAVE_PATH', "/data/web/order.valsun.cn/html/label_images/");

/*运输方式常量定义*/
define('TRANSPORT_EUB','6');  				//EUB运输方式

/*账号平台常量定义*/
define('RESERVED_PLATFORM','1,5,8,10,16');   //预留的平台