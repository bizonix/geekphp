<?php
/*
 *仓库系统相关接口操作类(model)
 *@add by : linzhengxiang ,date : 20140528
 */
defined('WEB_PATH') ? '' : exit;
class InterfaceWhModel extends InterfaceModel {
	
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 取消交易,仓库废弃订单
	 * @param int $orderid
	 * @param int $storeId
	 * @return 
	 * @author lzx
	 */
	public function discardShippingOrder($orderid, $storeId = 1){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['oidStr'] = $orderid;
		$conf['storeId'] = $storeId;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $data;
	}
	
	/**
     * 获取SKU实际库存
	 * @param string $sku 
	 * @return array
	 * @author lzx
     */
	public function getSkuStock($sku, $storeId=1){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['sku'] = $sku;
		$conf['storeId'] = $storeId;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $data;
    }
    
	/**
	 * 获取订单下仓库配货记录
	 * @param int $orderId
	 * @return array
	 * @author lzx
	 */
	public function getOrderPickingInfo($orderId){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['orderId'] = $orderId;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $data;	
	}
	
	/**
	 * 获取仓库配货记录
	 * @param int $orderId
	 * @param int $sku
	 * @return array
	 * @author lzx
	 */
	public function getOrderSkuPickingRecords($orderId, $sku){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['sku'] = $sku;
		$conf['orderId'] = $orderId;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $data;
	}
    
    /**
	 * 获取异常订单的配货信息，参数orderId（订单系统订单号）（wh）
	 * @param int $orderId
	 * @return array 返回料号 已配货成功为1,没有配货成功（没有配或者没配够）返回为0
	 * @author zqt
	 */
	public function getAbOrderInfo($orderId){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['orderId'] = $orderId;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $data;
	}
    
    //拉取异常订单接口，不用传参考，返回订单系统订单数组(wh)
	public function getAbOrderList(){
	    $conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $data['data'];
	}

    
    /**
	 * 根据SKU获取仓位
	 * @param sku 
     * @param storeId 默认为1，深圳A仓
	 * @return {"errCode":0,"errMsg":"","data":"[{\"pName\":\"B9002\"}]"} 
	 * @author zqt
	 */
	public function getSkuPosition($sku, $storeId=1){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['sku'] = $sku;
        $conf['storeId'] = $storeId;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		$data = json_decode($data['data'],true);
		return $data;
	}
    
    //拆分订单接口，参数orderId（订单系统订单号）
	public function operateAbOrder($orderId){
	    $conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['orderId'] = $orderId;
        $conf['calcWeight'] = $calcWeight;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
	}
    
    //获取入库接口（wh）
	public function getInRecords(){
	    $conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);	
	}
    
    //获取出库接口（wh）
	public static function getOutRecords(){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);	
	}


}
?>