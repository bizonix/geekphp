<?php
/*
 *运输方式管理相关接口操作类(model)
 *@add by : linzhengxiang ,date : 20140528
 */
defined('WEB_PATH') ? '' : exit;
class InterfaceTranModel extends InterfaceModel {

	public function __construct(){
		parent::__construct();
	}

	/**
     * 获取所有渠道信息
	 * @return array
	 * @author lzx
     */
	public function getChannelList($carrierId='all'){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['carrierId'] = $carrierId;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
    }

    /**
     * 获取运输方式列表信息,填写正确的运输方式参数类型（0非快递，1快递，2全部）
	 * @return array
	 * @author lzx
     */
	public function getCarrierList($type){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['type'] = "$type";
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
    }
	
	/**
     * 获取运输方式列表信息,填写正确的运输方式参数类型（0非快递，1快递，2全部）
	 * @return array
	 * @author lzx
     */
	public function getCarrierNameList($type, $flip = false){
		$CarrierList = $this->getCarrierList($type);
		$CarrierNameList = array();
		foreach($CarrierList as $value){
			$CarrierNameList[$value['id']] = $value['carrierNameCn'];
		}
		if($flip){
			$CarrierNameList = array_flip($CarrierNameList);
		}
		return $CarrierNameList;
    }
	
	/**
     * 获取运输方式列表信息,填写正确的运输方式参数类型（0非快递，1快递，2全部）
	 * @return array
	 * @author lzx
     */
	public function getCarrierNameById($transportId){
		$CarrierList = $this->getCarrierNameList(2);
		return $CarrierList[$transportId];
    }

    /**
     * 获取最优运输方式 (接口需要改造，必须传可选的运输方式数组，接口从该数组中选择最优的返回)
     * @param $countryName 国家
	 * @param $calcWeight 重量
     * @param $shipaddr 发货地址 默认为1，深圳
     * @param $zipCode 邮编，默认为空
     * @param $noShipId 中转地址ID，默认为空
     * @return array
	 * @author zqt
     */
	public function getBestShippingFee($countryName, $calcWeight, $shipaddr=1, $zipCode='', $noShipId=''){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['country'] = $countryName;
        $conf['weight'] = $calcWeight;
        $conf['shipAddId'] = $shipaddr;
        if(!empty($zipCode)){
            $conf['postCode'] = $zipCode;
        }
        if(!empty($noShipId)){
            $conf['noShipId'] = $noShipId;
        }
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
    }

    /**
     * 获取固定运输方式
     * @param $transportId 运输方式Id
     * @param $countryName 国家
     * @param $calcWeight 重量
	 * @return array
	 * @author zqt
     */
	public function getFixShippingFee($transportId, $countryName, $calcWeight){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
        $conf['carrierId'] = $transportId;
		$conf['country'] = $countryName;
        $conf['weight'] = $calcWeight;
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
	public static function getShippingTypeApi(){
		$conf = $this->getRequestConf(__FUNCTION__);
	    if (empty($conf)){
	        return false;
	    }
	    $result = callOpenSystem($conf);
	    $data = json_decode($result,true);
	    if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
	    return $this->changeArrayKey($data['data']);
	}
	
	/**
	 * getShippingTypeApi()
	 * 获取运输方式的接口（含有id）
	 * @return  array
	 * @auth: wcx
	 */
	public  function getShippingTypeApi2($type = '2'){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['type']	=	'2';
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
	}
	/**
	 * 获取所有标准国家
	 * wcx
	 */
	public function getCountryInfo($type="ALL"){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['type']	=	$type;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
	}
	
	/**
	 * 获取所有邮费
	 * wcx
	 */
	public function getPostFeeByTransSys($transportType,$allWeight,$shipAddId,$country){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		if($allWeight>2){
			$tmp		=	$allWeight;
			$allWeight	=	2;
		}else{
			$tmp		=	2;
		}
		$conf['carrierAbb']	=	$transportType;
		$conf['weight']	=	$allWeight;
		$conf['shipAddId']	=	$shipAddId;
		$conf['country']	=	$country;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		$data[$transportType]['totalFee']	=	($tmp/2)*$data[$transportType]['totalFee'];
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
	}
	
	/**
	 * 获取跟踪号信息状态接口
	 * lzj
	 */
	public function getTransTracknumStatus($tracknum, $is_new = '0'){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		
		$conf['tracknum']	=	$tracknum;
		$conf['is_new']		=	$is_new;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);

		if (isset($data['errCode']) && $data['errCode'] >0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		
		return isset($data['data']) ? $this->changeArrayKey($data['data']) : null;
	}
}
?>