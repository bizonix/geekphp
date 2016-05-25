<?php
/**
* @name order_constrast_intercept.php
* @author Herman.Xi (席慧超)
* @version 1.0
* @modify 2012-09-18 09:44:00
* @last modified Herman.Xi
* @last modified date 2012-12-15
* 自动脚本,对比拦截 658,659,660,661四种文件夹状态进入待发货状态
**/
set_time_limit(0);
//脚本参数检验
#########全局变量设置########	
//date_default_timezone_set('Asia/Chongqing');   
$detailLevel = 0;	
$storeId	= "1";
$pagesize	= 20;//每页显示的数据条目数
$mctime		= time();                    	
$cc			= $mctime;
$nowtime	= date("Y-m-d H:i:s",$cc);
$nowd		= date("Y-m-d",$cc);
#################以下时间范围用于测试#############
if(!defined('WEB_PATH')){
	define("WEB_PATH","/data/web/order.valsun.cn/");
}
require_once WEB_PATH."crontab/scripts.comm.php";
require_once WEB_PATH_CONF_SCRIPTS."script.ebay.config.php";
$tableName = "om_unshipped_order";
$ordersql 	 =  'SELECT         DISTINCT a.id 
				FROM 			'.$tableName.' AS a 
				LEFT JOIN       '.$tableName.'_detail AS b 
				ON 			    b.omOrderId = a.id
				WHERE			a.orderStatus IN ('.C('STATEOUTOFSTOCK').','.C('STATEPENDING').')
				AND				a.orderType IN ('.C('STATEOUTOFSTOCK_PO').','.C('STATEOUTOFSTOCK_AO').','.C('STATEPENDING_HASARRIVED').') 
				AND 			a.is_delete = 0
				AND 			a.storeId= '.$storeId."

				";
echo $ordersql; echo "<br>";//				AND 			b.sku IN ('3544_B_M')
$query	     =	$dbConn->query($ordersql);	
$orders      =	$dbConn->fetch_array_all($query);

$chunk_orders = array_chunk($orders,1000);
unset($orders);
//echo count($orders);
$time_start=time();
echo "\n=====[".date('Y-m-d H:i:s',$time_start)."]系统【缺货,合并包裹缺货,自动拦截】共有（".count($orders)."）个订单需要处理\n";

foreach($chunk_orders as $value_orders){
	if(!empty($value_orders)){
		foreach($value_orders as $value){
			BaseModel :: begin(); //开始事务
			$where = " WHERE id = ".$value['id'];
			$orderData = OrderindexModel::showOrderList($tableName, $where);
			//var_dump($orderData[$value['id']]);
			$returnStatus0 = array('orderStatus'=>$orderData[$value['id']]['orderData']['orderStatus'], 'orderType'=>$orderData[$value['id']]['orderData']['orderType']);
			$returnStatus = CommonModel::auto_contrast_intercept($orderData[$value['id']]);//自动拦截核心函数
			if($returnStatus0 != $returnStatus){
				if(OrderindexModel::updateOrder($tableName,$returnStatus,$where)){
					//echo "\n=====同步的订单状态成功======\n";
					$ProductStatus = new ProductStatus();
					if(!$ProductStatus->updateSkuStatusByOrderStatus(array($value['id']), $returnStatus['orderStatus'], $returnStatus['orderType'])){
						BaseModel :: rollback();
					}
				}else{
					BaseModel :: rollback();	
				}
			}
			BaseModel :: commit();
			BaseModel :: autoCommit();
		}
	}else{
		echo "\n=====没有同步的订单======\n";
	}
}

$time_end=time();
echo "\n=====[耗时:".ceil(($time_end-$time_start)/60)."分钟]====\n";
echo "\n=====[".date('Y-m-d H:i:s',$time_end)."]系统【缺货,合并包裹缺货,自动拦截】订单结束\n";
exit;
//sleep(10);//执行完操作之后开始休眠10秒钟,同步数据。。。。。