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
/******************************************************************************* 
 *  Marketplace Web Service Orders PHP5 Library
 *  Generated: Fri Jan 21 18:53:17 UTC 2011
 * 
 */

/**
 * Get Order  Sample
 */

include_once ('.config.inc.php'); 

/************************************************************************
 * Instantiate Implementation of MarketplaceWebServiceOrders
 * 
 * AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY constants 
 * are defined in the .config.inc.php located in the same 
 * directory as this sample
 ***********************************************************************/
// United States:
//$serviceUrl = "https://mws.amazonservices.com/Orders/2011-01-01";
// United Kingdom
//$serviceUrl = "https://mws.amazonservices.co.uk/Orders/2011-01-01";
// Germany
//$serviceUrl = "https://mws.amazonservices.de/Orders/2011-01-01";
// France
//$serviceUrl = "https://mws.amazonservices.fr/Orders/2011-01-01";
// Italy
//$serviceUrl = "https://mws.amazonservices.it/Orders/2011-01-01";
// Japan
//$serviceUrl = "https://mws.amazonservices.jp/Orders/2011-01-01";
// China
//$serviceUrl = "https://mws.amazonservices.com.cn/Orders/2011-01-01";
// Canada
//$serviceUrl = "https://mws.amazonservices.ca/Orders/2011-01-01";
 $config = array (
   'ServiceURL' => $serviceUrl,
   'ProxyHost' => null,
   'ProxyPort' => -1,
   'MaxErrorRetry' => 3,
 );

 $service = new MarketplaceWebServiceOrders_Client(
        AWS_ACCESS_KEY_ID,
        AWS_SECRET_ACCESS_KEY,
        APPLICATION_NAME,
        APPLICATION_VERSION,
        $config);

/************************************************************************
 * Uncomment to try out Mock Service that simulates MarketplaceWebServiceOrders
 * responses without calling MarketplaceWebServiceOrders service.
 *
 * Responses are loaded from local XML files. You can tweak XML files to
 * experiment with various outputs during development
 *
 * XML files available under MarketplaceWebServiceOrders/Mock tree
 *
 ***********************************************************************/
 // $service = new MarketplaceWebServiceOrders_Mock();

/************************************************************************
 * Setup request parameters and uncomment invoke to try out 
 * sample for Get Order Action
 ***********************************************************************/
 $request = new MarketplaceWebServiceOrders_Model_GetOrderRequest();
 $request->setSellerId(MERCHANT_ID);
 // @TODO: set request. Action can be passed as MarketplaceWebServiceOrders_Model_GetOrderRequest
 // object or array of parameters

 // Set the list of AmazonOrderIds
 $orderIds = new MarketplaceWebServiceOrders_Model_OrderIdList();
 $orderIds->setId(array('<AMAZON ORDER ID>'));
 $request->setAmazonOrderId($orderIds);
 
 invokeGetOrder($service, $request);

                                
/**
  * Get Order Action Sample
  * This operation takes up to 50 order ids and returns the corresponding orders.
  *   
  * @param MarketplaceWebServiceOrders_Interface $service instance of MarketplaceWebServiceOrders_Interface
  * @param mixed $request MarketplaceWebServiceOrders_Model_GetOrder or array of parameters
  */
  function invokeGetOrder(MarketplaceWebServiceOrders_Interface $service, $request) 
  {
      try {
              $response = $service->getOrder($request);
              
                echo ("Service Response\n");
                echo ("=============================================================================\n");

                echo("        GetOrderResponse\n");
                if ($response->isSetGetOrderResult()) { 
                    echo("            GetOrderResult\n");
                    $getOrderResult = $response->getGetOrderResult();
                    if ($getOrderResult->isSetOrders()) { 
                        echo("                Orders\n");
                        $orders = $getOrderResult->getOrders();
                        $orderList = $orders->getOrder();
                        foreach ($orderList as $order) {
                            echo("                    Order\n");
                            if ($order->isSetAmazonOrderId()) 
                            {
                                echo("                        AmazonOrderId\n");
                                echo("                            " . $order->getAmazonOrderId() . "\n");
                            }
                            if ($order->isSetSellerOrderId()) 
                            {
                                echo("                        SellerOrderId\n");
                                echo("                            " . $order->getSellerOrderId() . "\n");
                            }
                            if ($order->isSetPurchaseDate()) 
                            {
                                echo("                        PurchaseDate\n");
                                echo("                            " . $order->getPurchaseDate() . "\n");
                            }
                            if ($order->isSetLastUpdateDate()) 
                            {
                                echo("                        LastUpdateDate\n");
                                echo("                            " . $order->getLastUpdateDate() . "\n");
                            }
                            if ($order->isSetOrderStatus()) 
                            {
                                echo("                        OrderStatus\n");
                                echo("                            " . $order->getOrderStatus() . "\n");
                            }
                            if ($order->isSetFulfillmentChannel()) 
                            {
                                echo("                        FulfillmentChannel\n");
                                echo("                            " . $order->getFulfillmentChannel() . "\n");
                            }
                            if ($order->isSetSalesChannel()) 
                            {
                                echo("                        SalesChannel\n");
                                echo("                            " . $order->getSalesChannel() . "\n");
                            }
                            if ($order->isSetOrderChannel()) 
                            {
                                echo("                        OrderChannel\n");
                                echo("                            " . $order->getOrderChannel() . "\n");
                            }
                            if ($order->isSetShipServiceLevel()) 
                            {
                                echo("                        ShipServiceLevel\n");
                                echo("                            " . $order->getShipServiceLevel() . "\n");
                            }
                            if ($order->isSetShippingAddress()) { 
                                echo("                        ShippingAddress\n");
                                $shippingAddress = $order->getShippingAddress();
                                if ($shippingAddress->isSetName()) 
                                {
                                    echo("                            Name\n");
                                    echo("                                " . $shippingAddress->getName() . "\n");
                                }
                                if ($shippingAddress->isSetAddressLine1()) 
                                {
                                    echo("                            AddressLine1\n");
                                    echo("                                " . $shippingAddress->getAddressLine1() . "\n");
                                }
                                if ($shippingAddress->isSetAddressLine2()) 
                                {
                                    echo("                            AddressLine2\n");
                                    echo("                                " . $shippingAddress->getAddressLine2() . "\n");
                                }
                                if ($shippingAddress->isSetAddressLine3()) 
                                {
                                    echo("                            AddressLine3\n");
                                    echo("                                " . $shippingAddress->getAddressLine3() . "\n");
                                }
                                if ($shippingAddress->isSetCity()) 
                                {
                                    echo("                            City\n");
                                    echo("                                " . $shippingAddress->getCity() . "\n");
                                }
                                if ($shippingAddress->isSetCounty()) 
                                {
                                    echo("                            County\n");
                                    echo("                                " . $shippingAddress->getCounty() . "\n");
                                }
                                if ($shippingAddress->isSetDistrict()) 
                                {
                                    echo("                            District\n");
                                    echo("                                " . $shippingAddress->getDistrict() . "\n");
                                }
                                if ($shippingAddress->isSetStateOrRegion()) 
                                {
                                    echo("                            StateOrRegion\n");
                                    echo("                                " . $shippingAddress->getStateOrRegion() . "\n");
                                }
                                if ($shippingAddress->isSetPostalCode()) 
                                {
                                    echo("                            PostalCode\n");
                                    echo("                                " . $shippingAddress->getPostalCode() . "\n");
                                }
                                if ($shippingAddress->isSetCountryCode()) 
                                {
                                    echo("                            CountryCode\n");
                                    echo("                                " . $shippingAddress->getCountryCode() . "\n");
                                }
                                if ($shippingAddress->isSetPhone()) 
                                {
                                    echo("                            Phone\n");
                                    echo("                                " . $shippingAddress->getPhone() . "\n");
                                }
                            } 
                            if ($order->isSetOrderTotal()) { 
                                echo("                        OrderTotal\n");
                                $orderTotal = $order->getOrderTotal();
                                if ($orderTotal->isSetCurrencyCode()) 
                                {
                                    echo("                            CurrencyCode\n");
                                    echo("                                " . $orderTotal->getCurrencyCode() . "\n");
                                }
                                if ($orderTotal->isSetAmount()) 
                                {
                                    echo("                            Amount\n");
                                    echo("                                " . $orderTotal->getAmount() . "\n");
                                }
                            } 
                            if ($order->isSetNumberOfItemsShipped()) 
                            {
                                echo("                        NumberOfItemsShipped\n");
                                echo("                            " . $order->getNumberOfItemsShipped() . "\n");
                            }
                            if ($order->isSetNumberOfItemsUnshipped()) 
                            {
                                echo("                        NumberOfItemsUnshipped\n");
                                echo("                            " . $order->getNumberOfItemsUnshipped() . "\n");
                            }
                            if ($order->isSetPaymentExecutionDetail()) { 
                                echo("                        PaymentExecutionDetail\n");
                                $paymentExecutionDetail = $order->getPaymentExecutionDetail();
                                $paymentExecutionDetailItemList = $paymentExecutionDetail->getPaymentExecutionDetailItem();
                                foreach ($paymentExecutionDetailItemList as $paymentExecutionDetailItem) {
                                    echo("                            PaymentExecutionDetailItem\n");
                                    if ($paymentExecutionDetailItem->isSetPayment()) { 
                                        echo("                                Payment\n");
                                        $payment = $paymentExecutionDetailItem->getPayment();
                                        if ($payment->isSetCurrencyCode()) 
                                        {
                                            echo("                                    CurrencyCode\n");
                                            echo("                                        " . $payment->getCurrencyCode() . "\n");
                                        }
                                        if ($payment->isSetAmount()) 
                                        {
                                            echo("                                    Amount\n");
                                            echo("                                        " . $payment->getAmount() . "\n");
                                        }
                                    } 
                                    if ($paymentExecutionDetailItem->isSetSubPaymentMethod()) 
                                    {
                                        echo("                                SubPaymentMethod\n");
                                        echo("                                    " . $paymentExecutionDetailItem->getSubPaymentMethod() . "\n");
                                    }
                                }
                            } 
                            if ($order->isSetPaymentMethod()) 
                            {
                                echo("                        PaymentMethod\n");
                                echo("                            " . $order->getPaymentMethod() . "\n");
                            }
                            if ($order->isSetMarketplaceId()) 
                            {
                                echo("                        MarketplaceId\n");
                                echo("                            " . $order->getMarketplaceId() . "\n");
                            }
                            if ($order->isSetBuyerEmail()) 
                            {
                                echo("                        BuyerEmail\n");
                                echo("                            " . $order->getBuyerEmail() . "\n");
                            }
                            if ($order->isSetBuyerName()) 
                            {
                                echo("                        BuyerName\n");
                                echo("                            " . $order->getBuyerName() . "\n");
                            }
                            if ($order->isSetShipmentServiceLevelCategory()) 
                            {
                                echo("                        ShipmentServiceLevelCategory\n");
                                echo("                            " . $order->getShipmentServiceLevelCategory() . "\n");
                            }
                        }
                    } 
                } 
                if ($response->isSetResponseMetadata()) { 
                    echo("            ResponseMetadata\n");
                    $responseMetadata = $response->getResponseMetadata();
                    if ($responseMetadata->isSetRequestId()) 
                    {
                        echo("                RequestId\n");
                        echo("                    " . $responseMetadata->getRequestId() . "\n");
                    }
                } 

     } catch (MarketplaceWebServiceOrders_Exception $ex) {
         echo("Caught Exception: " . $ex->getMessage() . "\n");
         echo("Response Status Code: " . $ex->getStatusCode() . "\n");
         echo("Error Code: " . $ex->getErrorCode() . "\n");
         echo("Error Type: " . $ex->getErrorType() . "\n");
         echo("Request ID: " . $ex->getRequestId() . "\n");
         echo("XML: " . $ex->getXML() . "\n");
     }
 }
                
