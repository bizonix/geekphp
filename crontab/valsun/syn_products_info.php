<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";

Core::getInstance();
A("ValsunButt")->setConfig(C("VALSUN_CONF")['appKey'],C("VALSUN_CONF")['appToken']);
$whereData = 'select spu from we_goods where is_delete=0 and isNull(checkCost) group by spu ';
$count = count(MC($whereData));
$prepage = 100;
$page  = ceil($count/$prepage);

for($i=1;$i<=$page;$i++){
	$data = MC($whereData."limit ".($i-1)*$prepage.",$prepage");
	$spuArr = array();
	foreach($data as $v){
		$spuArr[] = array("spu"=>$v["spu"],"country"=>"United States");
	}
	$retData = A("ValsunButt")->getProductsFee($spuArr);
	foreach($retData as $k=>$v){
		unset($v["spu"]);
		unset($v["sku"]);
		$updateData = M("Goods")->updateDataWhere(array("checkCost"=>json_encode($v)),array("sku"=>$k));
		if($updateData){
			echo "update success \r\n";
		}else{
			echo "update error \r\n";
		}
	}
}

