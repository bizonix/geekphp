<?php
/*
 *产品中心相关接口操作类(model)
 *@add by : linzhengxiang ,date : 20140528
 */
defined('WEB_PATH') ? '' : exit;
class InterfaceDpModel extends InterfaceModel {
	
	public function __construct(){
		parent::__construct();
	}
    
	/**
	 * OpenApi::getCountryCode()
	 * 获取国家代码的接口
	 * @return  array
	 */
	public function getCountryCode($countryCode){
	    $conf = $this->getRequestConf(__FUNCTION__);
	    if (empty($conf)){
	        return false;
	    }
	    $conf['countryCode'] = $countryCode;
	    $result = callOpenSystem($conf);
	    $data = json_decode($result,true);
	    if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
	    return $this->changeArrayKey($data['data']);
	}
	
	/**
	 * getShippingTypeApi()
	 * 获取运输方式的接口
	 * @return  array
	 * @auth: wcx
	 */
	public function getShippingTypeApi(){
		$conf = $this->getRequestConf(__FUNCTION__);
	    if (empty($conf)){
	        return false;
	    }
	    $result = callOpenSystem($conf);
	    $data = json_decode($result,true);
	    if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
	    return $this->changeArrayKey($data['data']);
	}
	
	public function getDistributorList($param) {
	    $request = self::buildRequest($param, 'findDistributorList');
	    return callOpenSystem($request);
	
	    //		$res = '{"count":"7","list":[{"id":"10","type":"2","level":"1","token":"44dbcb5df54bbf7b7806513aadf070ff","company":"\u5e7f\u5dde\u5546\u94ed\u8f6f\u4ef6\u6709\u9650\u516c\u53f8","intention_products":"[\"2\",\"6\",\"10\"]"},{"id":"14","type":"2","level":"1","token":"f156c21999f8379198f2ff56789afa64","company":"\u6df1\u5733\u5e02\u7231\u6dd8\u57ce\u7f51\u7edc\u79d1\u6280\u6709\u9650\u516c\u53f8","intention_products":"[\"2\",\"6\",\"10\"]"},{"id":"17","type":"2","level":"1","token":"47d21f38b5d3ad3fcf60c0550c46d66e","company":"\u6df1\u5733\u5e02\u50b2\u57fa\u7535\u5b50\u5546\u52a1\u6709\u9650\u516c\u53f8","intention_products":"[\"2\",\"6\",\"10\"]"},{"id":"18","type":"2","level":"1","token":"d328c8c21c0d596eae8bb3e2fedc51bd","company":"\u6df1\u5733\u6613\u8054\u8f6f\u4ef6\u6709\u9650\u516c\u53f8","intention_products":"[\"2\",\"6\",\"10\"]"},{"id":"19","type":"2","level":"1","token":"187c4daeead9f5013b0e062c09940684","company":"\u6df1\u5733\u5e02\u8d5b\u7ef4\u7f51\u7edc\u79d1\u6280\u6709\u9650\u516c\u53f8","intention_products":"[\"2\",\"6\",\"10\"]"},{"id":"20","type":"2","level":"1","token":"c1e35582e423b00ff2759b9c3f166340","company":"\u6df1\u5733\u5e02\u8d5b\u7ef4\u7f51\u7edc\u79d1\u6280\u6709\u9650\u516c\u53f8","intention_products":"[\"2\",\"6\",\"10\"]"},{"id":"29","type":"2","level":"1","token":"","company":"","intention_products":"[\"2\",\"6\",\"10\"]"}]}';
	    //		return json_decode($res,true);
	}
}
?>