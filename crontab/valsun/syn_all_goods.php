<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";

Core::getInstance();

A("ValsunButt")->setConfig(C("VALSUN_CONF")['appKey'],C("VALSUN_CONF")['appToken']);
$startTime =  1356969599-3600;  //起始时间  时间戳
 //终止时间  为'0'时 表示当前时间  注意：表示当前时间请勿输入0 而应该'0'
$endTime =  $startTime+3600*24*5;
$isNew   = '1';
$companyId = '3';
do{
	$ret	=	A("ValsunButt")->getGoodsStatus($startTime,$endTime);
	$startTime = $endTime;
	$endTime = $endTime + 3600*24*5;
	$ret = json_decode($ret,true);
	foreach($ret["data"] as $v){
		$goods = M("Goods")->getSingleData("id",array("sku"=>$v["sku"],"companyId"=>$companyId));
		if(!empty($goods)){
			$ret = M("Goods")->updateData($goods['id'],$v);
			if($ret){
				echo "update Success \r\n";
			}else{
				echo "update Error \r\n";
			}
		}else{
			$v["companyId"] = $companyId;
			$ret = M("Goods")->insertData($v);
			if($ret){
				echo "insert Success \r\n";
			}else{
				echo "insert Error \r\n";
			}
		}
	}
}while($startTime < time());
