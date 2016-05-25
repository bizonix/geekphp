<?php
/**********************************************************************
 *	速卖通标记发货
 *	by  zhongyantai	2013-04-22
 */
error_reporting(E_ALL);
ini_set('max_execution_time', 1800);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once "/data/scripts/ebay_order_cron_job/script_root_path.php";
require_once "/data/scripts/ebay_order_cron_job/ebay_order_cron_config.php";
include_once SCRIPT_ROOT."aliexpress/Aliexpress.class.php";
define("ALI_LOG_DIR","/home/ebay_order_cronjob_logs/aliexpress/shipment/");


//ERP账号与速卖通账号的映射表
$erp_user_mapping	=	array(
	"3ACYBER"		=>	"cn1000268236",
	"szsunweb"		=>	"cn1000421358",
	"E-Global"		=>	"cn1000616054",
	"beauty365"		=>	"cn1000960806",
	"caracc"		=>	"cn1000983412",
	"Bagfashion"	=>	"cn1000983826",
	"prettyhair"	=>	"cn1000999030",
	"LovelyBaby"	=>	"cn1001428059",
	"Finejo"		=>	"cn1001392417",
	"5season"		=>	"cn1001424576",
	"fashiondeal"	=>	"cn1001656836",
	"Sunshine"		=>	"cn1001711574",
	"fashionqueen"	=>	"cn1001718610",
	"shiningstar"	=>	"cn1001739224",
	"babyhouse"		=>	"cn1500053764",
	"fashionzone"	=>	"cn1500152370",
	"shoesacc"		=>	"cn1500226033",
	"superdeal"		=>	"cn1500293467",
	"istore"		=>	"cn1500439756",
	"ladyzone"		=>	"cn1500514645",
	"beautywomen"	=>	"cn1500688776",
	"womensworld"	=>	"cn1501288533",
	"myzone"		=>	"cn1501287427",
	"homestyle"		=>	"cn1501540493",	//2013-08-01
	"championacc"	=>	"cn1501578304",	//2013-08-01
	"digitallife"	=>	"cn1501595926",	//2013-08-01
	"Etime"			=>	"cn1501638006",	//2013-08-20


	//taotaoAccount
	"taotaocart"	=>	"cn1501642501",
	"arttao"	=>	"cn1501654678",
	"taochains"	=>	"cn1501654797",
	"etaosky"	=>	"cn1501655651",
	"tmallbasket"	=>	"cn1501656206",
	"mucheer"	=>	"cn1501656494",
	"lantao"	=>	"cn1501657160",
	"direttao"	=>	"cn1501657334",
	"hitao"	=>	"cn1501657572",
	"taolink"	=>	"cn1501686293",

	//----------

);

$aliexpress_user	=	trim($argv[1]);
$account	=	$erp_user_mapping[$aliexpress_user];
$start	=	strtotime("-7 hour");
$sql	= 	"select ebay_id,recordnumber,ebay_carrier,combine_package ,ebay_status, ebay_tracknumber
			from ebay_order 
			where ebay_account = '$aliexpress_user' 
			and ebay_status ='2' 
			and scantime >= $start and (ShippedTime ='' or ShippedTime is null)
			";
// 
$sql	= 	$dbConn->execute($sql);
$alldata= 	$dbConn->getResultArray($sql);
$sum	=	sizeof($alldata);


if($sum > 0){
	foreach($alldata as $val){
	
		$recordnumber	=	$val['recordnumber'];
		$ebay_carrier	=	$val['ebay_carrier'];
		$trackno		=	$val['ebay_tracknumber'];
		$update_ebay_id	=	$val['ebay_id'];
		$mctime	=	time();

		$sql	= 	"select ebay_id,recordnumber,ebay_carrier,combine_package ,ebay_status, ShippedTime, ebay_combine
					from ebay_order where recordnumber = '$recordnumber' and ebay_status in (2,594,614,636,671,689,672,673)";
		$sql	= 	$dbConn->execute($sql);
		$data	= 	$dbConn->getResultArray($sql);
		$total	=	sizeof($data);
		
		$no_set_shipping_flag	=	0;
		switch (strtoupper($ebay_carrier)){
			case "香港小包挂号":
				$serviceName		= 'HKPAM';	//Hongkong Post Air Mail
				break;
			case "UPS":
				$serviceName		= 'UPS';
				break;
			case "DHL":
				$serviceName		= 'DHL';
				break;
			case "FEDEX":
				$serviceName		= 'FEDEX_IE';
				break;
			case "TNT":
				$serviceName		= 'TNT';
				break;
			case "EMS":
				$serviceName		= 'EMS';
				break;
			case "中国邮政挂号":
				$serviceName		= 'CPAM';	//China Post Air Mail
				break;
			default:
				$serviceName	=	$ebay_carrier;
				$no_set_shipping_flag	=	1;
				break;
		}

		if($total == 1){
			//B2B 没有合并包裹， 只有合并订单
			if(!empty($data[0]['ebay_combine'])){
				$other_order	=	explode("##",$data[0]['ebay_combine']);
				foreach($other_order as $v){
					if(empty($v)) continue;
					//取被合并订单的信息。 同时也标记发货
					$sql	= 	"select ebay_id,recordnumber,ebay_carrier,combine_package 
								from ebay_order where ebay_id = '".$v."'";
					$sql	= 	$dbConn->execute($sql);
					$ret	= 	$dbConn->getResultArray($sql);
					
					$dat	=	sellerShipment($account,$ret[0]['recordnumber'],$serviceName,$trackno,"all",$no_set_shipping_flag);
					if($dat)	update_order_shippedmarked_time($ret[0]['ebay_id'], $mctime);
				}
			}
			$ret	=	sellerShipment($account,$val['recordnumber'],$serviceName,$trackno,"all",$no_set_shipping_flag);
			if($ret)	update_order_shippedmarked_time($update_ebay_id, $mctime);
		}
		if($total > 1){
			$send_type		=	"all";
			$total_empty	=	0;
			foreach ($data as $v){
				if(empty($v['ShippedTime'])){	//存在未发货的
					$total_empty++;
				}
			}
			if($total_empty > 1){
				$send_type	=	"part";
			}
			$ret	=	sellerShipment($account,$val['recordnumber'],$serviceName,$trackno,$send_type,$no_set_shipping_flag);
			if($ret) update_order_shippedmarked_time($update_ebay_id, $mctime);
		}
	}
}


function update_order_shippedmarked_time($ebay_id,$mctime){
	global $dbConn;
	$sql	=	"update ebay_order set ebay_markettime='$mctime',ShippedTime='$mctime' 
				where ebay_id='$ebay_id'";
	//$sql	=	"update ebay_order set ebay_markettime='',ShippedTime='' 
	//			where ebay_id='$ebay_id'";
	$ret	=	$dbConn->execute($sql);
}


function sellerShipment($account,$recordnumber,$serviceName,$tracknumber,$type,$no_set_shipping_flag){

	$logfile	=	ALI_LOG_DIR."order_shipment_".$account."_".date("Y-m-d").".log";
	$configFile =	SCRIPT_ROOT."aliexpress/config/config_{$account}.php";
	if (file_exists($configFile)){
		include $configFile;
	}else{
		return false;
	}
	
	$aliexpress = new Aliexpress();
	$aliexpress->setConfig($appKey,$appSecret,$refresh_token);
	$aliexpress->doInit();
	$log_data	=	array();
	$log_data['time']	=	date("Y-m-d H:i:s");
	$log_data['recordnumber']	=	$recordnumber;
	$log_data['serviceName']	=	$serviceName;
	$log_data['tracknumber']	=	$tracknumber;
	$log_data['type']	=	$type;
	if(!$no_set_shipping_flag){
		$data	=	$aliexpress->sellerShipment($serviceName, $tracknumber, $type, $recordnumber);
	}
	if(isset($data['error_code']) && !empty($data['error_code'])){
		$log_data['msg']	=	$data['error_message'];
		$log	=	json_encode($log_data)."\r\n";
		@file_put_contents($logfile, $log, FILE_APPEND);
		return false;
	}else{
		$log_data['msg']	=	"success";
		$log	=	json_encode($log_data)."\r\n";
		@file_put_contents($logfile, $log, FILE_APPEND);
		return true;
	}
}

?>
