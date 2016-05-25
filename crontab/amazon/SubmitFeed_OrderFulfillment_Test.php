<?php

/*****************************************************************
 *	Amazon 标记发货实现！ SubmitFeed  OrderFulfillment
 *	_POST_ORDER_FULFILLMENT_DATA_
 *	windayzhong	2013-07-22
 */
//include_once "script_root_path.php";
//include_once SCRIPT_ROOT."function_purchase.php";
//include_once SCRIPT_ROOT."class_statistics.php";
//include_once SCRIPT_ROOT."ebay_order_cron_config.php";
//
//$account	=	trim($argv[1]);
//$site		=	trim($argv[2]);
//
//if(!preg_match('#^[\da-zA-Z]+$#i',$account)){
//	exit("Invalid ebay account: $account!");
//}
//
//$allow_site	=	array("uk","us");
//if(empty($site) || !in_array($site, $allow_site)){
//	exit("Invalid or empty site!");
//}
//if($site == "us"){
//	$keyname = SCRIPT_ROOT."ebaylibrary/keys/amazon_keys_{$account}.php";
//}else{
//	$keyname = SCRIPT_ROOT."ebaylibrary/keys/amazon_keys_{$site}_{$account}.php";
//}
//if (file_exists($keyname)){
//	include_once $keyname;
//}else{
//	exit("未找对应的key文件!");
//}
//
//define('APPLICATION_NAME',		$APPLICATION_NAME);
//define('APPLICATION_VERSION',	$APPLICATION_VERSION);
//define('AWS_ACCESS_KEY_ID',		$AWS_ACCESS_KEY_ID);
//define('AWS_SECRET_ACCESS_KEY', $AWS_SECRET_ACCESS_KEY);
//define('MERCHANT_ID',			$MERCHANT_ID);
//define('MARKETPLACE_ID',		$MARKETPLACE_ID);
//
//set_include_path(get_include_path().PATH_SEPARATOR.SCRIPT_ROOT);
//
////自动加载类文件
//function __autoload($className){
//	$filePath	=	str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
//	$includePaths	=	explode(PATH_SEPARATOR, get_include_path());
//	foreach($includePaths as $includePath){
//		if(file_exists($includePath . DIRECTORY_SEPARATOR . $filePath)){
//			require_once $filePath;
//			return;
//		}
//	}
//}

@ session_start();
error_reporting(-1);
ini_set('max_execution_time', 1800);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai'); //设置时区
//require_once "/data/web/erpNew/order.valsun.cn/framework.php";
require_once "D:/wamp/www/order.valsun.cn/framework.php"; //本机初始化文件
Core :: getInstance();
require_once WEB_PATH . "conf/scripts/script.config.php";

//脚本参数检验
if ($argc != 3) {
	exit ("Usage: /usr/bin/php	$argv[0] eBayAccount minutes_ago \n");
}
//账号检验
$account = trim($argv[1]); //账号
$site = trim($argv[2]); //站点

/*
if($minute==0||$minute>2000){
	exit("minutes_ago 只能在1~1200之间\n");
}
*/
if (!preg_match('#^[\da-zA-Z]+$#i', $account)) {
	exit ("Invalid amazon account: $account!");
}

if (strtoupper($site) == "US") {
	$keyname = WEB_PATH_CONF_SCRIPTS_KEYS_AMAZON . "amazon_keys_{$account}.php";
} else {
	$ext = strtolower($site);
	$keyname = WEB_PATH_CONF_SCRIPTS_KEYS_AMAZON . "amazon_keys_{$ext}_{$account}.php";
}
if (file_exists($keyname)) {
	include_once $keyname;
} else {
	exit ("未找对应的key文件!");
}

define('APPLICATION_NAME', $APPLICATION_NAME);
define('APPLICATION_VERSION', $APPLICATION_VERSION);
define('AWS_ACCESS_KEY_ID', $AWS_ACCESS_KEY_ID);
define('AWS_SECRET_ACCESS_KEY', $AWS_SECRET_ACCESS_KEY);
define('MERCHANT_ID', $MERCHANT_ID);
define('MARKETPLACE_ID', $MARKETPLACE_ID);

set_include_path(WEB_PATH_LIB_SDK . 'amazon');

require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebService/Client.php';
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebService/Interface.php';
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebService/Exception.php';
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebService/Model/SubmitFeedRequest.php';

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

$marketplaceIdArray = array (
	"Id" => array (
		MARKETPLACE_ID
	)
);
$now = date(DATE_ATOM); //提供给amazon mws的必须是带时区的DATE_ATOM格式

$dat = array (
	array (
			"AmazonOrderID" => "203-9224758-5617961", //recordNumber
		"FulfillmentDate" => $now, //date
		"CarrierName" => "Specify carrier", //平台上的运货名称
		"ShippingMethod" => "Deutschland Post", //平台上的发货方式
		"ShipperTrackingNumber" => "RG267410498DE", //跟踪号
		//"AmazonOrderItemCode"	=>	"",
		//"Quantity"				=>	"1"

	)
);
//$tName = 'om_unshipped_order';
//$select = '';
//OmAvailableModel::getTNameList();

$feed = setFeedXml($dat);

$feedHandle = fopen('php://temp', 'rw+');
fwrite($feedHandle, $feed);
rewind($feedHandle);

//设置接口参数
$parameters = array (
	'Merchant' => MERCHANT_ID,
	'MarketplaceIdList' => $marketplaceIdArray,
	'FeedType' => '_POST_ORDER_FULFILLMENT_DATA_',
	'FeedContent' => $feedHandle,
	'PurgeAndReplace' => false,
	'ContentMd5' => base64_encode(md5(stream_get_contents($feedHandle
), true)),);
rewind($feedHandle);
$request = new MarketplaceWebService_Model_SubmitFeedRequest($parameters);
invokeSubmitFeed($service, $request);
fclose($feedHandle);

//组装xml文件
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
		echo ("Service Response\n");
		echo ("=============================================================================\n");
		echo ("        SubmitFeedResponse\n");
		if ($response->isSetSubmitFeedResult()) {
			echo ("            SubmitFeedResult\n");
			$submitFeedResult = $response->getSubmitFeedResult();
			if ($submitFeedResult->isSetFeedSubmissionInfo()) {
				echo ("                FeedSubmissionInfo\n");
				$feedSubmissionInfo = $submitFeedResult->getFeedSubmissionInfo();
				if ($feedSubmissionInfo->isSetFeedSubmissionId()) {
					echo ("                    FeedSubmissionId\n");
					echo ("                        " . $feedSubmissionInfo->getFeedSubmissionId() . "\n"); //amazon的上传id，可用来查询是否上传成功
				}
				if ($feedSubmissionInfo->isSetFeedType()) {
					echo ("                    FeedType\n");
					echo ("                        " . $feedSubmissionInfo->getFeedType() . "\n");
				}
				if ($feedSubmissionInfo->isSetSubmittedDate()) {
					echo ("                    SubmittedDate\n");
					echo ("                        " . $feedSubmissionInfo->getSubmittedDate()->format(DATE_FORMAT) . "\n");
				}
				if ($feedSubmissionInfo->isSetFeedProcessingStatus()) {
					echo ("                    FeedProcessingStatus\n");
					echo ("                        " . $feedSubmissionInfo->getFeedProcessingStatus() . "\n");
				}
				if ($feedSubmissionInfo->isSetStartedProcessingDate()) {
					echo ("                    StartedProcessingDate\n");
					echo ("                        " . $feedSubmissionInfo->getStartedProcessingDate()->format(DATE_FORMAT) . "\n");
				}
				if ($feedSubmissionInfo->isSetCompletedProcessingDate()) {
					echo ("                    CompletedProcessingDate\n");
					echo ("                        " . $feedSubmissionInfo->getCompletedProcessingDate()->format(DATE_FORMAT) . "\n");
				}
			}
		}
		if ($response->isSetResponseMetadata()) {
			echo ("            ResponseMetadata\n");
			$responseMetadata = $response->getResponseMetadata();
			if ($responseMetadata->isSetRequestId()) {
				echo ("                RequestId\n");
				echo ("                    " . $responseMetadata->getRequestId() . "\n");
			}
		}

		echo ("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");
	} catch (MarketplaceWebService_Exception $ex) {
		echo ("Caught Exception: " . $ex->getMessage() . "\n");
		echo ("Response Status Code: " . $ex->getStatusCode() . "\n");
		echo ("Error Code: " . $ex->getErrorCode() . "\n");
		echo ("Error Type: " . $ex->getErrorType() . "\n");
		echo ("Request ID: " . $ex->getRequestId() . "\n");
		echo ("XML: " . $ex->getXML() . "\n");
		echo ("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
	}
}
?>
