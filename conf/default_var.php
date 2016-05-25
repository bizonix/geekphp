<?php
/**
*功能：定义常用变量
*版本：2013-05-08
*作者：冯赛明
*LastModified by Herman.Xi @20131110
*拿来当作订单状态配置文件
*/

if (!defined('WEB_PATH')) exit();
//全局配置信息
return  array(
	"STATEPENDING"              			=>  100, //待处理
	"STATEPENDING_INITIAL"         			=>  '000', //待处理(初始值)
	"STATEPENDING_CONV"         			=>  101, //待处理(常规)
	"STATEPENDING_MSG"          			=>  102, //待处理(有留言)
	"STATEPENDING_OW"           			=>  103, //待处理(超重)
	"STATEPENDING_BL"           			=>  104, //待处理(黑名单)
	"STATEPENDING_RO"           			=>  105, //待处理(重复订单)
	"STATEPENDING_CONPACK"                  =>  106, //待处理(合并包裹)
	"STATEPENDING_EXCPAY"                   =>  107, //待处理(异常收款地址)
	"STATEPENDING_TAOBAOSCALP"              =>  108, //待处理(淘宝刷单)
	"STATEPENDING_OWDONE"           		=>  109, //超重订单(已处理)
	"STATEPENDING_HASARRIVED"           	=>  110, //快递订单
	"STATEPENDING_OUTSTOCKPRINT"           	=>  170, //缺货需打印
	"STATEPENDING_CONSIGNMENT"           	=>  180, //淘代销订单待处理
	"STATEPENDING_LYNXPEND"                 =>  700, //待处理(待审核Tm)
	"STATESENDTEMP"                 		=>  500, //暂不寄
	"STATEPENDING_APPEXC"                   =>  140, //申请发货异常
	"STATEPENDING_APPEUBEXC"                =>  725, //申请EUB异常
	//"STATEPENDING_LYNXPEND"               =>  700, //待处理(待审核Tm)
	//"STATEPENDING_LYNXPEND"               =>  700, //待处理(待审核Tm)
	
	#################海外仓###########################
	"STATEPENDING_OS"                  		=>  911, //海外仓
	"STATEPENDING_OVERSEA"                  =>  115, //海外仓待处理
	"STATEOUTOFSTOCK_OVERSEA"               =>  303, //缺货(海外仓)
	"STATEOUTOFSTOCK_REOS"               	=>  926, //海外仓重复打印
	"STATEOUTOFSTOCK_OSPR"               	=>  910, //海外仓(已打印)
	"STATEOUTOFSTOCK_OSBE"               	=>  916, //海外仓(待打印)
	"STATEOUTOFSTOCK_OSSYNC"               	=>  917, //海外仓(已同步)
	"STATEOUTOFSTOCK_OSPO"               	=>  2004,//EUB海外仓
	"STATEOUTOFSTOCK_OSCAN"               	=>  925, //海外仓取消交易
	"STATEOUTOFSTOCK_OSSEN"               	=>  924, //海外仓暂不寄
	"STATEOUTOFSTOCK_OSPICK"               	=>  923, //海外仓上门取件
	"STATEOUTOFSTOCK_OSDOM"               	=>  922, //海外仓国内发货
	"STATEOUTOFSTOCK_OSSHIPPED"             =>  921, //海外仓已发货
	"STATEOUTOFSTOCK_OSBEPICK"              =>  920, //海外仓已配货
	"STATEOUTOFSTOCK_STOCK"               	=>  303, //海外仓缺货
	"STATEOUTOFSTOCK_LOCALPICKUP"           =>  928, //海外仓local pickup
	
	"STATEOVERSIZEDORDERS"           		=>  200, //超大订单
	"STATEOVERSIZEDORDERS_CONFIRM"   		=>  201, //超大订单(超大订单待确认)
	"STATEOVERSIZEDORDERS_PEND"      		=>  202, //超大订单(待审核)
	"STATEOVERSIZEDORDERS_PA"        		=>  203, //超大订单(部分审核通过)
	"STATEOVERSIZEDORDERS_TA"        		=>  204, //超大订单(审核通过)
	"STATEOVERSIZEDORDERS_WB"       		=>  205, //超大订单(被拦截)
	
	"STATEOUTOFSTOCK"                 		=>  300, //缺货拦截
	"STATEOUTOFSTOCK_PO"                	=>  301, //缺货(部分缺货拦截)
	"STATEOUTOFSTOCK_AO"                	=>  302, //缺货(全部缺货拦截)
	"STATEOUTOFSTOCK_ABNORMAL"              =>  304, //异常缺货
	
	"STATECANCELTRAN"                 		=>  400, //回收站
	
	"STATEBUJI"                 			=>  550, //补寄
	"STATEBUJI_PEND"                 		=>  551, //补寄（未处理）
	"STATEBUJI_DONE"                 		=>  552, //补寄（已处理）
	
	"STATEREFUND"                 			=>  660, //待退款
	"STATEREFUND_PEND"                 		=>  661, //退款待处理
	"STATEREFUND_DONE"                 		=>  662, //退款已处理
	"STATEREFUND_OUTSTOCK"                 	=>  663, //缺货需退款
	
	"STATESTOCKEXCEPTION"                 	=>  770, //库存异常
	"STATESTOCKEXCEPTION_OUTSTOCK"          =>  771, //缺货
	
	"STATEINTERCEPTSHIP"                 	=>  800, //新系统异常
	"STATEINTERCEPTSHIP_PEND"               =>  801, //异常(未处理)
	"STATEINTERCEPTSHIP_DONE"               =>  802, //异常(已处理)
	"STATERECYCLE"                 			=>  600, //废弃订单
	"STATECANCELTRAN_PENDING"               =>  601, //取消交易
	
	"STATESHIPPED"		        			=>	900, //仓库发货
	"STATESHIPPED_PRINTPEND"				=>	901, //待发货-待打印
	"STATESHIPPED_PRINTED"					=>	902, //待发货-已打印
	"STATESHIPPED_BEPICKING"				=>	903, //待发货-待配货
	"STATESHIPPED_PENDREVIEW"				=>	904, //待发货-待复核
	"STATESHIPPED_BEPACKAGED"				=>	905, //待发货-待包装
	"STATESHIPPED_BEWEIGHED"				=>	906, //待发货-待称重
	"STATESHIPPED_APPLYPRINT"				=>	990, //已申请打印
	"STATEHASSHIPPED_CONV"		        	=>	21, //已发货(常规)
	
	"STATEHASSHIPPED"		        		=>	2, //已发货
	
	"STATESYNCINTERCEPT"                 	=>  220, //同步拦截
	"STATESYNCINTERCEPT_AB"                 =>  221, //同步异常
);

?>