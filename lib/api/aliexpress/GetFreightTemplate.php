<?php
/**
 * 功能：根据账号获取运费模板的信息
 */
include_once WEB_PATH."lib/sdk/aliexpress/Aliexpress.php";
class GetFreightTemplate extends Aliexpress {
	public function getFreightTemplate($account) {
		set_time_limit(0);
		$this->apiName	= 'api.listFreightTemplate';
		$this->rootpath	= "openapi";
		$this->doInit();

		$apiInfo	= $this->server.'/'. $this->rootpath.'/'. $this->protocol.'/'. $this->version.'/'. $this->ns.'/'.$this->apiName.'/'. $this->appKey;
		$code_arr	=	array ();

		return $this->curl($apiInfo,$code_arr);
	}
}