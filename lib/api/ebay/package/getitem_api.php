<?php
/*
 * 抓取订单的某个属性
 * @add lzx, date 20140612
 */
include_once WEB_PATH."lib/api/ebay/eBaySession.php";
class GetItem extends eBaySession{
	
	protected $verb = 'GetItem';
	
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 获取订单当前收款的邮箱
	 * @param int $itemid
	 * @return array
	 * @author lzx
	 */
	public function getPayPalEmailAddress($itemid){			
		$requestXmlBody = ' <?xml version="1.0" encoding="utf-8"?>
			<'.$this->verb.'Request xmlns="urn:ebay:apis:eBLBaseComponents">
				<RequesterCredentials>
					<eBayAuthToken>'.$this->requestToken.'</eBayAuthToken>
				</RequesterCredentials>
				<OutputSelector>Item.PayPalEmailAddress</OutputSelector>
				<ItemID>'.$itemid.'</ItemID>
				<WarningLevel>High</WarningLevel>
			</'.$this->verb.'Request>';
		return $this->sendHttpRequest($requestXmlBody);
	}

	public function getItem($itemid){
		$this->setItemID($itemid);
		return $this->sendHttpRequest();
	}
}
?>