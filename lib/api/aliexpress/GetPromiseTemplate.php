<?php
/**
 * 功能：根据账号和模板ID获取服务模板的信息
 */
include_once WEB_PATH."lib/sdk/aliexpress/Aliexpress.php";
class GetPromiseTemplate extends Aliexpress {
	public function getPromiseTemplate($account,$templateId) {
		set_time_limit(0);
		$this->apiName	= 'api.queryPromiseTemplateById';
		$this->rootpath	= "openapi";
		$this->doInit();

		$apiInfo	= $this->server.'/'. $this->rootpath.'/'. $this->protocol.'/'. $this->version.'/'. $this->ns.'/'.$this->apiName.'/'. $this->appKey;
		$code_arr	=	array (
			'templateId' => $templateId
		);

		return $this->curl($apiInfo,$code_arr);
	}
}