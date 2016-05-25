<?php
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();
$time = time()-3600*25;
$orders = M("Order")->getAllData("id,tracking_number,create_time","seller_ship_time >{$time}");
foreach ($orders as $key => $value) {
	M("OrderDetails")->setTablePrefix('_'.date('Y_m',$value['create_time']));
	$orderDetail = M("OrderDetails")->getSingleData("id,shipping_type","id={$value['id']}");
	// if(in_array($orderDetail["shipping_type"], array('CNPSS','CHPTS')) && !empty($value['tracking_number'])){
	if(!empty($value['tracking_number'])){
		$trackCount = M("TrackAdmin")->getDataCount("order_id={$value['id']}");
		if($trackCount == 0){
			$insertData = array(
				"order_id"	=> $value['id'],
				"track_number"	=> $value['tracking_number'],
				"table_suffix"	=> $value['create_time'],
				"add_time"		=> time()
			);
			M("TrackAdmin")->insertData($insertData);
		}else{
			$updateData = array("track_number" => $value['tracking_number']);
			$whereData  = array("order_id" => $value['id']);
			M("TrackAdmin")->updateDataWhere($updateData,$whereData);
		}
	}
}


