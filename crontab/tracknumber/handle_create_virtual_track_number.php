<?php
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();
//echo base64_encode("WECfdfdfd22");exit;
//echo encode_pass("WC006228-4-339","WECLU");exit;
//echo encode_pass("c57SCb3IpNCFUAFACAwUZUEkDAwA","WECLUE_IMG","decode");exit;
$orderId		= '10437';
$trackNumber	= 'RI492016938CN';
$table_suffix	= strtotime("2015-03-02 09:45:46");
$add_time		= strtotime("2015-03-03 21:40:59");
$trackCount = M("TrackAdmin")->getDataCount("order_id={$orderId}");
if($trackCount == 0){
	$insertData = array(
		"order_id"		=> $orderId,
		"track_number"	=> $trackNumber,
		"table_suffix"	=> $table_suffix,
		"add_time"		=> $add_time
	);
	$res = M("TrackAdmin")->insertData($insertData);
	if($res){
		echo "insert Success ! \r\n";
	}else{
		echo "insert Error ! \r\n";
	}
}else{
	$updateData = array(
		"track_number" => $trackNumber,
		"table_suffix" => $table_suffix,
		"add_time"	   => $add_time
	);
	$whereData  = array("order_id" => $orderId);
	$res = M("TrackAdmin")->updateDataWhere($updateData,$whereData);
	if($res){
		echo "update Success ! \r\n";
	}else{
		echo "update Error ! \r\n";
	}
}


