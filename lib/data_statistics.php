<?php
include_once __DIR__.'/../framework.php';                               // 加载框架
Core::getInstance();                                                    // 初始化框架对象
include_once __DIR__.'/../lib/productstatus.class.php';                               // 加载框架

error_reporting(-1);
$product = new ProductStatus();
//$product->updateSkuStatusByOrderStatus(21,201);
global $dbConn;
$sql = "SELECT sku,spu FROM `pc_goods` where 1";
$sql = $dbConn->execute($sql);
$skuInfo = $dbConn->getResultArray($sql);

foreach($skuInfo as $item){
	//$product->checkDailyStatus($item['sku'],$item['spu']);
	$sku = $item['sku'];
	//$num = $product->getPastDayCount("18845_BL_M",30);
	//$dayilyNum = $product->calcAverageDailyCount($item['sku']);
	//$sql = "UPDATE `om_sku_daily_status` set AverageDailyCount={$dayilyNum} where sku='{$sku}'"; 
	//resetSkuStock
	/*
	if($dbConn->execute($sql)){
		echo "均量更新成功。。。。\n";
	}
	 */
	$product->resetSkuStock($item['sku'],1);
}


?>
