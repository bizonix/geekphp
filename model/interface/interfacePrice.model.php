<?php
/*
 *产品中心相关接口操作类(model)
 *@add by : linzhengxiang ,date : 20140528
 */
defined('WEB_PATH') ? '' : exit;
class InterfacePriceModel extends InterfaceModel {
	
	public function __construct(){
		parent::__construct();
	}
    
	/**
     * 获取定价规则
	 * @param int $dpId
	 * @return array
	 * @author wcx
     */
	public function getPriceRuleInfo($dpId){
		//$aa='{"dp_id":"94","init_cost":"1.99","accumulation_cost":"0.05","good_fee_type":"1","all_section":"null","fz_section":"[{\"brandMin\":\"0\",\"brandMax\":\"9999\",\"brandProfitMin\":\"15\",\"brandProfitMax\":\"15\",\"brandAdditional\":\"0\"}]","os_section":"[{\"overseaMin\":\"0\",\"overseaMax\":\"9999\",\"overseaProfitMin\":\"100\",\"overseaProfitMax\":\"100\",\"overseaAdditional\":\"0\"}]","sv_section":"[{\"generalMin\":\"0\",\"generalMax\":\"1\",\"generalProfitMin\":\"14\",\"generalProfitMax\":\"14\",\"generalAdditional\":\"0\"},{\"generalMin\":\"1\",\"generalMax\":\"10\",\"generalProfitMin\":\"10\",\"generalProfitMax\":\"14\",\"generalAdditional\":\"0\"},{\"generalMin\":\"10\",\"generalMax\":\"30\",\"generalProfitMin\":\"6\",\"generalProfitMax\":\"10\",\"generalAdditional\":\"0\"},{\"generalMin\":\"30\",\"generalMax\":\"9999\",\"generalProfitMin\":\"6\",\"generalProfitMax\":\"6\",\"generalAdditional\":\"0\"}]","other_sku_section":null,"postage_fee":{"0":{"transportation_id":"0","weight_rule":"[{\"weightMin\":\"0\",\"weightMax\":\"9999\",\"weightProfit\":\"1\"}]","fix_discount":"1.20","design_by_weight":"1","design_by_commission":"1","design_by_commission_value":"6.00"},"4":{"transportation_id":"4","weight_rule":"[{\"weightMin\":\"0\",\"weightMax\":\"0.5\",\"weightProfit\":\"10\"},{\"weightMin\":\"0.5\",\"weightMax\":\"1.5\",\"weightProfit\":\"10\"},{\"weightMin\":\"1.5\",\"weightMax\":\"2\",\"weightProfit\":\"8\"},{\"weightMin\":\"2\",\"weightMax\":\"9999\",\"weightProfit\":\"0\"}]","fix_discount":"0.00","design_by_weight":"1","design_by_commission":"0","design_by_commission_value":"9.00"}}}';
		//return json_decode($aa,true);
		$conf = $this->getRequestConf(__FUNCTION__);
		$conf['dpId'] = $dpId;
		//$conf['dpId'] = 199;//测试使用
		//$conf['cachetime']=0;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
    }

	/**
     * 获取分销商运输方式
	 * @param int $dpId
	 * @return array
	 * @author wcx
     */
	public function getCarrierInfo($dpId){
		$conf			=	$this->getRequestConf(__FUNCTION__);
		$conf['dpId']	=	trim($dpId);
		$conf['dpId'] = 94;//测试使用
		//var_dump($conf);exit;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
    }
    
    public function getProductsPriceByList($hbParam) {
        
        
    }
}