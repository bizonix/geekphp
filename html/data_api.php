<?php
include_once __DIR__.'/../framework.php';                               // 加载框架
Core::getInstance();                                                    // 初始化框架对象
include_once __DIR__.'/../lib/productstatus.class.php';                               // 加载框架

error_reporting(-1);
$product = new ProductStatus();
global $dbConn;

$updateSkuArr = $_POST['skuArr'];

$type = $_POST['type'];

if($type == "getData"){
	$rtnArr = array();
	foreach($updateSkuArr as $sku){
		//$rtn = $product->resetSkuStock($sku,1);
		//$product->calcAverageDailyCount($item['sku']);
		$item = $product->getRealSkuData($sku);
		$rtnArr[$sku] = $item;
	}
	echo json_encode($rtnArr);
}else{
	foreach($updateSkuArr as $sku){
		//$rtn = $product->resetSkuStock($sku,1);
		//$product->calcAverageDailyCount($item['sku']);
		$product->resetSkuAverage($sku);
	}
}

?>
