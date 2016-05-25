<?php
	/*
	 *更新eBay交易结果
	 */
	require_once "eBaySession.php";
	class CompleteSaleAPI{
		private	$verb='CompleteSale';
		
		private $token;
		private $devID;
		private $appID;
		private $certID;
		private $serverUrl;
		private $siteID;
		private $compatabilityLevel;
		
		private $handle;
		
		public function __construct($ebay_account){
			require WEB_PATH_CONF_SCRIPTS_KEYS_EBAY.'keys_'.$ebay_account.'.php';
			
			$this->token=$userToken;
			$this->devID=$devID;
			$this->appID=$appID;
			$this->certID=$certID;
			$this->serverUrl=$serverUrl;
			$this->siteID=$siteID;
			$this->compatabilityLevel=$compatabilityLevel;
			
			$this->handle=new eBaySession($this->token, $this->devID, $this->appID, $this->certID,
										  $this->serverUrl, $this->compatabilityLevel, $this->siteID, $this->verb);
		}
		//给顾客留评价
		public function give_feedback($ebay_userid,$ebay_itemid,$ebay_tid='',$ebay_orderid=''){
			$request_body='<?xml version="1.0" encoding="utf-8"?>
							<CompleteSaleRequest xmlns="urn:ebay:apis:eBLBaseComponents">
							<WarningLevel>High</WarningLevel>
							<FeedbackInfo>
								<CommentType>Positive</CommentType>
								<CommentText>Wonderful buyer, very fast payment.</CommentText>
								<TargetUser>'.$ebay_userid.'</TargetUser>
							</FeedbackInfo>
							<ItemID>'.$ebay_itemid.'</ItemID>';
			if($ebay_tid !=''){			
				$request_body .= '<TransactionID>'.$ebay_tid.'</TransactionID>';			
			}
			if($ebay_orderid != ''){			
				$request_body .= '<OrderID>'.$ebay_orderid.'</OrderID>';			
			}
			$request_body .= '<RequesterCredentials>
								<eBayAuthToken>'.$this->token.'</eBayAuthToken>
								</RequesterCredentials>
								</CompleteSaleRequest>';
			return $this->handle->sendHttpRequest($request_body);
		}
		//更新发货信息(trackno)到ebay
		public function update_order_shippingdetail_to_ebay($trans){
			$request_body='<?xml version="1.0" encoding="utf-8"?> 
							<CompleteSaleRequest xmlns="urn:ebay:apis:eBLBaseComponents"> 
								<RequesterCredentials> 
									<eBayAuthToken>'.$this->token.'</eBayAuthToken> 
								</RequesterCredentials> 
								<ItemID>'.$trans['itemid'].'</ItemID> 
								<TransactionID>'.$trans['tid'].'</TransactionID>';
			if(!empty($trans['orderid'])){
				$request_body	.='<OrderID>'.$trans['orderid'].'</OrderID>';
			}
			/*$request_body .='<Paid>true</Paid>
								<Shipped>true</Shipped>';*/
			$request_body	.= '<Shipment>
									<ShipmentTrackingDetails>
										<ShipmentTrackingNumber>'.$trans['ebay_tracknumber'].'</ShipmentTrackingNumber>
										<ShippingCarrierUsed>'.$trans['ebay_carrier'].'</ShippingCarrierUsed>
									</ShipmentTrackingDetails>
								</Shipment>';			
			$request_body .= '</CompleteSaleRequest>';
			return $this->handle->sendHttpRequest($request_body);
		}
		//只标发货 不同步track info
		public function just_mark_order_shipped($trans){
			$request_body='<?xml version="1.0" encoding="utf-8"?> 
							<CompleteSaleRequest xmlns="urn:ebay:apis:eBLBaseComponents"> 
								<RequesterCredentials> 
									<eBayAuthToken>'.$this->token.'</eBayAuthToken> 
								</RequesterCredentials> 
								<ItemID>'.$trans['itemid'].'</ItemID> 
								<TransactionID>'.$trans['tid'].'</TransactionID>';
			if(!empty($trans['orderid'])){
				$request_body	.='<OrderID>'.$trans['orderid'].'</OrderID>';
			}
			$request_body .='<Paid>true</Paid>
							 <Shipped>true</Shipped>
							</CompleteSaleRequest>';
			return $this->handle->sendHttpRequest($request_body);
		}
	}
?>