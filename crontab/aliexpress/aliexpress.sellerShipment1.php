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
	"citymiss"		=>	"cn1510304665",
	"zeagoo360"		=>	"cn1500440054",

	//taotaoAccount
	"taotaocart"	=>	"cn1501642501",
	"arttao"		=>	"cn1501654678",
	"taochains"		=>	"cn1501654797",
	"etaosky"		=>	"cn1501655651",
	"tmallbasket"	=>	"cn1501656206",
	"mucheer"		=>	"cn1501656494",
	"lantao"		=>	"cn1501657160",
	"direttao"		=>	"cn1501657334",
	"hitao"			=>	"cn1501657572",
	"taolink"		=>	"cn1501686293",

	//----------

	//surfaceAccount
	"acitylife"		=> "cn1510515579",
	"etrademart"	=> "cn1510509503",
	"centermall"	=> "cn1510509429",
	"viphouse"		=> "cn1510514024",

	//---------

);

$surface_accounts = array('acitylife','etrademart','centermall','viphouse');

$aliexpress_user	=	trim($argv[1]);
$account	=	$erp_user_mapping[$aliexpress_user];

echo "-------------------start ".date("Y-m-d H:i:s")."------------------------\n";

if($aliexpress_user == "Etime") {
	$start	=	strtotime("-7 hour");
}else{
	
	$start	=	strtotime("-7 hour");
}
$sql	= 	"select ebay_id,recordnumber,ebay_carrier,combine_package ,ebay_status, ebay_tracknumber
			from ebay_order 
			where ebay_account = '$aliexpress_user' 
			and ebay_status ='2' 
			and scantime >= $start and (ShippedTime ='' or ShippedTime is null)
			ORDER BY scantime";
// and ebay_status ='2' 
//$testsql = " ORDER BY scantime"; $sql .= $testsql;  // for test 

$sql	= 	$dbcon->execute($sql);
$alldata= 	$dbcon->getResultArray($sql);
$sum	=	sizeof($alldata);

if($sum > 0){
	foreach($alldata as $val){
		
		echo "开始上传订单【{$val['ebay_id']}】 ----------运输方式={$val['ebay_carrier']}------\n\n";
	
		$recordnumber	=	$val['recordnumber'];
		$ebay_carrier	=	$val['ebay_carrier'];
		$trackno		=	$val['ebay_tracknumber'];
		$update_ebay_id	=	$val['ebay_id'];
		$mctime	=	time();

		$sql	= 	"select ebay_id,recordnumber,ebay_carrier,combine_package ,ebay_status, ShippedTime, ebay_combine
					from ebay_order where recordnumber = '$recordnumber' and ebay_status in (2,594,614,636,671,689,672,673)";
		$sql	= 	$dbcon->execute($sql);
		$data	= 	$dbcon->getResultArray($sql);
		$total	=	sizeof($data);
		
		$no_set_shipping_flag	=	0;
		if (in_array($aliexpress_user, $surface_accounts)){
			$surface_carrier = get_surface_trackno($data[0]['ebay_id'], $total == 1 ? 1 : 2);
			$ebay_carrier	=	$surface_carrier['ebay_carrier'];
			$trackno		=	$surface_carrier['ebay_tracknumber'];
		}
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
			case "EUB":
				$serviceName		= 'EMS_ZX_ZX_US';	//EUB
				break;
			
			case "新加坡小包挂号":
				$serviceName		= 'SGP';	
				break;
			case "WEDO":
				$serviceName		= 'Other';	
				break;
			default:
				$serviceName	=	$ebay_carrier;
				$no_set_shipping_flag	=	1;
				break;
		}
		
		$Website = $serviceName=='Other' ? "http://www.wedoexpress.com/index.php?mod=trackInquiry&act=index&carrier=wedo&tracknum={$trackno}" : '';
		
		if($total == 1){
			//B2B 没有合并包裹， 只有合并订单
			if(!empty($data[0]['ebay_combine'])){
				$other_order	=	explode("##",$data[0]['ebay_combine']);
				foreach($other_order as $v){
					if(empty($v)) continue;
					//取被合并订单的信息。 同时也标记发货
					$sql	= 	"select ebay_id,recordnumber,ebay_carrier,combine_package 
								from ebay_order where ebay_id = '".$v."'";
					$sql	= 	$dbcon->execute($sql);
					$ret	= 	$dbcon->getResultArray($sql);
					
					$dat	=	sellerShipment($account,$ret[0]['recordnumber'],$serviceName,$trackno,"all", $no_set_shipping_flag, $Website);
					if($dat)	update_order_shippedmarked_time($ret[0]['ebay_id'], $mctime);
				}
			}
			$ret	=	sellerShipment($account,$val['recordnumber'],$serviceName,$trackno,"all", $no_set_shipping_flag, $Website);
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
			$ret	=	sellerShipment($account,$val['recordnumber'],$serviceName,$trackno,$send_type,$no_set_shipping_flag, $Website);
			if($ret) update_order_shippedmarked_time($update_ebay_id, $mctime);
		}
	}
}

echo "-------------------end ".date("Y-m-d H:i:s")."------------------------\n";
exit;

function get_surface_trackno($ebayid, $packingstatus){
	
	global $dbcon;
	
	$sql = "SELECT ebay_countryname,ebay_state,ebay_city,ebay_postcode,ebay_carrier,ebay_account,ebay_userid,ebay_usermail FROM ebay_order WHERE ebay_id='{$ebayid}'";
	$sql = $dbcon->query($sql);
	$orderinfo = $dbcon->fetch_one($sql);
	
	$ebay_carrier = str_replace('平邮', '挂号', $orderinfo['ebay_carrier']);
	
	$starttime = time()-3*24*3600;
	$endtime = time();
	$sql = "SELECT ebay_account,ebay_tracknumber,ebay_carrier FROM ebay_order WHERE ebay_combine!='1' AND ebay_carrier='$ebay_carrier' AND scantime BETWEEN '{$starttime}' AND '{$endtime}' AND ebay_postcode='{$orderinfo['ebay_postcode']}' AND ebay_countryname='{$orderinfo['ebay_countryname']}' ORDER BY ebay_id DESC";
	//$sql = "SELECT * FROM ebay_order as eo WHERE eo.ebay_combine!='1' AND eo.scantime > '1390320000' AND eo.scantime < '1390492799' AND eo.ebay_carrier='中国邮政挂号' AND eo.ebay_countryname = 'Brazil' AND eo.ebay_state = 'Parana' AND eo.ebay_city = 'Nova Esperanca'";
	$sql = "SELECT ebay_account,ebay_tracknumber,ebay_carrier FROM ebay_order WHERE ebay_combine!='1' AND scantime>'{$starttime}' AND scantime<'{$endtime}' AND ebay_carrier='$ebay_carrier' AND ebay_postcode='{$orderinfo['ebay_postcode']}' ORDER BY scantime DESC LIMIT 10";
	echo "$sql\n\n";
	$sql = $dbcon->query($sql);
	$orders = $dbcon->getResultArray($sql);
	
	
	if (empty($orders)){
		$_orderinfo = $orderinfo;
		unset($_orderinfo['ebay_postcode'], $_orderinfo['ebay_carrier'], $_orderinfo['ebay_account'], $_orderinfo['ebay_userid'], $_orderinfo['ebay_usermail']);
		
		$wheres = array();
		/*while (!empty($_orderinfo)){
			$wheres[] = $_orderinfo;
			array_pop($_orderinfo);
		}*/
		$wheres[] = $_orderinfo;
		foreach ($wheres AS $where){
			$where = array2strarray($where);
			$sql_where = array();
			foreach ($where AS $_k=>$_v){
				if (empty($_k)){
					continue;
				}
				$_v = trim($_v);
				$sql_where[] = "{$_k}={$_v}";
			}
			$sql = "SELECT ebay_account,ebay_tracknumber,ebay_carrier FROM ebay_order WHERE ebay_combine!='1' AND scantime>'{$starttime}' AND scantime<'{$endtime}' AND ebay_carrier='$ebay_carrier' AND ".implode(' AND ', $sql_where)." ORDER BY scantime DESC LIMIT 10";
			echo "$sql\n\n";
			$sql = $dbcon->query($sql);
			$orders = $dbcon->getResultArray($sql);
			if (!empty($orders)) break;
		}
		
	}
	foreach ($orders AS $order){
		if (!check_is_useful($order['ebay_tracknumber'], $orderinfo['ebay_account'], $orderinfo['ebay_userid'], $orderinfo['ebay_usermail'])){
			$surfacedata = array();
			$surfacedata['order_id'] = $ebayid;
			$surfacedata['account'] = $order['ebay_account'];
			$surfacedata['use_account'] = $orderinfo['ebay_account'];
			$surfacedata['shippingstatus'] = 1;
			$surfacedata['packingstatus'] = $packingstatus;
			$surfacedata['trackno'] = $order['ebay_tracknumber'];
			$surfacedata['carrier'] = $order['ebay_carrier'];
			$surfacedata['saleuser'] = $orderinfo['ebay_userid'];
			$surfacedata['saleemail'] = $orderinfo['ebay_usermail'];
			backup_surfaceid($surfacedata);
			return $order;
		}
	}
	
	$trackno = 'WD'.str_pad($ebayid, 9, "0", STR_PAD_LEFT)."CN";
	if (!check_is_useful($trackno, $orderinfo['ebay_account'], $orderinfo['ebay_userid'], $orderinfo['ebay_usermail'])){
		$surfacedata = array();
		$surfacedata['order_id'] = $ebayid;
		$surfacedata['account'] = 'wedo';
		$surfacedata['use_account'] = $orderinfo['ebay_account'];
		$surfacedata['shippingstatus'] = 1;
		$surfacedata['packingstatus'] = $packingstatus;
		$surfacedata['trackno'] = $trackno;
		$surfacedata['carrier'] = 'wedo';
		$surfacedata['saleuser'] = $orderinfo['ebay_userid'];
		$surfacedata['saleemail'] = $orderinfo['ebay_usermail'];
		backup_surfaceid($surfacedata);
	}
	return array('ebay_account'=>'wedo', 'ebay_tracknumber'=>$trackno, 'ebay_carrier'=>'wedo');
}

function check_is_useful($tracknumber, $account, $saleuser, $saleemail){
	global $dbcon;
	$sql = "SELECT COUNT(*) AS num FROM aliexpress_surface WHERE trackno='{$tracknumber}' AND (account='{$account}' OR saleuser='{$saleuser}' OR saleemail='{$saleemail}')";
	$sql = $dbcon->query($sql);
	$check_result = $dbcon->fetch_one($sql);

	return $check_result['num']>0 ? true : false; 
}

function backup_surfaceid($data){
	global $dbcon;
	$sql = "INSERT INTO aliexpress_surface SET ".array2sql($data).",shipingtime=".time();
	return $dbcon->query($sql);
}


function update_order_shippedmarked_time($ebay_id,$mctime){
	global $dbcon;
	
	echo "标记订单({$ebay_id})上传跟踪号-------上传时间".date("Y-m-d H:i:s",$mctime)."\n\n";
	$sql	=	"update ebay_order set ebay_markettime='$mctime',ShippedTime='$mctime' 
				where ebay_id='$ebay_id'";
	//$sql	=	"update ebay_order set ebay_markettime='',ShippedTime='' 
	//			where ebay_id='$ebay_id'";
	$ret	=	$dbcon->execute($sql);
}


function sellerShipment($account,$recordnumber,$serviceName,$tracknumber,$type,$no_set_shipping_flag,$Website=""){

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
		$data	=	$aliexpress->sellerShipment($serviceName, $tracknumber, $type, $recordnumber, '', $Website);
		echo "交易号=$recordnumber-------运输方式=$serviceName---------跟踪号=$tracknumber--------类型=$type----------URL=$Website-----上传结果=".(isset($data['error_code']) && !empty($data['error_code']) ? 'failure' : 'success')."\n\n";
	}else {
		echo "交易号=$recordnumber-------运输方式=$serviceName---------跟踪号=$tracknumber--------类型=$type----------不支持该运输方式上传\n\n";
		return false;
	}
	
	if(isset($data['error_code']) && !empty($data['error_code'])){
		echo $data['error_message']."\n\n";
		$log_data['msg']	=	$data['error_message'];
		$log	=	json_encode($log_data)."\r\n";
		@file_put_contents($logfile, $log, FILE_APPEND);
		return preg_match("/Operation\sfailed\sin\sAuthorization/i", $data['error_message'])>0 ? true : false;
	}else{
		$json_data	=	json_encode($data);
		if(empty($data) || empty($json_data)){
			$log_data['msg']	=	"op fail";
			$log	=	json_encode($log_data)."\r\n";
			@file_put_contents($logfile, $log, FILE_APPEND);
			return false;
		}else{
			$log_data['msg']	=	"success";
			$log	=	json_encode($log_data)."_______".$json_data."\r\n";
			@file_put_contents($logfile, $log, FILE_APPEND);
			return true;
		}
	}
}

?>
