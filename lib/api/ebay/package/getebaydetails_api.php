<?php
/*
 * 抓取订单的某个属性
 * @add lzx, date 20140612
 */
include_once WEB_PATH."lib/api/ebay/eBaySession.php";
class GeteBayDetails extends eBaySession{
	
	protected $verb = 'GeteBayDetails';
	
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 获取订单当前收款的邮箱
	 * @param int $itemid
	 * @return array
	 * @author lzx
	 */
	public function geteBayDetails($detailname){			
		$requestXmlBody = ' <?xml version="1.0" encoding="utf-8"?>
			<'.$this->verb.'Request xmlns="urn:ebay:apis:eBLBaseComponents">
				<RequesterCredentials>
					<eBayAuthToken>'.$this->requestToken.'</eBayAuthToken>
				</RequesterCredentials>
				<DetailName>'.$detailname.'</DetailName>
				<WarningLevel>High</WarningLevel>
			</'.$this->verb.'Request>';
		return $this->sendHttpRequest($requestXmlBody);
	}
}
?>