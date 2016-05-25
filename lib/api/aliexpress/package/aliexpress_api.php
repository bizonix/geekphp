<?php
/**
 * 速卖通基础信息api
 */
include_once WEB_PATH."lib/api/aliexpress/aliexpressSession.php";
class Aliexpress extends AliexpressSession{
    public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 功能：获取物流信息
	 * by：zjr
	 */
	public function listLogisticsService(){
		$apiName	= 'api.listLogisticsService';
		$data = array(
			  'access_token' => $this->access_token,
		);
		$url = $this->getUrl($apiName).$this->apiSign($apiName,$data);
		return json_decode($this->Curl($url,$data),true);
	}
}
?>
