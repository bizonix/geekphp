<?php
/*
 * 获取订单抓取ID列表
 * @add lzx, date 20140612
 */
include_once "../eBaySession.php";
class GetCertainOrder extends eBaySession{
	
	private $verb = 'GetOrders';
	
	public function __construct(){
		parent::__construct();
	}
	
	public function request($order_ids){
		
		$order_ids = array_filter(create_function('$a', 'return preg_match("/^\d{12}(|\-\d{12,14}|\-0)$/i", $orderid)>0;'), $order_ids);
		if (count($order_ids)==0){
			return false;
		}
		$valid_orderids = array_map(create_function('$a','return "<OrderID>".$a."</OrderID>";'), $order_ids);
		$requestXmlBody = '<?xml version="1.0" encoding="utf-8"?>
			<'.$this->verb.'Request xmlns="urn:ebay:apis:eBLBaseComponents">
			<RequesterCredentials>
				<eBayAuthToken>'.$this->token.'</eBayAuthToken>
			</RequesterCredentials>  
			<DetailLevel>ReturnAll</DetailLevel>
			<IncludeFinalValueFee>true</IncludeFinalValueFee>
			<OrderRole>Seller</OrderRole><OrderStatus>Completed</OrderStatus>
			<OrderIDArray>'."\n".implode("\n\t",$valid_orderids)."\n".'</OrderIDArray>
			</'.$this->verb.'Request>';
		return $this->sendHttpRequest($requestXmlBody);
	}
	
	public function getPayPalEmailAddress($itemid){			
		$handle=new eBaySession($this->token, $this->devID, $this->appID, $this->certID, $this->serverUrl, $this->compatabilityLevel, $this->siteID, 'GetItem');
		$requestXmlBody = ' <?xml version="1.0" encoding="utf-8"?>
								<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
								<RequesterCredentials>
									<eBayAuthToken>'.$this->token.'</eBayAuthToken>
								</RequesterCredentials>
								<OutputSelector>Item.PayPalEmailAddress</OutputSelector>
								<ItemID>'.$itemid.'</ItemID>
								<WarningLevel>High</WarningLevel>
							</GetItemRequest>';
		$responseXml = $handle->sendHttpRequest($requestXmlBody);
		$responseDoc = new DomDocument();	
		$responseDoc->loadXML($responseXml);
		$paypaladress = $responseDoc->getElementsByTagName('PayPalEmailAddress')->item(0)->nodeValue;
		return $paypaladress;
	}
	
	public function getSellerTransactions($orderid){		
		$handle=new eBaySession($this->token, $this->devID, $this->appID, $this->certID, $this->serverUrl, $this->compatabilityLevel, $this->siteID, 'GetOrderTransactions');
		$requestXmlBody = ' <?xml version="1.0" encoding="utf-8"?>
								<GetOrderTransactionsRequest xmlns="urn:ebay:apis:eBLBaseComponents">
								<RequesterCredentials>
									<eBayAuthToken>'.$this->token.'</eBayAuthToken>
								</RequesterCredentials>
								<DetailLevel>ReturnAll</DetailLevel>
								<OutputSelector>OrderArray.Order.TransactionArray.Transaction.Buyer.BuyerInfo.ShippingAddress</OutputSelector>
								<IncludeFinalValueFee>true</IncludeFinalValueFee>
								<OrderRole>Seller</OrderRole>
								<OrderStatus>Completed</OrderStatus>
								<OrderIDArray>
									<OrderID>'.$orderid.'</OrderID>
								</OrderIDArray>
							</GetOrderTransactionsRequest>';
		$responseXml = $handle->sendHttpRequest($requestXmlBody);
		$responseDoc = new DomDocument();
		$responseDoc->loadXML($responseXml);
		return $responseDoc->getElementsByTagName('ShippingAddress')->item(0);
	}
}
?>