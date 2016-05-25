<?php
/*
 *仓库系统相关接口操作类(model)
 *@add by : linzhengxiang ,date : 20140528
 */
defined('WEB_PATH') ? '' : exit;
class InterfaceOrderModel extends InterfaceModel {

	public function __construct(){
		parent::__construct();
	}

	
	/**
	 * 功能：同步订单信息到订单系统
	 * @param $orderdatas
	 */
	public function synOrderInfoToOrderSys($orderdatas=''){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['orderdatas'] = $orderdatas;
		$conf['cachetime']	= 0;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['error_response']['code'] != 200) self::$errMsg[$data['error_response']['code']] = "[{$data['error_response']['code']}]{$data['error_response']['msg']}";
		return $data;
	}
	
	/**
	 * 功能：订单系统提供的订单信息的接口  ，包括费用
	 * @param $recordnumber
	 * @param $account
	 */
	public function getOrderInfo($recordnumber='',$account=''){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['recordnumber'] = $recordnumber;
		$conf['$account']     = $$account;
		$conf['cachetime']	  = 0;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['error_response']['code'] != 200) self::$errMsg[$data['error_response']['code']] = "[{$data['error_response']['code']}]{$data['error_response']['msg']}";
		return $data;
	}






}
?>