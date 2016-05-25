<?php
/** 
 *  PHP Version 5
 *
 *  @category    Amazon
 *  @package     MarketplaceWebService
 *  @copyright   Copyright 2009 Amazon Technologies, Inc.
 *  @link        http://aws.amazon.com
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     2009-01-01
 */
/******************************************************************************* 

 *  Marketplace Web Service PHP5 Library
 *  Generated: Thu May 07 13:07:36 PDT 2009
 * 
 */

/**
 * Submit Feed  Sample
 */

include_once ('.config.inc.php'); 
error_reporting(E_ALL);

include "../../include/config.php";

$ss				= "select * from ebay_account where ebay_account ='cxxlp@126.com' and ebay_user ='$user' ";
$ss				= $dbConn->execute($ss);
$ss				= $dbConn->getResultArray($ss);


$AWS_ACCESS_KEY_ID		= $ss[0]['AWS_ACCESS_KEY_ID'];
$AWS_SECRET_ACCESS_KEY	= $ss[0]['AWS_SECRET_ACCESS_KEY'];
$MERCHANT_ID			= $ss[0]['MERCHANT_ID'];
$MARKETPLACE_ID			= $ss[0]['MARKETPLACE_ID'];
$serviceUrl				= $ss[0]['serviceUrl'].'/Orders/2011-01-01';
  	 define('AWS_ACCESS_KEY_ID', $AWS_ACCESS_KEY_ID);
    define('AWS_SECRET_ACCESS_KEY', $AWS_SECRET_ACCESS_KEY);  

 define ('MERCHANT_ID', $MERCHANT_ID);
    define ('MARKETPLACE_ID', $MARKETPLACE_ID);
 set_include_path(get_include_path() . PATH_SEPARATOR . '../../.');    
 
 

/************************************************************************
* Uncomment to configure the client instance. Configuration settings
* are:
*
* - MWS endpoint URL
* - Proxy host and port.
* - MaxErrorRetry.
***********************************************************************/
// IMPORTANT: Uncomment the approiate line for the country you wish to
// sell in:
// United States:
//$serviceUrl = "https://mws.amazonservices.com";
// United Kingdom
//$serviceUrl = "https://mws.amazonservices.co.uk";
// Germany
//$serviceUrl = "https://mws.amazonservices.de";
// France
//$serviceUrl = "https://mws.amazonservices.fr";
// Italy
//$serviceUrl = "https://mws.amazonservices.it";
// Japan
//$serviceUrl = "https://mws.amazonservices.jp";
// China
//$serviceUrl = "https://mws.amazonservices.com.cn";
// Canada
//$serviceUrl = "https://mws.amazonservices.ca";
// India
//$serviceUrl = "https://mws.amazonservices.in";

$config = array (
  'ServiceURL' => $serviceUrl,
  'ProxyHost' => null,
  'ProxyPort' => -1,
  'MaxErrorRetry' => 3,
);

/************************************************************************
 * Instantiate Implementation of MarketplaceWebService
 * 
 * AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY constants 
 * are defined in the .config.inc.php located in the same 
 * directory as this sample
 ***********************************************************************/
 $service = new MarketplaceWebService_Client(
     AWS_ACCESS_KEY_ID, 
     AWS_SECRET_ACCESS_KEY, 
     $config,
     APPLICATION_NAME,
     APPLICATION_VERSION);
 
/************************************************************************
 * Uncomment to try out Mock Service that simulates MarketplaceWebService
 * responses without calling MarketplaceWebService service.
 *
 * Responses are loaded from local XML files. You can tweak XML files to
 * experiment with various outputs during development
 *
 * XML files available under MarketplaceWebService/Mock tree
 *
 ***********************************************************************/
 // $service = new MarketplaceWebService_Mock();

/************************************************************************
 * Setup request parameters and uncomment invoke to try out 
 * sample for Submit Feed Action
 ***********************************************************************/
 // @TODO: set request. Action can be passed as MarketplaceWebService_Model_SubmitFeedRequest
 // object or array of parameters

// Note that PHP memory streams have a default limit of 2M before switching to disk. While you
// can set the limit higher to accomidate your feed in memory, it's recommended that you store
// your feed on disk and use traditional file streams to submit your feeds. For conciseness, this
// examples uses a memory stream.

$feed = '
<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <Header>
        <DocumentVersion>1.01</DocumentVersion>
        <MerchantIdentifier>M_MWSTEST_49045593</MerchantIdentifier>
    </Header>
    <MessageType>OrderFulfillment</MessageType>
    <Message>
        <MessageID>1</MessageID>
        <OperationType>Update</OperationType>
        <OrderFulfillment>
            <AmazonOrderID>002-3275191-2204215</AmazonOrderID>
            <FulfillmentDate>2009-07-22T23:59:59-07:00</FulfillmentDate>
            <FulfillmentData>
                <CarrierName>Contact Us for Details</CarrierName>
                <ShippingMethod>Standard</ShippingMethod>
            </FulfillmentData>
            <Item>
                <AmazonOrderItemCode>42197908407194</AmazonOrderItemCode>
                <Quantity>1</Quantity>
            </Item>
        </OrderFulfillment>
    </Message>
</AmazonEnvelope>';

echo $feed;


// Constructing the MarketplaceId array which will be passed in as the the MarketplaceIdList 
// parameter to the SubmitFeedRequest object.
//$marketplaceIdArray = array("Id" => array('<Marketplace_Id_1>','<Marketplace_Id_2>'));
     
 // MWS request objects can be constructed two ways: either passing an array containing the 
 // required request parameters into the request constructor, or by individually setting the request
 // parameters via setter methods.
 // Uncomment one of the methods below.
 
/********* Begin Comment Block *********/

$feedHandle = @fopen('php://temp', 'rw+');
fwrite($feedHandle, $feed);
rewind($feedHandle);
$parameters = array (
  'Merchant' => $MERCHANT_ID,
  'MarketplaceIdList' => $marketplaceIdArray,
  'FeedType' => '_POST_ORDER_FULFILLMENT_DATA_',
  'FeedContent' => $feedHandle,
  'PurgeAndReplace' => false,
  'ContentMd5' => base64_encode(md5(stream_get_contents($feed), true)),
);


$request = new MarketplaceWebService_Model_SubmitFeedRequest($parameters);
/********* End Comment Block *********/

/********* Begin Comment Block *********/
//$feedHandle = @fopen('php://memory', 'rw+');
//fwrite($feedHandle, $feed);
//rewind($feedHandle);

//$request = new MarketplaceWebService_Model_SubmitFeedRequest();
//$request->setMerchant(MERCHANT_ID);
//$request->setMarketplaceIdList($marketplaceIdArray);
//$request->setFeedType('_POST_PRODUCT_DATA_');
//$request->setContentMd5(base64_encode(md5(stream_get_contents($feedHandle), true)));
//rewind($feedHandle);
//$request->setPurgeAndReplace(false);
//$request->setFeedContent($feedHandle);

//rewind($feedHandle);
/********* End Comment Block *********/

invokeSubmitFeed($service, $request);

//@fclose($feedHandle);
                                        
/**
  * Submit Feed Action Sample
  * Uploads a file for processing together with the necessary
  * metadata to pr