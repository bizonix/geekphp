<?php
/**
 * 上传速卖通跟踪号
 */
include_once WEB_PATH."lib/api/aliexpress/aliexpressSession.php";
class AliexpressSellerShipment extends AliexpressSession{
    public function __construct(){
		parent::__construct();
	}
    
	public function sellerShipment($serviceName, $logisticsNo, $sendType, $outRef, $description="",$Website=""){

		$apiName	= 'api.sellerShipment';
		$data = array(
		  	'access_token'	=>	$this->access_token,
		  	'serviceName'	=>	$serviceName,
			'logisticsNo'	=>	$logisticsNo,
			'sendType'		=>	$sendType,
			'outRef'		=>	$outRef
		);
		if(!empty($description)){
			$data['description']    = $description;
		}
		if(!empty($Website)){
			$data['trackingWebsite']   = $Website;
		}
		$url = $this->getUrl($apiName).$this->apiSign($apiName,$data);
		return json_decode($this->Curl($url,$data),true);
	}
}
?>
