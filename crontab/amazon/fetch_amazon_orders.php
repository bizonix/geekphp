<?php
/**
 *  PHP Version 5
 *
 *  @category    Amazon
 *  @package     MarketplaceWebServiceOrders
 *  @copyright   Copyright 2008-2009 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *  @link        http://aws.amazon.com
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     2011-01-01
 */
//脚本参数检验
if ($argc != 4) {
	exit ("Usage: /usr/bin/php	$argv[0] eBayAccount minutes_ago \n");
}
//账号检验
$account = trim($argv[1]);
$minute = intval($argv[2]);
$site = trim($argv[3]);

/*
if($minute==0||$minute>2000){
	exit("minutes_ago 只能在1~1200之间\n");
}
*/
if (!preg_match('#^[\da-zA-Z]+$#i', $account)) {
	exit ("Invalid amazon account: $account!");
}

if(!defined('WEB_PATH')){
	define("WEB_PATH","/data/web/order.valsun.cn/");
}
//define('SCRIPTS_PATH_CRONTAB', '/data/web/erpNew/order.valsun.cn/crontab/');    
require_once WEB_PATH."crontab/scripts.comm.php";
require_once WEB_PATH_CONF_SCRIPTS."script.ebay.config.php";
require_once WEB_PATH_LIB_SCRIPTS_AMAZON."amazon_order_func.php";

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
//echo "\n".get_include_path()."\n";
set_include_path(get_include_path().PATH_SEPARATOR.WEB_PATH_LIB_SDK_AMAZON);
//
//echo "\n".get_include_path()."\n";
//
/*function __autoload($className){
	$filePath = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
	$includePaths = explode('/', get_include_path());
	foreach($includePaths as $includePath){
		if(file_exists($includePath . DIRECTORY_SEPARATOR . $filePath)){
			require_once $filePath;
			return;
		}
	}
}*/
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebServiceOrders/Client.php';
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebServiceOrders/Interface.php';
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebServiceOrders/Exception.php';
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebServiceOrders/Model/ListOrdersRequest.php';
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebServiceOrders/Model/MarketplaceIdList.php';
require_once WEB_PATH_LIB_SDK . 'amazon/MarketplaceWebServiceOrders/Model/ListOrderItemsRequest.php';

$config = array (
	'ServiceURL' => $serviceUrl,
	'ProxyHost' => null,
	'ProxyPort' => -1,
	'MaxErrorRetry' => 3,
);

$start = date("Y-m-d H:i:s", (time() - $minute * 60));

//$order_statistics = new OrderStatistics();

$service = new MarketplaceWebServiceOrders_Client(AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, APPLICATION_NAME, APPLICATION_VERSION, $config);
$request = new MarketplaceWebServiceOrders_Model_ListOrdersRequest();
$request->setSellerId(MERCHANT_ID);
$request->setCreatedAfter(new DateTime($start, new DateTimeZone('UTC')));
//$request->setCreatedBefore(new DateTime('2012-08-03 12:00:00', new DateTimeZone('UTC')));
$marketplaceIdList = new MarketplaceWebServiceOrders_Model_MarketplaceIdList();
$marketplaceIdList->setId(array (
	MARKETPLACE_ID
));
$request->setMarketplaceId($marketplaceIdList);
//由account得出其accountId
$tName = 'om_account';
$select = 'id';
$where = "WHERE account='$account' AND platformId=11"; //亚马孙平台id为11
$accountIdList = OmAvailableModel :: getTNameList($tName, $select, $where);
if (empty ($accountIdList)) {
	exit ("$account is not in om_account!\n");
}
$accountId = $accountIdList[0]['id'];

$transportList = CommonModel::getCarrierListById();
$flip_transportList = array_flip($transportList);
//var_dump($flip_transportList); exit;

invokeListOrders($service, $request);
/**
  * List Orders Action Sample
  * ListOrders can be used to find orders that meet the specified criteria.
  *
  * @param MarketplaceWebServiceOrders_Interface $service instance of MarketplaceWebServiceOrders_Interface
  * @param mixed $request MarketplaceWebServiceOrders_Model_ListOrders or array of parameters
  */
function invokeListOrders(MarketplaceWebServiceOrders_Interface $service, $request) {

	global $account, $site, $accountId, $flip_transportList;

	try {
		$response = $service->listOrders($request);
		echo ("Service Response\n");
		echo ("=============================================================================\n");
		if ($response->isSetListOrdersResult()) {
			//echo ("ListOrdersResult\n");
			$listOrdersResult = $response->getListOrdersResult();
			if ($listOrdersResult->isSetNextToken()) {
				//echo ("	NextToken----");
				//echo ($listOrdersResult->getNextToken() . "\n");
			}
			if ($listOrdersResult->isSetCreatedBefore()) {
				//echo ("CreatedBefore----");
				//echo ($listOrdersResult->getCreatedBefore() . "\n");
			}
			if ($listOrdersResult->isSetLastUpdatedBefore()) {
				//echo ("LastUpdatedBefore----");
				//echo ($listOrdersResult->getLastUpdatedBefore() . "\n");
			}
			if ($listOrdersResult->isSetOrders()) {
				$orders = $listOrdersResult->getOrders();
				$orderList = $orders->getOrder();
				$orderIndex = 1;
				$now = time();
				//BaseModel :: begin(); //开始事务
				foreach ($orderList as $order) {
					echo ("***********Orders $orderIndex************\n\n");
					$orderIndex++;
					$orderData = array (); //om_unshipped_order
					$orderUserInfoData = array (); //om_unshipped_order_userInfo
					$orderExtenData = array (); //om_unshipped_order_extension
					$street2 = ''; //街道2
					$street3 = ''; //街道3

					if ($order->isSetAmazonOrderId() && $order->isSetOrderStatus() && $order->getOrderStatus() == 'Unshipped') {
						//$orderData['ebay_status'] = 1; //同步进来的订单的状态
						$orderData['recordNumber'] = $order->getAmazonOrderId(); //平台上的订单id
						//echo "AmazonOrderId ==== {$orderData['recordNumber']}\n";
					} else {
						//echo 'getOrderStatus======' . $order->getOrderStatus() . "\n";
						if ($order->getOrderStatus() == 'Shipped') { //表示已经发货了的订单
							$shippedRecordNum = $order->getAmazonOrderId();
							$tName = 'om_shipped_order';
							$where = "WHERE recordNumber='$shippedRecordNum' and accountId='$accountId' and ShippedTime=''";
							$set = "SET ShippedTime='$now'"; //标记发货时间
							$affectRow = OmAvailableModel :: updateTNameRow($tName, $set, $where);
							if ($affectRow) {
								echo "update $shippedRecordNum ShippedTime success" . "\n";
							} else {
								echo "update $shippedRecordNum ShippedTime fail may be has shippedTime or had no this recordNumber" . "\n";
							}
						}
						continue;
					}
					if ($order->isSetPurchaseDate()) { //亚马逊平台上的下单时间
						$orderData['ordersTime'] = strtotime($order->getPurchaseDate());
						//echo ("addTime  ==== {$order->getPurchaseDate()}\n");
					}
					if ($order->isSetLastUpdateDate()) { //支付时间
						$orderData['paymentTime'] = strtotime($order->getLastUpdateDate());
						//echo ("paidTime  ==== {$order->getLastUpdateDate()}\n");
					}
					$orderstatus = ''; //订单状态
					//if ($order->isSetOrderStatus()) {
					//                    	if($order->getOrderStatus() == 'Unshipped'){
					//							$orderstatus				= 1;
					//						}
					//					}
					if ($order->isSetFulfillmentChannel()) {
						$orderExtenData['FulfillmentChannel'] = $order->getFulfillmentChannel();
						//echo ("FulfillmentChannel  ==== "); //订单配送方式
						//echo ($orderExtenData['FulfillmentChannel'] . "\n");
					}
					if ($order->isSetSalesChannel()) {
						$orderExtenData['SalesChannel'] = $order->getSalesChannel();
						//echo ("SalesChannel ==== ");
						//echo ($orderExtenData['SalesChannel'] . "\n");
					}
					if ($order->isSetOrderChannel()) {
						$orderExtenData['OrderChannel'] = $order->getOrderChannel();
						//echo ("OrderChannel ====");
						//echo ($orderExtenData['OrderChannel'] . "\n");
					}
					if ($order->isSetShipServiceLevel()) {
						$orderExtenData['ShipServiceLevel'] = $order->getShipServiceLevel();
						//echo ("ShipServiceLevel ==== ");
						//echo ($orderExtenData['ShipServiceLevel'] . "\n");
					}
					if ($order->isSetShippingAddress()) { //判断是否设置了地址
						$shippingAddress = $order->getShippingAddress();
						if ($shippingAddress->isSetName()) { //获取收件人姓名
							$orderUserInfoData['username'] = htmlentities($shippingAddress->getName(), ENT_QUOTES);
							//echo ("username ==== ");
							//echo ($orderUserInfoData['username'] . "\n");
						}
						if ($shippingAddress->isSetAddressLine1()) { //街道1
							$orderUserInfoData['street'] = htmlentities($shippingAddress->getAddressLine1(), ENT_QUOTES);
							//echo ("street ==== ");
							//echo ($orderUserInfoData['street'] . "\n");
						}
						if ($shippingAddress->isSetAddressLine2()) { //街道2
							$street2 = htmlentities($shippingAddress->getAddressLine2(), ENT_QUOTES);
						}
						if ($shippingAddress->isSetAddressLine3()) { //街道3
							$street3 = htmlentities($shippingAddress->getAddressLine3(), ENT_QUOTES);
						}
						if ($shippingAddress->isSetCity()) { //城市
							$orderUserInfoData['city'] = htmlentities($shippingAddress->getCity(), ENT_QUOTES);
							//echo ("city ==== ");
							//echo ($orderUserInfoData['city'] . "\n");
						}
						if ($shippingAddress->isSetCounty()) { //郡，县
							$orderUserInfoData['address2'] = htmlentities($shippingAddress->getCounty() . ' ' . $street2, ENT_QUOTES);
							$orderUserInfoData['address3'] = htmlentities($shippingAddress->getCounty() . ' ' . $street3, ENT_QUOTES);
							//echo ("address2 ==== ");
							//echo ($orderUserInfoData['address2'] . "\n");
							//echo ("address3 ==== ");
							//echo ($orderUserInfoData['address3'] . "\n");
						}
						if ($shippingAddress->isSetDistrict()) { //地方，区
							//echo ("District ==== " . $shippingAddress->getDistrict() . "\n");
						}
						if ($shippingAddress->isSetStateOrRegion()) { //州
							$orderUserInfoData['state'] = htmlentities($shippingAddress->getStateOrRegion(), ENT_QUOTES);
							//echo ("state ==== ");
							//echo ($orderUserInfoData['state'] . "\n");
						}
						if ($shippingAddress->isSetPostalCode()) { //邮编
							$orderUserInfoData['zipCode'] = htmlentities($shippingAddress->getPostalCode(), ENT_QUOTES);
							//echo ("zipCode ==== ");
							//echo ($orderUserInfoData['zipCode'] . "\n");
						}
						if ($shippingAddress->isSetCountryCode()) { //国家简称

							/*$ebay_countrynames = array('US'=>'United States', "UK"=>"United Kingdom");
							$orderData['ebay_couny'] 	= 	$shippingAddress->getCountryCode() ;
							$orderData['ebay_site'] 	= 	$shippingAddress->getCountryCode() ;
							$orderData['ebay_countryname'] 	= 	$ebay_countrynames[$shippingAddress->getCountryCode()];*/
							//$sql = "SELECT regions_en FROM  ebay_region WHERE  regions_jc =  '".$shippingAddress->getCountryCode()."'";
							//                        	$sql	= $dbcon->execute($sql);
							//							$amazon_countryname	= $dbcon->fetch_one($sql);
							$countrycode = $shippingAddress->getCountryCode()=='GB' ? 'UK' : $shippingAddress->getCountryCode();
							$orderUserInfoData['countrySn'] = $countrycode;
							//这里要调用运输方式管理系统的数据，获取国家简称对应的国家名称
							$countryNameInfo = CommonModel::getCountrieInfoBySn($countrycode);//根据国家简称获取该国家的信息
							//$orderData['ebay_site'] = $shippingAddress->getCountryCode() ;
							$orderUserInfoData['countryName'] = trim($countryNameInfo['regions_en']);//获得国家名称
							//echo ("getCountryCode==== " . $orderUserInfoData['countrySn'] . "\n");
						}
						if ($shippingAddress->isSetPhone()) { //手机
							$orderUserInfoData['phone'] = $shippingAddress->getPhone();
							//echo ("phone ==== ");
							//echo ($orderUserInfoData['phone'] . "\n");
						}
					}
					if ($order->isSetOrderTotal()) {
						$orderTotal = $order->getOrderTotal();
						if ($orderTotal->isSetCurrencyCode()) { //币种
							$orderUserInfoData['currency'] = $orderTotal->getCurrencyCode();
							//echo ("currency ==== ");
							//echo ($orderUserInfoData['currency'] . "\n");
						}
						if ($orderTotal->isSetAmount()) { //订单总价,线上总价
							$orderData['onlineTotal'] = $orderTotal->getAmount();
							$orderData['actualTotal'] = $orderTotal->getAmount();
							//echo ("onlineTotal ==== ");
							//echo ($orderData['onlineTotal'] . "\n");
						}
					}
					if ($order->isSetNumberOfItemsShipped()) {
						//echo ("ItemsShipped ==== " . $order->getNumberOfItemsShipped() . "\n");
					}
					if ($order->isSetNumberOfItemsUnshipped()) {
						//echo ("NumberOfItemsUnshipped ==== " . $order->getNumberOfItemsUnshipped() . "\n");
					}
					if ($order->isSetPaymentExecutionDetail()) {
						$paymentExecutionDetail = $order->getPaymentExecutionDetail();
						$paymentExecutionDetailItemList = $paymentExecutionDetail->getPaymentExecutionDetailItem();
						foreach ($paymentExecutionDetailItemList as $paymentExecutionDetailItem) {
							echo ("######PaymentExecutionDetailItem######\n");
							if ($paymentExecutionDetailItem->isSetPayment()) {
								$payment = $paymentExecutionDetailItem->getPayment();
								if ($payment->isSetCurrencyCode()) {
									//echo ("CurrencyCode ==== ");
									//echo ($payment->getCurrencyCode() . "\n");
								}
								if ($payment->isSetAmount()) {
									//echo (" Amount ==== ");
									//echo ($payment->getAmount() . "\n");
								}
							}
							if ($paymentExecutionDetailItem->isSetSubPaymentMethod()) {
								//echo ("SubPaymentMethod ==== ");
								//echo ($paymentExecutionDetailItem->getSubPaymentMethod() . "\n");
							}
						}
					}
					if ($order->isSetPaymentMethod()) {
						$orderExtenData['PaymentMethod'] = $order->getPaymentMethod();
						//echo ("PaymentMethod ==== ");
						//echo ($orderExtenData['PaymentMethod'] . "\n");
					}
					if ($order->isSetMarketplaceId()) {
						$orderExtenData['MarketplaceId'] = $order->getMarketplaceId();
						//echo ("MarketplaceId ==== ");
						//echo ($orderExtenData['MarketplaceId'] . "\n");
					}
					if ($order->isSetBuyerName()) { //买家ID
						$orderUserInfoData['platformUsername'] = htmlentities($order->getBuyerName(), ENT_QUOTES);
						//echo ("platformUsername ==== ");
						//echo ($orderUserInfoData['platformUsername'] . "\n");
					}
					if ($order->isSetBuyerEmail()) { //买家email
						$orderUserInfoData['email'] = $order->getBuyerEmail();
						//echo ("email ==== ");
						//echo ($orderUserInfoData['email'] . "\n");

					}
					if ($order->isSetShipmentServiceLevelCategory()) {
						$orderExtenData['ShipmentServiceLevelCategory'] = $order->getShipmentServiceLevelCategory();
						//echo ("ShipmentServiceLevelCategory ==== ");
						//echo ($orderExtenData['ShipmentServiceLevelCategory'] . "\n");
					}

					//$orderData['ebay_user']			=	$user;
					$orderData['platformId'] = 11; //amazon的平台ID为11
					$orderData['accountId'] = $accountId; //amazon的账号ID
					//echo ("accountId ==== ");
					//echo ($orderData['accountId'] . "\n");
					$orderData['orderAddTime'] = time(); //添加到系统的时间
					//echo ("orderAddTime ==== ");
					//echo ($orderData['orderAddTime'] . "\n");
					$orderData['orderStatus'] = C('STATEPENDING'); //默认订单状态
					$orderData['orderType'] = C('STATEPENDING_CONV'); //默认订单类型
					
					$insertOrder = array('orderData' => $orderData,
										'orderExtenData' => $orderExtenData,			  
										'orderUserInfoData' => $orderUserInfoData
										);
					$tName = 'om_unshipped_order';
					$where = "WHERE recordNumber='{$orderData['recordNumber']}' AND platformId={$orderData['platformId']}";
					$flagCountUnshipped = OmAvailableModel :: getTNameCount($tName, $where);
					$tName = 'om_shipped_order';
					$flagCountshipped = OmAvailableModel :: getTNameCount($tName, $where);
					if (empty ($flagCountUnshipped) && empty ($flagCountshipped)) { //判断订单是否已经在系统2个订单表（未发货和已发货）中存在
						//$orderData['ebay_ordersn']	=	generateOrdersn();
						$detailrequest = new MarketplaceWebServiceOrders_Model_ListOrderItemsRequest();
						$detailrequest->setSellerId(MERCHANT_ID);
						$detailrequest->setAmazonOrderId($orderData['recordNumber']);
						$orderDetailArr = invokeListOrderItems($service, $detailrequest);
						
						for($i = 0; $i<count($orderDetailArr); $i++){
							$orderDetailArr[$i]['orderDetailData']['recordNumber'] = $orderData['recordNumber'];
						}
						//print_r($orderDetailArr);
						if (!empty ($orderDetailArr)) {
							$insertOrder['orderDetail'] = $orderDetailArr;
							//var_dump($obj_order_detail_data); echo "<br>";
							$calcInfo = CommonModel :: calcAddOrderWeight($orderDetailArr);//计算重量和包材
							//var_dump($calcInfo); exit;
							$insertOrder['orderData']['calcWeight'] = $calcInfo[0];
							$insertOrder['orderData']['pmId'] = $calcInfo[1];
							if(count($insertOrder['orderDetail']) > 1){
								$insertOrder['orderData']['orderAttribute'] = 3;
							}else if(isset($insertOrder['orderDetail'][0]['orderDetailData']['amount']) && $insertOrder['orderDetail'][0]['orderDetailData']['amount'] > 1){
								$insertOrder['orderData']['orderAttribute'] = 2;
							}
							$insertOrder['orderData']['transportId'] = $flip_transportList[get_carrier($insertOrder['orderData']['calcWeight'], $insertOrder['orderUserInfoData']['countryName'])];
							$calcShippingInfo = CommonModel :: calcAddOrderShippingFee($insertOrder,1);//计算运费
							//var_dump($calcShippingInfo); exit;
							//$insert_orderData['orderData']['calcShipping'] = $calcShippingInfo['fee']['fee'];
							$insertOrder['orderData']['channelId'] = $calcShippingInfo['fee']['channelId'];
							
							$insertOrder = AutoModel :: auto_contrast_intercept($insertOrder);
							//print_r($interceptInfo); exit;
							/*$insertOrder['orderData']['orderStatus'] = $interceptInfo['orderStatus'];
							$insertOrder['orderData']['orderType'] = $interceptInfo['orderType'];*/
							//var_dump($insertOrder); exit;
			
							if(OrderAddModel :: insertAllOrderRow($insertOrder)){
								//echo 'insert success!' . "\n";
								echo "-----".date("Y-m-d H:i:s").", 新增订单{$orderData['recordNumber']}成功\r\n";
							}else{
								echo "-----".date("Y-m-d H:i:s").", 新增订单{$orderData['recordNumber']}失败\r\n";
								//echo OrderAddModel :: $errMsg;
							}
						} else {
							//echo "Amazon ID: ".$orderData['recordnumber']." 订单详情添加失败\n";
							echo "Amazon ID: " . $orderData['recordNumber'] . " has no detail \n";
						}
					} else {
						echo "Amazon ID: " . $orderData['recordNumber'] . " had exist\n";
					}
				}
			}
		}
	} catch (MarketplaceWebServiceOrders_Exception $ex) {
		echo ("Caught Exception: " . $ex->getMessage() . "\n");
		echo ("Response Status Code: " . $ex->getStatusCode() . "\n");
		echo ("Error Code: " . $ex->getErrorCode() . "\n");
		echo ("Error Type: " . $ex->getErrorType() . "\n");
		echo ("Request ID: " . $ex->getRequestId() . "\n");
		echo ("XML: " . $ex->getXML() . "\n");
	} catch (Exception $e) {
		BaseModel :: rollback();
		BaseModel :: autoCommit();
		echo $e->getMessage() . "\n";
	}
}

function invokeListOrderItems(MarketplaceWebServiceOrders_Interface $service, $request) {

	//global $dbcon, $user;

	//$result = true;
	
	try {
		$response = $service->listOrderItems($request);
		$orderDetailData = array (); //用来存放orderDetail信息
		$orderDetailExtenData = array (); //用来存放orderDetailExtend信息
		$orderReturnData = array (); //用来储存上面2个数组用来返回，格式为array('orderDetailData'=>$orderDetailData,'orderDetailExtenData'=>$orderDetailExtenData)
		if ($response->isSetListOrderItemsResult()) {
			echo ("*****************ListOrderItemsResult*****************\n");

			$listOrderItemsResult = $response->getListOrderItemsResult();
			if ($listOrderItemsResult->isSetNextToken()) {
				//echo ("NextToken ==== " . $listOrderItemsResult->getNextToken() . "\n");
			}
			if ($listOrderItemsResult->isSetAmazonOrderId()) {
				//echo ("AmazonOrderId ==== " . $listOrderItemsResult->getAmazonOrderId() . "\n");
			}
			if ($listOrderItemsResult->isSetOrderItems()) {

				$orderItems = $listOrderItemsResult->getOrderItems();
				$orderItemList = $orderItems->getOrderItem();
				$i = 0; //定义一个计数器用来累加
				$orderReturnData = array();
				foreach ($orderItemList as $orderItem) {
					echo ("#########OrderItem########$i#######\n");
					if ($orderItem->isSetASIN()) { //itemId
						$orderDetailExtenData['itemId'] = $orderItem->getASIN();
						//echo ("itemId ==== ");
						//echo ($orderDetailExtenData['itemId'] . "\n");
					}
					if ($orderItem->isSetSellerSKU()) { //sku
						$orderDetailData['sku'] = str_pad(preg_replace('/^(0|\*)*/', '', $orderItem->getSellerSKU()), 3, '0', STR_PAD_LEFT);
						//echo ("sku ==== ");
						//echo ($orderDetailData['sku'] . "\n");
					}
					if ($orderItem->isSetOrderItemId()) { //OrderItemId
						$orderDetailData['recordNumber'] = $orderItem->getOrderItemId();
						//echo ("recordNumber ==== ");
						//echo ($orderDetailData['recordNumber'] . "\n");
					}
					if ($orderItem->isSetTitle()) { //itemTitle
						$orderDetailExtenData['itemTitle'] = htmlentities($orderItem->getTitle(), ENT_QUOTES);
						//echo ("itemTitle ==== ");
						//echo ($orderDetailExtenData['itemTitle'] . "\n");
					}
					if ($orderItem->isSetQuantityOrdered()) { //amount
						$orderDetailData['amount'] = $orderItem->getQuantityOrdered();
						//echo ("amount ==== ");
						//echo ($orderDetailData['amount'] . "\n");
					}
					if ($orderItem->isSetQuantityShipped()) { //已发货数量
						//echo ("QuantityShipped ==== " . $orderItem->getQuantityShipped() . "\n");
					}
					if ($orderItem->isSetItemPrice()) {
						$itemPrice = $orderItem->getItemPrice();
						if ($itemPrice->isSetCurrencyCode()) {
							//echo ("ItemPrice.CurrencyCode ==== " . $itemPrice->getCurrencyCode() . "\n");
						}
						if ($itemPrice->isSetAmount()) {
							$orderDetailData['itemPrice'] = round($itemPrice->getAmount() / $orderDetailData['amount'], 2);
							//echo ("itemPrice ==== ");
							//echo ($orderDetailData['itemPrice'] . "\n");
						}
					}
					if ($orderItem->isSetShippingPrice()) {
						$shippingPrice = $orderItem->getShippingPrice();
						if ($shippingPrice->isSetCurrencyCode()) {
							//echo ("ShippingPrice.CurrencyCode ==== " . $shippingPrice->getCurrencyCode() . "\n");
						}
						if ($shippingPrice->isSetAmount()) {
							$orderDetailExtenData['FinalValueFee'] = $shippingPrice->getAmount();
							//echo ("shippingPrice ==== ");
							//echo ($orderDetailExtenData['shippingPrice'] . "\n");
						}
					}
					if ($orderItem->isSetGiftWrapPrice()) {
						$giftWrapPrice = $orderItem->getGiftWrapPrice();
						if ($giftWrapPrice->isSetCurrencyCode()) {
							//echo ("GiftWrapPrice.CurrencyCode ==== " . $giftWrapPrice->getCurrencyCode() . "\n");
						}
						if ($giftWrapPrice->isSetAmount()) {
							//echo ("GiftWrapPrice.Amount ==== " . $giftWrapPrice->getAmount() . "\n");
						}
					}
					if ($orderItem->isSetItemTax()) {
						$itemTax = $orderItem->getItemTax();
						if ($itemTax->isSetCurrencyCode()) {
							//echo ("ItemTax.CurrencyCode ==== " . $itemTax->getCurrencyCode() . "\n");
						}
						if ($itemTax->isSetAmount()) {
							//echo ("ItemTax.Amount ==== " . $itemTax->getAmount() . "\n");
						}
					}
					if ($orderItem->isSetShippingTax()) {
						$shippingTax = $orderItem->getShippingTax();
						if ($shippingTax->isSetCurrencyCode()) {
							//echo ("ShippingTax.CurrencyCode ==== " . $shippingTax->getCurrencyCode() . "\n");
						}
						if ($shippingTax->isSetAmount()) {
							//echo ("ShippingTax.Amount ==== " . $shippingTax->getAmount() . "\n");
						}
					}
					if ($orderItem->isSetGiftWrapTax()) {
						$giftWrapTax = $orderItem->getGiftWrapTax();
						if ($giftWrapTax->isSetCurrencyCode()) {
							//echo ("GiftWrapTax.CurrencyCode ==== " . $giftWrapTax->getCurrencyCode() . "\n");
						}
						if ($giftWrapTax->isSetAmount()) {
							//echo ("GiftWrapTax.Amount ==== " . $giftWrapTax->getAmount() . "\n");
						}
					}
					if ($orderItem->isSetShippingDiscount()) {
						$shippingDiscount = $orderItem->getShippingDiscount();
						if ($shippingDiscount->isSetCurrencyCode()) {
							//echo ("ShippingDiscount.CurrencyCode ==== " . $shippingDiscount->getCurrencyCode() . "\n");
						}
						if ($shippingDiscount->isSetAmount()) {
							//echo ("ShippingDiscount.Amount ==== " . $shippingDiscount->getAmount() . "\n");
						}
					}
					if ($orderItem->isSetPromotionDiscount()) {
						$promotionDiscount = $orderItem->getPromotionDiscount();
						if ($promotionDiscount->isSetCurrencyCode()) {
							//echo ("PromotionDiscount.CurrencyCode ==== " . $promotionDiscount->getCurrencyCode() . "\n");
						}
						if ($promotionDiscount->isSetAmount()) {
							//echo ("PromotionDiscount.Amount ==== " . $promotionDiscount->getAmount() . "\n");
						}
					}
					if ($orderItem->isSetPromotionIds()) {
						$promotionIds = $orderItem->getPromotionIds();
						$promotionIdList = $promotionIds->getPromotionId();
						foreach ($promotionIdList as $promotionId) {
							//echo ("PromotionId ==== " . $promotionId);
						}
					}
					if ($orderItem->isSetCODFee()) {
						$CODFee = $orderItem->getCODFee();
						if ($CODFee->isSetCurrencyCode()) {
							//echo ("CODFee.CurrencyCode ==== " . $CODFee->getCurrencyCode() . "\n");
						}
						if ($CODFee->isSetAmount()) {
							//echo ("CODFee.Amount ==== " . $CODFee->getAmount() . "\n");
						}
					}
					if ($orderItem->isSetCODFeeDiscount()) {
						$CODFeeDiscount = $orderItem->getCODFeeDiscount();
						if ($CODFeeDiscount->isSetCurrencyCode()) {
							//echo ("CODFeeDiscount.CurrencyCode ==== " . $CODFeeDiscount->getCurrencyCode() . "\n");
						}
						if ($CODFeeDiscount->isSetAmount()) {
							//echo ("CODFeeDiscount.Amount" . $CODFeeDiscount->getAmount() . "\n");
						}
					}
					if ($orderItem->isSetGiftMessageText()) {
						//echo ("GiftMessageText ==== " . $orderItem->getGiftMessageText() . "\n");
					}
					if ($orderItem->isSetGiftWrapLevel()) {
						//echo ("GiftWrapLevel ==== " . $orderItem->getGiftWrapLevel() . "\n");
					}
					$i++; //i+1
					$orderReturnData[] = array('orderDetailData' => $orderDetailData,			
											'orderDetailExtenData' => $orderDetailExtenData
											);
				}
				//$orderReturnData['orderDetailData'] = $orderDetailData;
				//$orderReturnData['orderDetailExtenData'] = $orderDetailExtenData;
			}
		}
		if ($response->isSetResponseMetadata()) {
			$responseMetadata = $response->getResponseMetadata();
			if ($responseMetadata->isSetRequestId()) {
				echo ("RequestId " . $responseMetadata->getRequestId() . "\n");
			}
		}
		return $orderReturnData;
	} catch (MarketplaceWebServiceOrders_Exception $ex) {
		echo ("Caught Exception: " . $ex->getMessage() . "\n");
		echo ("Response Status Code: " . $ex->getStatusCode() . "\n");
		echo ("Error Code: " . $ex->getErrorCode() . "\n");
		echo ("Error Type: " . $ex->getErrorType() . "\n");
		echo ("Request ID: " . $ex->getRequestId() . "\n");
		echo ("XML: " . $ex->getXML() . "\n");
		return array ();
	}

}
?>