<?php
error_reporting(E_ALL);
include_once "/data/scripts/ebay_order_cron_job/ebay_order_cron_config.php";
ini_set('max_execution_time', 1800);
header("Content-type: text/html; charset=utf-8");
//require_once "../scripts.comm.php";
//require_once WEB_PATH_LIB_SDK_DL."CNCommenScript.php";
require_once "/data/scripts/ebay_order_cron_job/CN_DL/sdk/DL/DLCommenScript.php";
$ss = "SELECT a.ebay_id,b.recordnumber FROM ebay_mark_shipping as a left join ebay_order as b on a.ebay_id=b.ebay_id WHERE a.ebay_account='dresslink.com' AND a.status=0";
$ss = $dbcon->execute($ss);
$ss = $dbcon->getResultArray($ss);
if(!$ss){
	echo "没有订单需要标记发货！\n";
	exit;
}
foreach($ss as $key=>$value){
	$data[$value['recordnumber']] = '3';
}
//$data = array('DL00000265'=>'3','DL00000266'=>'3','DL00000267'=>'3');
//print_r($ss);exit;
$msg = DLOrderStatus($data);
//print_r($msg);echo "sdf";
$msg = json_decode($msg,true);

if($msg['ACK']=="Success"){
	foreach($ss as $order){
		$sql = "UPATE ebay_mark_shipping SET status=1  WHERE ebay_id={$order['ebay_id']}";
		$sql = $dbcon->execute($sql);
		if(!$sql){
			echo "订单更新".$order['ebay_id']."ebay_mark_shipping表失败！\n";
		}
	}
}else{
	echo "标记发货失败！\n";
	print_r($msg['Errors']);
}
?>