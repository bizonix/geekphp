<?php
error_reporting(E_ALL);
ini_set('max_execution_time', 1800);
if(!defined('WEB_PATH')){
	define("WEB_PATH","/data/web/order.valsun.cn/");
}
require_once WEB_PATH."crontab/scripts.comm.php";
require_once WEB_PATH_CONF_SCRIPTS."script.ebay.config.php";
require_once WEB_PATH_LIB_SDK."cndirect/CNCommenScript.php";
echo "调用CN 新接口,时间".date("Y-m-d H:i:s")."\r\n";
$ss = "SELECT * FROM om_adjust_sku WHERE CN_status=0 and type=5 and is_exception=0 order by createdtime ASC";
$ss = $dbConn->query($ss);
$ss = $dbConn->fetch_array_all($ss);
$sku_stock = array();
$sku_price = array();
$sku_weight = array();
$sku_cubeweightcost = array();
foreach($ss as $key=>$value){
	if(strpos($value['sku'],'.')){
		$tt = "UPDATE om_adjust_sku SET is_exception=1 WHERE sku='{$value['sku']}'";
		$tt = $dbConn->query($tt);
		continue;
	}
	
	if($value['type']==5){
		$data = explode(",",$value['adjustvalue']);
		$is_exception = false;
		$datainfo = explode("*",$data[0]);
		if(count($data) !=4){
			$is_exception = true;
			continue;
		}
		if(count($datainfo) !=3){
			$is_exception = true;
			continue;
		}
		$cube = round($datainfo[0],2)."cm*".round($datainfo[1],2)."cm*".round($datainfo[2],2)."cm";
		if($data[1]==0 || $data[1]==""){
			$is_exception = true;
		}
		if($data[2]==0 || $data[2]==""){
			$is_exception = true;
		}
		if($data[3]==0 || $data[3]==""){
			$is_exception = true;
		}
		if($is_exception){
			$ss = "UPDATE om_adjust_sku SET is_exception=1 WHERE id='{$value['id']}'";
			$ss = $dbcon->execute($ss);
		}else{
			$sku_cubeweightcost[$value['sku']]['cubage'] = $cube;
			$sku_cubeweightcost[$value['sku']]['cubage_weight'] = round($data[1],2)."";
			$sku_cubeweightcost[$value['sku']]['weight'] = round($data[2],2)."";
			$sku_cubeweightcost[$value['sku']]['cost'] = round($data[3],2)."";
		}
		//$sku_cubeweightcost[$value['sku']] = $value['adjustvalue'];
	}
}

//新接口

if(!empty($sku_cubeweightcost)){
//print_r($sku_cubeweightcost);exit;
	$msg = adjust_cubeweightcost($sku_cubeweightcost);
	$msg = json_decode($msg,true);
	if(isset($msg['ACK'])&&$msg['ACK'] != "Failure"){
		foreach($sku_cubeweightcost as $key=>$value){
			$sql = "UPDATE om_adjust_sku SET CN_status=1 WHERE type=5 and sku='{$key}' and is_exception=0 and CN_status=0";
			$sql = $dbcon->execute($sql);
			//$data = explode(",",$value); 
			if($sql){
				
				echo "料号【{$key}】post to cndirect 成功！ 体积：{$value['cubage']},体积重量：{$value['cubage_weight']},称重重量：{$value['weight']},货本：{$value['cost']}\r\n";
			}else{
				echo "料号【{$key}】post to cndirect 失败！ 体积：{$value['cubage']},体积重量：{$value['cubage_weight']},称重重量：{$value['weight']},货本：{$value['cost']}\r\n";
			}
		}
	}else{
		echo "调用重量接口出错了，赶紧查一下！出错信息：";
		print_r($msg['Errors']);
	}
}else{
	echo "没有需要更新体积重量价格的料号\r\n";
}
?>