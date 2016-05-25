<?php
/**
 * @name : 新订单系统EUB跟踪号自动线下申请脚本
 * @author : guanyongjun
 * @date : 2014/02/28
 * @version : 1.0
*/
error_reporting(-1);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
require_once '/data/web/order.valsun.cn/crontab/eub_tracknum/include/config.php';
require_once SCRIPT_PATH."framework.php";
Core::getInstance();
################基础信息配置###############
$nums 			= isset($argv[1]) ? $argv[1] : 200;//一次申请多少个跟踪号
$days 			= isset($argv[2]) ? $argv[2] : 1;//多少天内的订单申请跟踪号
$carrierId		= 6;//EUB运输方式ID
$userId			= 71;//统一用户ID
$times			= time();
if ($times >= strtotime('2014-01-28 18:00:01') && $times < strtotime('2014-01-29 06:00:01')) exit('EUB邮局线路暂时关闭');
if ($times >= strtotime('2014-02-02 18:00:01') && $times < strtotime('2014-02-03 06:00:01')) exit('EUB邮局线路暂时关闭');

if ($times >= strtotime('2014-01-29 06:00:01') && $times < strtotime('2014-02-03 06:00:01')) {
	//发件人（英文、中文）信息
	$s_name			= 'Chen Qian';
	$s_postcode		= '440307';
	$s_phone		= '075589619601';
	$s_mobile		= '18073021301';
	$s_country		= 'CN';
	$s_province		= '440000';
	$s_city			= '440300';
	$s_county		= '440307';
	$s_company		= 'SailVan Network Technology Co., Ltd. of Shenzhen City';
	$s_street		= 'Xinsheng Village, Longgang District, 53 cents Tin Yiu Industrial Park';
	$s_email		= 'chenqian@sailvan.com';

	$c_name			= '陈前';
	$c_postcode		= '440307';
	$c_phone		= '075589619601';
	$c_mobile		= '18073021301';
	$c_country		= 'CN';
	$c_province		= '440000';
	$c_city			= '440300';
	$c_county		= '440307';
	$c_company		= '深圳市赛维网络科技有限公司';
	$c_street		= '深圳市龙岗区新生村仙田路53号耀安工业园';
	$c_email		= 'chenqian@sailvan.com';
} else {
	//发件人（英文、中文）信息
	$s_name			= 'Chen Qian';
	$s_postcode		= '350602';
	$s_phone		= '05963299218';
	$s_mobile		= '18073021301';
	$s_country		= 'CN';
	$s_province		= '350000';
	$s_city			= '350600';
	$s_county		= '350602';
	$s_company		= 'Zhangpu Fenzhe Garment Co., Ltd.';
	$s_street		= 'North Zone Xia Jin Sui Zhangpu CHINA';
	$s_email		= 'sunweb889@gmail.com';

	$c_name			= '陈前';
	$c_postcode		= '350602';
	$c_phone		= '05963299218';
	$c_mobile		= '18073021301';
	$c_country		= 'CN';
	$c_province		= '350000';
	$c_city			= '350600';
	$c_county		= '350602';
	$c_company		= '漳浦芬哲制衣有限公司';
	$c_street		= '漳浦绥安开发区金霞北路';
	$c_email		= 'sunweb889@gmail.com';
}
################### 获取需要申请EUB跟踪号的订单列表 #############
$logs			= "==============EUB 跟踪号自动申请日志==============\n\n";
$ids			= "";
$condition		= "";
$status			= "660";//要排除的订单状态
$idarr			= array();
$data			= array();
$times			= time() - 600;//每隔10分钟重新跑下失败的订单跟踪号申请
$sql			= "SELECT om_id FROM om_tracknumber_record_fail WHERE addTime >= {$times}";
//echo $sql,"\n";
$query			= $dbConn->query($sql);
$res			= $dbConn->fetch_array_all($query);
foreach ($res as $val) {
	array_push($idarr,$val['om_id']);
}
$endtime		= time();
$starttime		= $endtime-$days*86400;
$ids			= implode(",",$idarr);
$condition		= empty($ids) ? "" : "AND a.id NOT IN({$ids})"; 
$nocounts		= "'eshoppingstar75','ishoppingclub68','newcandy789','mysoulfor','estore456'";
$sql			= "SELECT id FROM om_account WHERE account IN({$nocounts})";
//echo $sql,"\n";
$query			= $dbConn->query($sql);
$res			= $dbConn->fetch_array_all($query);
foreach ($res as $v) {
	array_push($data,$v['id']);
}
$noaccoutid		= implode(",",$data);
$data			= array();
################### 自动移动帐号为$nocounts的订单到EUB跟踪号线上手工申请文件夹###########
$sql			= "SELECT a.id FROM om_unshipped_order AS a
					LEFT JOIN om_order_tracknumber AS b ON a.id = b.omOrderId
					WHERE a.transportId = 6 AND accountId IN({$noaccoutid}) AND a.orderType != 729 AND a.orderStatus IN(100) AND (b.tracknumber IS NULL OR b.tracknumber = '')";
echo $sql,"===\n";
$query			= $dbConn->query($sql);
$res			= $dbConn->fetch_array_all($query);
foreach ($res as $v) {
	array_push($data,$v['id']);
}
$upids			= implode(",",$data);
if (!empty($upids)) {
	$sql 			= "UPDATE om_unshipped_order SET orderStatus = '100',orderType = '729' WHERE id IN({$upids})";
	echo $sql,"===";
	$query			= $dbConn->query($sql);
	$upids			= mysql_affected_rows();
	echo $upids,"\n";
}
################### END ############################

$condition		.= " AND a.accountId NOT IN({$noaccoutid})";
$condition		.= " AND a.orderAddTime BETWEEN {$starttime} AND {$endtime}";
$sql 			= "SELECT a.id,a.accountId,a.platformId,b.tracknumber,c.suffix FROM om_unshipped_order AS a
					LEFT JOIN om_order_tracknumber AS b ON a.id = b.omOrderId
					LEFT JOIN om_platform AS c ON a.platformId = c.id
					WHERE orderStatus NOT IN ({$status}) AND calcWeight <= 2 AND transportId = {$carrierId} {$condition} AND (b.tracknumber IS NULL OR b.tracknumber = '') LIMIT {$nums}";
echo $sql,"\n";
$query			= $dbConn->query($sql);
$res			= $dbConn->fetch_array_all($query);
$logs			.= "此次共有".count($res)."个订单需要自动申请跟踪号\n";
################### 组装申请EUB接口需要的数据 ######################
if (count($res)<=0) exit("没有符合条件的数据需要申请跟踪号的！\n");
foreach ($res as $val) {
	$orderid		= $val['id'];
	$account		= $val['accountId'];
	$tabname		= $val['suffix'];
	$logs			.= date('Y-m-d H:i:s',time())."===订单号{$orderid}申请跟踪号开始===\n";

	//收件人信息
	$sql			= "SELECT username,email,zipCode,landline,street,address2,address3,city,state,countryName,countrySn FROM om_unshipped_order_userInfo WHERE omOrderId = {$orderid}";
	$query			= $dbConn->query($sql);
	$res			= $dbConn->fetch_array($query);
	$r_name			= isset($res['username']) ? $res['username'] : '';
	$r_postcode		= isset($res['zipCode']) ? $res['zipCode'] : '';
	if(strpos($res['zipCode'],'-')) {
		$r_postcode	= substr($res['zipCode'],0,strpos($res['zipCode'],'-'));
	}
	$r_phone		= isset($res['landline']) ? $res['landline'] : '';
	$r_mobile		= '';
	$r_country		= empty($res['countrySn']) ? $res['countryName'] : $res['countryName'];
	if ($r_country=='United States') $r_country = 'US';
	if (in_array($r_country,array('Puerto Rico','PuertoRico'))) $r_country = 'US';
	if ($r_country=='Virgin Islands (U.S.)') $r_country = 'US';
	$r_province		= isset($res['state']) ? $res['state'] : '';
	$r_city			= isset($res['city']) ? $res['city'] : '';
	$r_street		= isset($res['street']) ? $res['street'] : '';
	if (empty($r_street)) $r_street = isset($res['address2']) ? $res['address2'] : '';
	$r_email		= isset($res['email']) ? $res['email'] : '';

	//订单主体信息
	$customercode	= 'amazonacount';
	$clcttype		= '0';
	$pod			= 'false';
	$untread		= 'Returned';
	$printcode		= '01';
	$volweight		= 0;
	$remark			= '';

	//订单详情
	$items			= '';
	$sql			= "SELECT * FROM om_unshipped_order_detail WHERE omOrderId = {$orderid}";
	//echo $sql,"\n";
	$query			= $dbConn->query($sql);
	$res			= $dbConn->fetch_array_all($query);
	foreach ($res as $v) {
		$is_combine	= check_combine($v['sku']);
		$itmetitle	= get_order_details($tabname,$v['id']);
		//exit($itmetitle);
		if (!$is_combine) {//非组合料号
			$enname		= substr($itmetitle,0,128);
			$count		= $v['amount'];
			$description= substr($itmetitle,0,60);
			$sku_info	= get_goods($v['sku']);
			$unit		= '';
			$weight		= isset($sku_info['goodsWeight']) ? round(floatval($sku_info['goodsWeight'])*$count,3) : 0;
			$cnname		= isset($sku_info['goodsName']) ? substr($sku_info['goodsName'],0,64) : '';
		} else {
			$enname		= substr($itmetitle,0,128);
			$count		= $v['amount'];
			$weight		= 0;
			$sku 		= isset($is_combine['sku']) ? $is_combine['sku'] : '';
			$counts		= isset($is_combine['count']) ? $is_combine['count'] : 0;
			$sku_info	= get_goods($sku);
			$unit		= '';
			$weight_sku	= isset($sku_info['goodsWeight']) ? floatval($sku_info['goodsWeight'])*$counts : 0;
			$cnname		= isset($sku_info['goodsName']) ? substr($sku_info['goodsName'],0,64) : '';
			$weight 	+= $weight_sku;
		}
		$items		.= "<item><cnname><![CDATA[{$cnname}]]></cnname><enname><![CDATA[{$enname}]]></enname><count><![CDATA[{$count}]]></count><unit><![CDATA[{$unit}]]></unit><weight><![CDATA[{$weight}]]></weight><origin><![CDATA[CN]]></origin><delcarevalue>0</delcarevalue><description><![CDATA[{$description}]]></description></item>";
	}

	//上传给EUB服务器的内容
	$xml_data 		= "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>";
	$xml_data		.= "<orders xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"><order>";
	$xml_data		.= "<orderid><![CDATA[{$orderid}]]></orderid><customercode><![CDATA[{$customercode}]]></customercode><vipcode></vipcode><clcttype><![CDATA[{$clcttype}]]></clcttype><pod><![CDATA[{$pod}]]></pod><untread><![CDATA[{$untread}]]></untread><printcode>{$printcode}</printcode><volweight><![CDATA[{$volweight}]]></volweight><remark><![CDATA[{$remark}]]></remark>";
	$xml_data		.= "<sender><name><![CDATA[{$s_name}]]></name><postcode><![CDATA[{$s_postcode}]]></postcode><phone><![CDATA[{$s_phone}]]></phone><mobile><![CDATA[{$s_mobile}]]></mobile><country>{$s_country}</country><province><![CDATA[{$s_province}]]></province><city><![CDATA[{$s_city}]]></city><county><![CDATA[{$s_county}]]></county><company><![CDATA[{$s_company}]]></company><street><![CDATA[{$s_street}]]></street><email><![CDATA[{$s_email}]]></email></sender>";
	$xml_data		.= "<collect><name><![CDATA[{$c_name}]]></name><postcode><![CDATA[{$c_postcode}]]></postcode><phone><![CDATA[{$c_phone}]]></phone><mobile><![CDATA[{$c_mobile}]]></mobile><country>{$c_country}</country><province><![CDATA[{$c_province}]]></province><city><![CDATA[{$c_city}]]></city><county><![CDATA[{$c_county}]]></county><company><![CDATA[{$c_company}]]></company><street><![CDATA[{$c_street}]]></street><email><![CDATA[{$c_email}]]></email></collect>";
	$xml_data		.= "<receiver><name><![CDATA[{$r_name}]]></name><postcode><![CDATA[{$r_postcode}]]></postcode><phone><![CDATA[{$r_phone}]]></phone><mobile><![CDATA[{$r_mobile}]]></mobile><country><![CDATA[{$r_country}]]></country><province><![CDATA[{$r_province}]]></province><city><![CDATA[{$r_city}]]></city><street><![CDATA[{$r_street}]]></street></receiver>";
	$xml_data		.= "<items>{$items}</items>";
	$xml_data		.= "</order></orders>";
	$logs			.= date('Y-m-d H:i:s',time())."===订单编号:{$orderid},发送的内容为:\n".$xml_data."\n";
	// exit;

	$app_ver	= "international_eub_us_1.1";
	$app_key	= "amazonacount_89d53589c3333ec2a0d39be87e2840d2";
	$url 		= "http://www.ems.com.cn/partner/api/public/p/order/";
	$rtn		= get_eub_trackNumber($url,$xml_data)."\n";
	$logs		.= $rtn;
	$logs		.= date('Y-m-d H:i:s',time())."===订单号{$orderid}申请跟踪号结束===\n\n";
	echo $logs;
}

$filename		= SCRIPT_PATH.'crontab/eub_tracknum/log/'.date('Y-m-d',time()).'_tracknumber.txt';
write_a_file($filename, $logs);
exit;

//检查是否为组合料号
function check_combine($sku){
	global $dbConn;
	$sql 	= "SELECT sku,count FROM pc_sku_combine_relation WHERE combineSku = '{$sku}'";
	$query 	= $dbConn->query($sql);
	$res 	= $dbConn->fetch_array($query);
	if ($res) {
		return $res;
	} else {
		return false;
	}
}

//获取料号的信息
function get_goods($sku){
	global $dbConn;
	$sql 	= "SELECT goodsName,goodsWeight FROM pc_goods WHERE sku = '{$sku}'";
	$query	= $dbConn->query($sql);
	$res 	= $dbConn->fetch_array($query);
	if ($res) {
		return $res;
	} else {
		return false;
	}
}

//获取订单的英文信息
function get_order_details($tabname,$id){
	global $dbConn;
	$sql 	= "SELECT itemTitle FROM om_unshipped_order_detail_extension_{$tabname} WHERE omOrderdetailId = '{$id}'";
	//echo $sql;
	$query	= $dbConn->query($sql);
	$res 	= $dbConn->fetch_array($query);
	if ($res) {
		return $res['itemTitle'];
	} else {
		return false;
	}
}


//申请EUB跟踪号
function get_eub_trackNumber($url,$xml_data){
	global $dbConn,$app_ver,$app_key,$orderid,$account,$userId;
	$header[] = "Content-type: text/xml";
	$header[] = "version: {$app_ver}";
	$header[] = "authenticate: {$app_key}";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_data);
	$response = curl_exec($ch);
	if (curl_errno($ch)) {
		return date('Y-m-d H:i:s',time())."===订单编号:{$orderid}申请跟踪号失败,原因：\n".curl_error($ch);
	}
	curl_close($ch);

	if (!empty($response)) {
		$xml = simplexml_load_string($response);
		$data	= json_encode($xml->children());
		$data	= json_decode($data);
		$times	= time();
		if (isset($data->mailnum)) {
			$track_number	= $data->mailnum;
			$sql 	= "INSERT INTO om_order_tracknumber (`tracknumber`,`omOrderId`,`addUser`,`createdTime`) values ('{$track_number}','{$orderid}','{$userId}','{$times}')";
			$query	= $dbConn->query($sql);
			$rid	= mysql_affected_rows();
			if ($rid) {
				$sql	= "DELETE FROM om_tracknumber_record_fail WHERE om_id = '{$orderid}'";
				$query	= $dbConn->query($sql);
				$delid	= mysql_affected_rows();
				return date('Y-m-d H:i:s',time())."===订单编号:{$orderid},申请的跟踪号为:".$data->mailnum;
			} else {
				return date('Y-m-d H:i:s',time())."===订单编号:{$orderid},插入跟踪号记录出错:".$sql;
			}
		} else {
			$err_msg= mysql_real_escape_string($data->description);
			$sql 	= "INSERT INTO om_order_notes(omOrderId,content,userId,createdTime) VALUES('{$orderid}','跟踪号申请错误日志:\n{$err_msg}','{$userId}','{$times}')";
			$query	= $dbConn->query($sql);
			//$rid	= $dbConn->insert_id();			
			$rid	= mysql_affected_rows();			
			$sql	= "UPDATE om_unshipped_order SET `orderType` = 725 , `orderStatus` = 800 WHERE id = '{$orderid}'";
			$query	= $dbConn->query($sql);
			$upid	= mysql_affected_rows();
			if (!$upid) {
				$sql 	= "REPLACE INTO om_tracknumber_record_fail (`om_id`,`err_msg`,`addTime`) values ('{$orderid}','{$err_msg}',{$times})";
				$query	= $dbConn->query($sql);
				$rid	= $dbConn->insert_id();
			}
			return date('Y-m-d H:i:s',time())."===订单编号:{$orderid}申请跟踪号失败,原因：\n".$data->description."===\n".$sql;
		}		
	}
}
exit;
?>
