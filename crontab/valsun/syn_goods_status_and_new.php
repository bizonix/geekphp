<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();

A("ValsunButt")->setConfig(C("VALSUN_CONF")['appKey'],C("VALSUN_CONF")['appToken']);
$startTime = time()-3600*24*4;
$endTime = time();
$res = A("ValsunButt")->getGoodsStatus($startTime,$endTime);
$res = json_decode($res,true);
$companyId = 3;
foreach($res["data"] as $k=>$v){
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
