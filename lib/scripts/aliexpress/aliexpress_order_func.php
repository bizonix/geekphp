<?php
function get_carrier_name($ebay_carrier){
		if(in_array($ebay_carrier, array('Hongkong Post Air Mail', 'HK Post Air Mail', 'HKPAM', 'Hongkong Post Airmail', 'HK Post Airmail','HongKong Post Air Mail'))){
			$ebay_carrier		= '香港小包挂号';
		}
		if(in_array($ebay_carrier, array('UPSS', 'UPS Express Saver'))){
			$ebay_carrier		= 'UPS';
		}
		
		if($ebay_carrier   == 'DHL'){
			$ebay_carrier		= 'DHL';
		}
		
		if($ebay_carrier   == 'EMS'){
			$ebay_carrier		= 'EMS';
		}
		
		if(in_array($ebay_carrier, array('ChinaPost Post Air Mail', 'China Post Air Mail', 'CPAM', 'China Post Airmail'))){
			$ebay_carrier		= '中国邮政挂号';
		}
		
		if($ebay_carrier=='ePacket'){
			$ebay_carrier = 'EUB';
		}

		if($ebay_carrier == "Fedex IE"){
			$ebay_carrier = 'FedEx';
		}
		return $ebay_carrier;
}


function time_shift($origin_num) { //转换成时间戳
	$time_offset	=	0;
	$i	=	0;
	$i	=	strpos($origin_num,"-");
	
	if($i > 0){
		$temp	=	explode("-", $origin_num);
		$utc	=	intval(preg_replace("/0/","",$temp[1]));
		$time_offset	=	time() - 3600*(8+ $utc);	
	}
	$i	=	0;
	$i	=	strpos($origin_num,"+");
	if($i > 0){
		$temp	=	explode("+", $origin_num);
		$utc	=	intval(preg_replace("/0/","",$temp[1]));
		if($utc > 8){
			$time_offset	=	time() + 3600*($utc - 8);	
		}else{
			$time_offset	=	time() - 3600*(8 - $utc);	
		}
	}
	$time	=	strtotime(substr($origin_num,0,14));
	return array($time, $time_offset);

}

/*function get_account_id($accountName) {
	$omAvailableAct = new OmAvailableAct();                
	$where  = " WHERE account = '{$accountName}' AND is_delete = 0 ";
	$res	=  $omAvailableAct->act_getTNameList('om_account', 'id', $where);
	if(empty($res)){
		$log	=	"没有账户名为{$accountName}的账户信息\n";            
		@file_put_contents($logfile, $log, FILE_APPEND);
		return FALSE;
	}         
	return $res['id'];            
}*/

function get_country_name($code) {
	global $dbConn;            
	$sql = "select regions_en from om_country_list where regions_jc='{$code}' limit 1";
	$sql = $dbConn->query($sql);
	$res = $dbConn->fetch_array($sql);
	return $res['regions_en'];
}

function get_sku_location($sku){
	global $dbConn;
	$sql = "select  goods_location from ebay_goods where goods_sn='{$sku}' limit 1";
	$sql = $dbConn->execute($sql);
	$res = $dbConn->fetch_one($sql);
	return $res['goods_location'];
}  
?>