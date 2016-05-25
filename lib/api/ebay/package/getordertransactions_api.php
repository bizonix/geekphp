<?php
/*
 * 获取订单抓取ID列表
 * @add lzx, date 20140612
 */
include_once "../eBaySession.php";
class GetOrders extends eBaySession{
	
	private $verb = 'GetItem';
	
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 从交易信息获取发货地址
	 * @param string $orderid
	 * @return array
	 * @author lzx
	 */
	public function getSellerTransactions($orderid){		
		$requestXmlBody = ' <?xml version="1.0" encoding="utf-8"?>
			<'.$this->verb.'Request xmlns="urn:ebay:apis:eBLBaseComponents">
			<RequesterCredentials>
				<eBayAuthToken>'.$this->token.'</eBayAuthToken>
			</RequesterCredentials>
			<DetailLevel>ReturnAll</DetailLevel>
			<OutputSelector>OrderArray.Order.TransactionArray.Transaction.Buyer.BuyerInfo.ShippingAddress</OutputSelector>
			<OutputSelector>OrderArray.Order.TransactionArray.Transaction.Buyer.Email</OutputSelector>
			<IncludeFinalValueFee>true</IncludeFinalValueFee>
			<OrderRole>Seller</OrderRole>
			<OrderStatus>Completed</OrderStatus>
			<OrderIDArray>
				<OrderID>'.$orderid.'</OrderID>
			</OrderIDArray>
			</'.$this->verb.'Request>';
		return XML_unserialize($this->sendHttpRequest($requestXmlBody));
	}
}
?>