<?php
$taobao_user	=	trim($argv[1]);
if(empty($taobao_user)){
	echo "empty user!\n";
	exit;
}
ini_set('max_execution_time', 1800);
if(!defined('WEB_PATH')){
	define("WEB_PATH","/data/web/order.valsun.cn/");
}
require_once WEB_PATH."crontab/scripts.comm.php";
require_once WEB_PATH_LIB_SDK_TAOBAO.'lib/functions.php';
require_once WEB_PATH_LIB_SDK_TAOBAO.'lib/taobao.logistics.offline.send.php';

//$taobao_user	= '001';
$configFile	=	WEB_PATH_CONF_SCRIPTS_KEYS_TAOBAO."config_".$taobao_user.".php";
$logfile	=	SCRIPT_DATA_LOG."taobao/shipment/taobao_shipment_".$taobao_user."_".date("Y-m-d").".log";
//echo $logfile;die;
if (file_exists($configFile)){
	include_once $configFile;
}else{
	echo	"error： 未找对应的config文件!\n";
	exit;
}

$debug_mode	=	'false';	
$user		=	'vipchen';	
$start	    =	strtotime("-72 hour");
$account_info = get_account_id($account);
$omAvailableAct = new OmAvailableAct();            
$where   = " WHERE accountId = '{$account_info['accountid']}' AND is_delete = 0 AND b.weighTime>$start";
$alldata =  $omAvailableAct->act_getTNameList('om_shipped_order as a left join om_shipped_order_warehouse as b on a.id=b.omOrderId', 'a.id,a.recordNumber,a.accountId,a.transportId', $where);

$sum	=	sizeof($alldata);

if($sum > 0){
	foreach($alldata as $val){
		$log_data	=	array();
		
		$carrier = CommonModel::getShipingNameById($val['transportId']);
		$where  = " WHERE omOrderId = '{$val['id']}' AND is_delete = 0 ";
		$res	=  $omAvailableAct->act_getTNameList('om_order_tracknumber', 'tracknumber', $where);
		
		$log_data['time']			=	date("Y-m-d H:i:s");
		$log_data['recordnumber']	=	$recordnumber	=	$val['recordnumber'];
		$log_data['ebay_carrier']						=	$carrier;
		$log_data['tracknumber']	=	$tracknumber	=	$res[0]['tracknumber'];
		$log_data['ebay_id']							=	$val['id'];
		$company_code	=	getLogisticCode($carrier);
		$log_data['company_code']	=	$company_code;
		
		if(empty($res)){
			$log_data['errcode']	=	11;
			$log_data['msg']		=	"empty ebay_tracknumber";
			$log	=	json_encode($log_data)."\r\n";
			@file_put_contents($logfile, $log, FILE_APPEND);
			continue;
		}

		if(empty($company_code)){
			$log_data['errcode']	=	10;
			$log_data['msg']		=	"empty company_code";
			$log	=	json_encode($log_data)."\r\n";
			@file_put_contents($logfile, $log, FILE_APPEND);
			continue;
		}
		
		$json_data	=	taobaoLogisticsOfflineSend($url, $session, $appKey, $appSecret, $recordnumber, $company_code, $tracknumber);
		if(isset($json_data['error_response'])){
			$log_data['errcode']	=	$json_data['error_response']['code'];
			$log_data['msg']		=	$json_data['error_response']['sub_msg'];
			$log	=	json_encode($log_data)."\r\n";
			@file_put_contents($logfile, $log, FILE_APPEND);
			continue;
		}else{
			$log_data['errcode']	=	0;
			$log_data['msg']		=	json_encode($json_data);
			$log	=	json_encode($log_data)."\r\n";
			@file_put_contents($logfile, $log, FILE_APPEND);
			continue;
		}
	}
}

?>