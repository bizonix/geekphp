<?php
	/*
	 *更新eBay交易结果
	 *czq 20140626
	 */
	include_once WEB_PATH."lib/api/ebay/eBaySession.php";
	class CompleteSale extends eBaySession{
		
		public	$verb ='CompleteSale';
		
		public function __construct(){
			parent::__construct();
		}

		/**
		 * 给顾客留评价
		 * @param number $ebay_userid
		 * @param number $ebay_itemid
		 * @param string $ebay_tid
		 * @param string $ebay_orderid
		 * @return mixed
		 * @author czq
		 */
		public function give_feedback($ebay_userid,$ebay_itemid,$ebay_tid='',$ebay_orderid=''){
			$requestXmlBody ='<?xml version="1.0" encoding="utf-8"?>
							<'.$this->verb.'Request xmlns="urn:ebay:apis:eBLBaseComponents">
							<WarningLevel>High</WarningLevel>
							<FeedbackInfo>
								<CommentType>Positive</CommentType>
								<CommentText>Wonderful buyer, very fast payment.</CommentText>
								<TargetUser>'.$ebay_userid.'</TargetUser>
							</FeedbackInfo>
							<ItemID>'.$ebay_itemid.'</ItemID>';
			if($ebay_tid !=''){			
				$requestXmlBody .= '<TransactionID>'.$ebay_tid.'</TransactionID>';			
			}
			if($ebay_orderid != ''){			
				$requestXmlBody .= '<OrderID>'.$ebay_orderid.'</OrderID>';			
			}
			$requestXmlBody .= '<RequesterCredentials>
							 	<eBayAuthToken>'.$this->requestToken.'</eBayAuthToken>
							</RequesterCredentials>
							</'.$this->verb.'Request>';
			return $this->sendHttpRequest($requestXmlBody);
		}
		
		/**
		 * 更新发货信息(trackno)到ebay
		 * @param array $trans
		 * @return mixed
		 * @author czq
		 */
		public function update_order_shippingdetail_to_ebay($trans){
			$request_body ='<?xml version="1.0" encoding="utf-8"?> 
							<'.$this->verb.'Request xmlns="urn:ebay:apis:eBLBaseComponents"> 
								<RequesterCredentials> 
									<eBayAuthToken>'.$this->requestToken.'</eBayAuthToken> 
								</RequesterCredentials> 
								<ItemID>'.$trans['itemid'].'</ItemID> 
								<TransactionID>'.$trans['tid'].'</TransactionID>';
			if(!empty($trans['orderid'])){
				$request_body	.='<OrderID>'.$trans['orderid'].'</OrderID>';
			}
			$request_body	.= '<Shipment>
									<ShipmentTrackingDetails>
										<ShipmentTrackingNumber>'.$trans['ebay_tracknumber'].'</ShipmentTrackingNumber>
										<ShippingCarrierUsed>'.$trans['ebay_carrier'].'</ShippingCarrierUsed>
									</ShipmentTrackingDetails>
								</Shipment>';			
			$request_body .= '</'.$this->verb.'Request>';
			return $this->sendHttpRequest($request_body);
		}
		
		/**
		 * 只标发货 不同步track info
		 * @param array $trans
		 * @return boolean
		 * @author czq
		 */
		public function markOrderShipped($trans){
			$requestXmlBody ='<?xml version="1.0" encoding="utf-8"?> 
							<CompleteSaleRequest xmlns="urn:ebay:apis:eBLBaseComponents"> 
								<RequesterCredentials> 
									<eBayAuthToken>'.$this->requestToken.'</eBayAuthToken> 
								</RequesterCredentials> 
								<ItemID>'.$trans['itemid'].'</ItemID> 
								<TransactionID>'.$trans['tid'].'</TransactionID>';
			if(!empty($trans['orderid'])){
				$requestXmlBody	.='<OrderID>'.$trans['orderid'].'</OrderID>';
			}
			$requestXmlBody .='<Paid>true</Paid>
							 <Shipped>true</Shipped>
							</CompleteSaleRequest>';
			return $this->sendHttpRequest($requestXmlBody);
		}
	}
?>