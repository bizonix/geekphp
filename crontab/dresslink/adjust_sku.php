<?php
error_reporting(E_ALL);
ini_set('max_execution_time', 1800);
if(!defined('WEB_PATH')){
	define("WEB_PATH","/data/web/order.valsun.cn/");
}
require_once WEB_PATH."crontab/scripts.comm.php";
require_once WEB_PATH_CONF_SCRIPTS."script.ebay.config.php";
require_once WEB_PATH_LIB_SDK."dresslink/DLCommenScript.php";
echo "\r\r调用DL接口,时间".date("Y-m-d H:i:s")."\r\n";

$ss = "SELECT * FROM om_adjust_sku WHERE DL_status=0 and type=1 and is_exception=0";
$ss = $dbConn->query($ss);
$ss = $dbConn->fetch_array_all($ss);
$sku_stock = array();
$sku_price = array();
$sku_weight = array();
$sku_cubeweightcost = array();
foreach($ss as $key=>$value){
	if(preg_match("/^\W+$/",$value['sku'])){
		$tt = "UPDATE om_adjust_sku SET is_exception=1 WHERE sku='{$value['sku']}'";
		$tt = $dbConn->query($tt);
		continue;
	}
	if(strpos($value['sku'],'.') || $value['adjustvalue']==""){
		$tt = "UPDATE om_adjust_sku SET is_exception=1 WHERE  id={$value['id']}";
		$tt = $dbConn->query($tt);
		continue;
	}
	if($value['type']==1){
		$sku_stock[trim($value['sku'])] = $value['adjustvalue'];
	}
}
/********更新库存**********/

//$sku_stock = array('2011_M'=>'1','2011_XL'=>'1','14508_BL'=>'1');
//print_r($sku_stock);exit;
if(!empty($sku_stock)){
	$info = adjustSkuStock($sku_stock);
	$info = json_decode($info,true);
	print_r($info);
	if($info['ACK'] == "Success"){
		print_r($sku_stock);
		foreach($ss as $val){
			//if($val{'type'}==1&&$val['DL_status']==0){
				$sql = "UPDATE om_adjust_sku SET DL_status=1 WHERE id='{$val['id']}'";
				$sql = $dbConn->query($sql);
				if(!$sql){
					echo "更新om_adjust_sku失败！id:".$val['id']."\r\n";
				}	
			//}
		}
	}else{
		echo "调用库存接口出错了，赶紧查一下！出错信息：";
		print_r($info['Errors']);
	}
}else{
	echo "无料号需要更新库存\r\n";
}

?>