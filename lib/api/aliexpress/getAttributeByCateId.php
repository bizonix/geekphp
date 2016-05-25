<?php
/**
 * 功能：根据类别ID获取类别属性的信息
 */
include_once WEB_PATH."lib/sdk/aliexpress/Aliexpress.php";
class getAttributeByCateId extends Aliexpress {
	public function getAttributeInfo($cateId) {
		set_time_limit(0);
		$this->apiName	= 'api.getAttributesResultByCateId';
		$this->rootpath	= "openapi";
		$this->doInit();

		$apiInfo		= $this->server.'/'. $this->rootpath.'/'. $this->protocol.'/'. $this->version.'/'. $this->ns.'/'.$this->apiName.'/'. $this->appKey;
		$code_arr=	array (
			'access_token'		=> $this->access_token,
			'cateId'			=> $cateId,
		);

		//echo $apiInfo;
		return $this->curl($apiInfo,$code_arr);
	}
}