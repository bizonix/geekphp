<?php
/**
 * ÑÇÂíÑ·×¥µ¥Àà-»ñÈ¡aliexpressÖ§³ÖµÄÎïÁ÷ÐÅÏ¢
 */
include_once WEB_PATH."lib/api/aliexpress/aliexpressSession.php";
class AliexpressListLogisticsService extends AliexpressSession{
    public function __construct(){
		parent::__construct();
	}
	
    /**
	 * 功能：获取物流信息
	 * by：zjr
	 */
	public function listLogisticsService(){
		$apiName	= 'api.findOrderById';
		$data = array(
			  'access_token' => $this->access_token,
		);
		$url = $this->getUrl($apiName).$this->apiSign($apiName,$data);
		return json_decode($this->Curl($url,$data),true);
	}

}
?>
