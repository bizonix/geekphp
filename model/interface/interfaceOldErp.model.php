<?php
/*
 *仓库系统相关接口操作类(model)
 *@add by : linzhengxiang ,date : 20140528
 */
defined('WEB_PATH') ? '' : exit;
class InterfaceOldErpModel extends InterfaceModel {

	public function __construct(){
		parent::__construct();
	}

	
	/**
	 * 功能：获取旧erp系统中的实时库存信息
	 * @param $sku
	 */
	public function getOldErpSkuStockInfo($sku=''){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['sku'] = $sku;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['error_response']['code'] != 200) self::$errMsg[$data['error_response']['code']] = "[{$data['error_response']['code']}]{$data['error_response']['msg']}";
		return $data;
	}






}
?>