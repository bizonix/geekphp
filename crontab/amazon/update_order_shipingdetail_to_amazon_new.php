<?php
//脚本参数检验
if ($argc != 4) {
	exit ("输入的参数不正确！Usage: /usr/bin/php	$argv[0] eBayAccount site hours \n");
}
error_reporting(-1);
//账号检验
$account	= trim($argv[1]);	//亚马逊账号
$site		= trim($argv[2]);	//亚马逊站点
$hours		= $argv[3];			//多少小时前的数据
$interval	= 500;				//单次上传跟踪号的数量
/*$account	= 'Finejo2099';
$site		= 'US';
$hours		= 2;*/

/*include_once "script_root_path.php";
include_once SCRIPT_ROOT."function_purchase.php";
include_once SCRIPT_ROOT."class_statistics.php";
include_once SCRIPT_ROOT."ebay_order_cron_config.php";*/

ini_set('max_execution_time', 3600);
if(!defined('WEB_PATH')){
	define("WEB_PATH","/data/web/order.valsun.cn/");
}
//define('SCRIPTS_PATH_CRONTAB', '/data/web/erpNew/order.valsun.cn/crontab/');    
require_once WEB_PATH."crontab/scripts.comm.php";
require_once WEB_PATH_CONF_SCRIPTS."script.ebay.config.php";
require_once WEB_PATH_LIB_SCRIPTS_AMAZON."amazon_order_func.php";

$path		= SCRIPT_DATA_LOG;

if (!preg_match('#^[\da-zA-Z]+$#i', $account)) {	//验证账号
	exit ("Invalid amazon account: $account!");
}
if(strlen($site) < 1) {	//验证站点
	exit ("Invalid sites: $site!");
}
if(preg_match('/^\d+$/',$hours) < 1) {	//验证小时数
	exit ("Invalid hours: $hours!");
}

$nowTime	= time();
$date		= date('Y-m',$nowTime);
$logPath	= $path.'amazon_track_number_upload/'.$site.'/'.$account.'/'.$date.'/'.date('d',$nowTime).'/';
if(!is_dir($logPath)) {
	$mkStatus = mkdir($logPath,0777,true);
	if(!$mkStatus) {
		exit('Can\'t create log dir!');
	}
}

//测试是否可以建立Log日志文件
$errStatus = errorLog("test operate",'test');
if(!$errStatus) {
	exit('Can\'t create Log file');
}

if (strtoupper($site) == "US") {
	$keyname = WEB_PATH_CONF_SCRIPTS_KEYS_AMAZON . "amazon_keys_{$account}.php";
} elseif(strtoupper($site) == "UK") {
	$ext = strtolower($site);
	$keyname = WEB_PATH_CONF_SCRIPTS_KEYS_AMAZON . "amazon_keys_{$ext}_{$account}.php";
}
if (file_exists($keyname)) {
	include_once $keyname;
} else {
	exit ("未找对应的key文件!\n");
}

define('APPLICATION_NAME', $APPLICATION_NAME);
define('APPLICATION_VERSION', $APPLICATION_VERSION);
define('AWS_ACCESS_KEY_ID', $AWS_ACCESS_KEY_ID);
define('AWS_SECRET_ACCESS_KEY', $AWS_SECRET_ACCESS_KEY);
define('MERCHANT_ID', $MERCHANT_ID);
define('MARKETPLACE_ID', $MARKETPLACE_ID);
/*set_include_path(get_include_path().PATH_SEPARATOR.SCRIPT_ROOT);

function __autoload($className){
	$filePath = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
	$includePaths = explode(PATH_SEPARATOR, get_include_path());
	foreach($includePaths as $includePath){
		if(file_exists($includePath . DIRECTORY_SEPARATOR . $filePath)){		
			require_once $filePath;
			return;
		}
	}
}*/
error_reporting(-1);
set_include_path(get_include_path().PATH_SEPARATOR.WEB_PATH_LIB_SDK_AMAZON);
//var_dump(get_include_path());
//spl_autoload_register(".php");
/*function __autoload($className){
	//echo $className;exit;
	$filePath = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
	var_dump($filePath);
	$includePaths = explode(PATH_SEPARATOR, get_include_path());
	foreach($includePaths as $includePath){
		if(file_exists($includePath . DIRECTORY_SEPARATOR . $filePath)){		
			require_once $filePath;
			return;
		}else{
			echo $filePath."is not exist \n";
		}
	}
}*/
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebService/Client.php';
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebService/Interface.php';
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebService/Exception.php';
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebService/Model/SubmitFeedRequest.php';
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebService/Model/SubmitFeedResponse.php';
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebService/Model/SubmitFeedResult.php';
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebService/Model/GetFeedSubmissionResultRequest.php';

/*$_SESSION['user']	= 'vipchen';
$user	= $_SESSION['user'];*/

$serviceURL = "https://mws.amazonservices.com"; //默认为us站点的url
if (strtoupper($site) == "UK") { //uk站点的url
	$serviceURL = "https://mws.amazonservices.co.uk";
}

$config = array (
	'ServiceURL' => $serviceURL,
	'ProxyHost' => null,
	'ProxyPort' => -1,
	'MaxErrorRetry' => 3,
);

$service = new MarketplaceWebService_Client(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, $config, APPLICATION_NAME, APPLICATION_VERSION);
//var_dump($service); exit;
//exit('ffff');
$marketplaceIdArray = array (
	"Id" => array (
		MARKETPLACE_ID
	)
);
$now = date(DATE_ATOM);		//当前时间

$nowtime	= time();
$mctime		= $nowtime;
$start		= strtotime(date('Y-m-d',$nowtime-(3600*$hours)).' 00:00:00');
$end		= strtotime(date('Y-m-d',$nowtime).' 23:59:59');

$omAvailableAct = new OmAvailableAct();
$where = 'WHERE is_delete=0 ';
$where .= 'AND platformId = 11 ';
$GLOBAL_EBAY_ACCOUNT = $omAvailableAct->act_getTNameList2arrById('om_account', 'id', 'account', $where);
//var_dump($GLOBAL_EBAY_ACCOUNT);
$FLIP_GLOBAL_EBAY_ACCOUNT = array_flip($GLOBAL_EBAY_ACCOUNT);

$accountId = $FLIP_GLOBAL_EBAY_ACCOUNT[$account];


/*$order_sql	= "	select ebay_orderid,ebay_account,ebay_ordersn,ebay_countryname,
						ebay_id,ebay_tracknumber,ebay_carrier,ebay_combine,recordnumber
				from 	ebay_order 
				where 	ebay_user='$user' 
				AND  	((scantime>=$start AND scantime<=$end) or ebay_status = '614')
				AND 	ebay_combine!='1'  
				AND 	(ShippedTime ='' or ShippedTime is null) 
				AND		ebay_tracknumber!=''
				AND		ebay_carrier!='' 
				AND 	ebay_account = '".$account."' 
				AND		ebay_site = '".$site."'";// limit 0,7";*/
				
/*$order_sql	= "	select 	a.omOrderId
				from 	om_unshipped_order_warehouse as a
				where	(a.weighTime BETWEEN $start AND $end)
				and 	a.storeId = 1 ";
$order_db	= $dbConn->query($order_sql);
$orders		= $dbConn->fetch_array_all($order_db);
//var_dump($orders); echo "\n"; exit;
$handle_cnt=count($orders);
if($handle_cnt<=0 ){
	exit("No order to handel\n");
}*/
$delivery_arr = CommonModel::getCarrierListById();

$order_sql	= 	"select a.id,a.recordNumber,a.transportId,a.combinePackage,a.orderStatus 
				from om_shipped_order as a 
				left join om_shipped_order_warehouse as b
				on a.id = b.omOrderId
				left join om_shipped_order_extension_amazon as c
				on a.id = c.omOrderId
				where a.accountId = '{$accountId}'
				and	c.site = '{$site}'
				and a.orderStatus ='".C("STATESHIPPED")."'
				and a.orderType ='".C("STATEHASSHIPPED_CONV")."'
				and (b.weighTime BETWEEN $start AND $end) and (a.ShippedTime ='' or a.ShippedTime is null) 
				ORDER BY b.weighTime ";
$order_db	= $dbConn->query($order_sql);
$orders		= $dbConn->fetch_array_all($order_db);

/*$order_sql	=	" select ebay_orderid,ebay_account,ebay_ordersn,ebay_countryname,
						ebay_id,ebay_tracknumber,ebay_carrier,ebay_combine,recordnumber
				from 	ebay_order where recordnumber in(
'002-8137713-8318634',
'115-9924496-2629023',
'107-8620600-1710651',
'109-4445248-2697864',
'112-8959307-6029052',
'112-8141525-2093001')

";*/

//$order_db	= $dbcon->execute($order_sql);
//$orders		= $dbcon->getResultArray($order_db);

//print_r($orders);exit;
//echo count($orders);exit;
//print_r($dbcon->error);exit;
//print_r($orders);exit;

//if(empty($orders)) {
//	exit('未找到符合条件的订单，请检查账号、账号是否输入正确!');
//}

$handle_cnt	= count($orders);
//echo $handle_cnt; echo "\n"; exit;
if(!$handle_cnt){
	exit("no order to handle!\n");	
}
$quantity	= 0;
$orderIds	= array();
$trackNum	= array();
$maxNum		= ceil($handle_cnt/$interval);

//echo $maxNum;exit;
$ids		= 0;
$times		= 0;
for($i = 0; $i < $maxNum; $i++) {
	$dat = array();	//对数组清零
	$orderIds	= array();
	for($num = $i * $interval; $num < $i * $interval + $interval; $num++) {
		$express = '';
		$carrier = '';
		if(!isset($orders[$num])) {
			continue;
		}
		$ids = $num;
		$carrier = $delivery_arr[$orders[$num]['transportId']];
		$orderTracknumber = OrderindexModel::selectOrderTracknumber('where is_delete=0 and omOrderId = '.$orders[$num]['id']);
		$tracknumber = $orderTracknumber[0]['tracknumber'];
		//echo $tracknumber; echo "\n";
		switch($carrier) {
			case 'EUB':
				$carrier	= 'China Post';
				$express	= 'ePackage';
				break;
			case 'FedEx' : 
				$carrier	= 'FedEx';
				$express	= 'Express';
				break;
			case '德国邮政' : 
				$carrier	= 'Specify carrier';
				$express	= 'Deutsche Post';
				break;
			case '中国邮政平邮'		: 
			case '中国邮政挂号' : 
				$carrier	= 'China Post';
				$express	= 'First Class';
				break;
			default : 
				errorLog($orders[$num]['recordNumber'].': 没有快递方式','N');	
				continue;
		}
		$dat[] = array (
			"AmazonOrderID"			=> $orders[$num]['recordNumber'],//"110-3420043-3481829",
			"FulfillmentDate"		=> $now,
			"CarrierName"			=> $carrier,//"China Post",	//运输方式，
			"ShippingMethod"		=> $express,//"ePackage",	//快递方式
			"ShipperTrackingNumber" => $tracknumber,//"LN094927927CN",  
			//"AmazonOrderItemCode"	=>	"",
			//"Quantity"				=>	"1"
		);
		$trackNum[] = $tracknumber;
		$orderIds[] = $orders[$num]['recordNumber'];
		//break;
	}
	//print_r($dat);exit;
	//echo implode("','",$trackNum);exit;
	/***************begin--- 上传跟踪号*******************/
	$file		= $logPath.end($orderIds).'.txt';
	$xmlFile	= $logPath.end($orderIds).'_submitResult.txt';
	//echo $file;exit;
	$feed = setFeedXml($dat);
	//print_r($feed);exit;
	
	error_reporting(-1);
	file_put_contents($file,$feed);
	file_put_contents($xmlFile,'');
	$feedHandle = fopen($file,'rw+');// fopen('php://temp', 'rw+');
	fwrite($feedHandle, $feed);
	rewind($feedHandle);

	//设置接口参数
	$parameters = array (
		'Merchant'			=> MERCHANT_ID,
		'MarketplaceIdList' => $marketplaceIdArray,
		'FeedType'			=> '_POST_ORDER_FULFILLMENT_DATA_',
		'FeedContent'		=> $feedHandle,
		'PurgeAndReplace'	=> false,
		'ContentMd5'		=> base64_encode(md5(stream_get_contents($feedHandle), true))
	);
	rewind($feedHandle);
	//echo "==============="; echo "\n";
	$request = new MarketplaceWebService_Model_SubmitFeedRequest($parameters);
	$submissionId = invokeSubmitFeed($service, $request);
	//echo $submissionId."\r\n";
	fclose($feedHandle);
	//***************end------- 上传跟踪号*******************
	sleep(120);	//暂停120秒再查询$submissionId	
	//***************begin----- 检测跟踪号状态*******************
	getFeedStatus($submissionId,$service,$xmlFile);
	while(true) {
		if(is_file($xmlFile)) {
			$requestXml = file_get_contents($xmlFile);
			$xmlResult = xml_to_array($requestXml);
			if(isset($xmlResult['ErrorResponse']['Error'])) {
				sleep(90);
				getFeedStatus($submissionId,$service,$xmlFile);
			} else {
				break;
			}
		} else {
			break;
		}
	}
	if(is_file($xmlFile)) {
		$requestXml = file_get_contents($xmlFile);
		$xmlResult = xml_to_array($requestXml);
		if(isset($xmlResult['AmazonEnvelope']['Message']['ProcessingReport']['StatusCode'])) {
			$errStatus = $xmlResult['AmazonEnvelope']['Message']['ProcessingReport']['StatusCode'];
			if(isset($xmlResult['AmazonEnvelope']['Message']['ProcessingReport']['StatusCode']['Result'][0]['ResultCode']) && $xmlResult['AmazonEnvelope']['Message']['ProcessingReport']['StatusCode']['Result'][0]['ResultCode'] == 'Error') {
				$errMsg = $xmlResult['AmazonEnvelope']['Message']['ProcessingReport']['StatusCode']['Result'][0]['ResultDescription'];
				errorLog(implode(',',$orderIds).': '.$errMsg."",'N');	
				continue;
			}
			if($errStatus == 'Complete') {
				$status = update_order_shippedmarked_time($orderIds,$mctime);
				if(!$status) {
					$msg = implode(',',$orderIds).':数据库的状态更新失败';
					errorLog($msg,'N');
				}
				errorLog(implode(',',$orderIds).': Upload tracking number success ','Y');	
			} else {
				errorLog(implode(',',$orderIds).': Upload tracking number Failure a','N');	
			}
		} else {
			errorLog(implode(',',$orderIds).': Upload tracking number Failure b','N');
		}
	} else {
		errorLog(implode(',',$orderIds).': Upload tracking number Failure c','N');
	}
	/***************begin----- 检测跟踪号状态*******************/
	$times++ ;
	if($times >= 15) {	//控制上传api的次数，api规定不能超过15次。15次后需要再等待30分钟才能再继续上传
		errorLog('重复上传超过有效次数(15次)! 最后上传的订单号为: '.$orderIds, 'N');
		break;
	}
	sleep(30); //等待30秒再重新上传第二次
}
errorLog(date('Y-m-d H:i:s',$start).'至'.date('Y-m-d H:i:s',$end).'的跟踪号上传完成!',"Y");

//设置发送请求的xml
function setFeedXml($ret) {
	$xmlHeader = '<?xml version="1.0" encoding="UTF-8"?>
						<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
						<Header>
							<DocumentVersion>1.01</DocumentVersion>
							<MerchantIdentifier>ListingsContentHandler</MerchantIdentifier>
						</Header>
						<MessageType>OrderFulfillment</MessageType>';
	$xmlBody = "";
	foreach ($ret as $k => $v) {
		$index = $k +1;
		$xmlBody .= '<Message>
							<MessageID>' . $index . '</MessageID>
							<OperationType>Update</OperationType>
							<OrderFulfillment>
								<AmazonOrderID>' . $v['AmazonOrderID'] . '</AmazonOrderID>
								<FulfillmentDate>' . $v['FulfillmentDate'] . '</FulfillmentDate>
								<FulfillmentData>
									<CarrierName>' . $v['CarrierName'] . '</CarrierName>
									<ShippingMethod>' . $v['ShippingMethod'] . '</ShippingMethod>
									<ShipperTrackingNumber>' . $v['ShipperTrackingNumber'] . '</ShipperTrackingNumber>
								</FulfillmentData>
						</OrderFulfillment>
						</Message>';
	}
	/*
	<Item>
								<AmazonOrderItemCode>'.$v['AmazonOrderItemCode'].'</AmazonOrderItemCode>
								<Quantity>'.$v['Quantity'].'</Quantity>
							</Item>
	*/
	$xmlFooter = '</AmazonEnvelope>';
	return $xmlHeader . $xmlBody . $xmlFooter;
}
/***********************************************************************************************
 *	上传标记发货xml文件到Amazon
 *	@param MarketplaceWebService_Interface $service instance of MarketplaceWebService_Interface
 *	@param mixed $request MarketplaceWebService_Model_SubmitFeed or array of parameters
 */
function invokeSubmitFeed(MarketplaceWebService_Interface $service, $request) {
	try {
		$response = $service->submitFeed($request);
		//file_put_contents('d:/public.txt',$response);
		//echo ("Service Response\n");
		//echo ("=============================================================================\n");
		//echo ("        SubmitFeedResponse\n");
		if ($response->isSetSubmitFeedResult()) {
			//echo ("            SubmitFeedResult\n");
			$submitFeedResult = $response->getSubmitFeedResult();
			if ($submitFeedResult->isSetFeedSubmissionInfo()) {
				//echo ("                FeedSubmissionInfo\n");
				$feedSubmissionInfo = $submitFeedResult->getFeedSubmissionInfo();
			}
		}
		return $feedSubmissionInfo->getFeedSubmissionId();
	} catch (MarketplaceWebService_Exception $ex) {
		$str = "Caught Exception: " . $ex->getMessage() . "\r\n"."Response Status Code: " . $ex->getStatusCode() . "\r\n"."Error Code: " . $ex->getErrorCode() . "\r\n"."Error Type: " . $ex->getErrorType() . "\r\n"."Request ID: " . $ex->getRequestId() . "\r\n"."XML: " . $ex->getXML() . "\r\n"."ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\r\n";
		errorLog($str,'N');
	}
}

//获取FeedSubmissionId的状态
function getFeedStatus($submitId,$service,$xmlFile) {
	if(preg_match('/^\d+$/',$submitId) < 1) {
		errorLog($submitId,':submissionId 不正确!','N');
		return false;
	}
	if(is_file($xmlFile)) {
		$resource = @fopen($xmlFile, 'w+');
	} else {
		$resource = @fopen('php://memory', 'rw+');
	}

	$parameters = array (
		'Merchant' => MERCHANT_ID,
		'FeedSubmissionId' => $submitId,
		'FeedSubmissionResult' => $resource,//@fopen('php://memory', 'rw+'),
	);

	$request = new MarketplaceWebService_Model_GetFeedSubmissionResultRequest($parameters);

	//$request = new MarketplaceWebService_Model_GetFeedSubmissionResultRequest();
	$request->setMerchant(MERCHANT_ID);
	$request->setFeedSubmissionId($submitId);

	if(is_file($xmlFile)) {
		$request->setFeedSubmissionResult(@fopen($xmlFile, 'rw+'));
	} else {
		$request->setFeedSubmissionResult(@fopen('php://memory', 'rw+'));
	}
	invokeGetFeedSubmissionResult($service, $request);
}
//根据
function invokeGetFeedSubmissionResult(MarketplaceWebService_Interface $service, $request) 
{
	try {
			$response = $service->getFeedSubmissionResult($request);
			$response = $service->getFeedSubmissionResult($request);

			//file_put_contents('d:/amazon_result.txt',$response);
			//echo ("Service Response\n");
			//echo ("=============================================================================\n");

			//echo("        GetFeedSubmissionResultResponse\n");
			if ($response->isSetGetFeedSubmissionResultResult()) {
				$getFeedSubmissionResultResult = $response->getGetFeedSubmissionResultResult(); 
				//file_put_contents($path.'fffff.txt',$getFeedSubmissionResultResult);
				//echo ("            GetFeedSubmissionResult");
			  
				if ($getFeedSubmissionResultResult->isSetContentMd5()) {
					//echo ("                ContentMd5");
					//echo ("                " . $getFeedSubmissionResultResult->getContentMd5() . "\n");
				}
			}
			if ($response->isSetResponseMetadata()) { 
				//echo("            ResponseMetadata\n");
				$responseMetadata = $response->getResponseMetadata();
				if ($responseMetadata->isSetRequestId()) 
				{
					//echo("                RequestId\n");
					//echo("                    " . $responseMetadata->getRequestId() . "\n");
				}
			} 

			//echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");
	} catch (MarketplaceWebService_Exception $ex) {
		$str = "Caught Exception: " . $ex->getMessage() . "\r\n"."Response Status Code: " . $ex->getStatusCode() . "\r\n"."Error Code: " . $ex->getErrorCode() . "\r\n"."Error Type: " . $ex->getErrorType() . "\r\n"."Request ID: " . $ex->getRequestId() . "\r\n"."XML: " . $ex->getXML() . "\r\n"."ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\r\n";
		errorLog($str,'N');/*
		echo("Caught Exception: " . $ex->getMessage() . "\n");
		echo("Response Status Code: " . $ex->getStatusCode() . "\n");
		echo("Error Code: " . $ex->getErrorCode() . "\n");
		echo("Error Type: " . $ex->getErrorType() . "\n");
		echo("Request ID: " . $ex->getRequestId() . "\n");
		echo("XML: " . $ex->getXML() . "\n");
		echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");*/
	}
}

/**
 * 更新订单的ebay_markettime和ShippedTime
 */
function update_order_shippedmarked_time($ebay_ordersn,$mctime) {
	global $dbConn;
	
	$tableName = "om_shipped_order";
	$data['marketTime'] = $mctime;
	$data['ShippedTime'] = $mctime;
	$where = " where recordNumber IN ('".implode("','",$ebay_ordersn)."') ";
	OrderindexModel::updateOrder($tableName,$data,$where);
	/*$order_db	= mysql_query($sql,$link);*/
	//$dbConn->query($sql);
	//errorLog($sql,'N');
	//echo "\r\n".$sql."\r\n";
	return $order_db;
}

/**
 * 错误日志
 */
function errorLog($message,$type) {
	$nowTime = time();
	//$now	= date('Y-m-d_H-i-s',$nowTime);
	$date	= date('Y-m',$nowTime);
	global $logPath;
	if(!is_dir($logPath)) {
		$dirStatus = mkdir($logPath,0777,true);		//尝试建立日志目录
		if(!$dirStatus) {
			exit('日志目录建立失败!');
		}
	}
	switch($type) {
		case 'Y':
			$status = error_log("\r\n{$message}\r\n",3,$logPath.'success.log');
			break;
		case 'N':
			$status = error_log("\r\n{$message}\r\n",3,$logPath.'error.log');
			break;
		case 'db':
			$status = error_log("\r\n{$message}\r\n",3,$logPath.'db_error.log');
			break;
		case 'test':
			$status = error_log("\r\nThis is a test Log file!\r\n",3,$logPath.'test_error.log');
			break;
		case 'submitId';
			$status = error_log("\r\n",3,$logPath.'submitId.log');
			break;
	}
	return $status;
}

/**
 * 将xml转为array
 */
function xml_to_array( $xml )
{
	$reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
	if(preg_match_all($reg, $xml, $matches))
	{
		$count = count($matches[0]);
		$arr = array();
		for($i = 0; $i < $count; $i++)
		{
			$key = $matches[1][$i];
			$val = xml_to_array( $matches[2][$i] );  // 递归
			if(array_key_exists($key, $arr))
			{
				if(is_array($arr[$key]))
				{
					if(!array_key_exists(0,$arr[$key]))
					{
						$arr[$key] = array($arr[$key]);
					}
				}else{
					$arr[$key] = array($arr[$key]);
				}
				$arr[$key][] = $val;
			}else{
				$arr[$key] = $val;
			}
		}
		return $arr;
	}else{
		return $xml;
	}
}
