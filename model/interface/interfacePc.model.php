<?php
/*
 *产品中心相关接口操作类(model)
 *@add by : linzhengxiang ,date : 20140528
 */
defined('WEB_PATH') ? '' : exit;
class InterfacePcModel extends InterfaceModel {
	
	public function __construct(){
		parent::__construct();
	}
    
	/**
     * 获取单个sku信息
	 * @param string $sku 
	 * @return array
	 * @author lzx
     */
	public function getSkuInfo($sku){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['sku'] = $sku;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
    }
    
    /**
     * 获取单个sku重量，支持虚拟SKU
	 * @param string $sku 
	 * @return array
	 * @author lzx
     */
	public function getSkuWeight($sku){
	   $ret = $this->getSkuInfo($sku);
       $skuInfo = $ret['skuInfo'];
       $returnWeight = 0;
       foreach($skuInfo as $value){
         $skuDetail = $value['skuDetail'];
         $amount = $value['amount'];
         $returnWeight += $skuDetail['goodsWeight'] * $amount;
       }
       return $returnWeight;
    }
    
	/**
     * 获取包材信息
	 * @return array
	 * @author lzx 
	 * @modify wcx
     */
	public function getMaterList(){
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
     * 根据包材id获取包材信息
     * @param int $mid
	 * @return array
	 * @author lzx
     */
	public function getMaterInfoById($mid){
        $materlist = $this->key('id')->getMaterList();
        return isset($materlist[$mid]) ? $materlist[$mid] : false;
    }
    
    /**
     * 获取所有的料号转换记录数组
	 * @return array('old_sku'=>'new_sku','old_sku'=>'new_sku'，……)
	 * @author zqt
     */
	public function getSkuConversionArr(){
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
     * 获取所有的料号转换记录数组
     * @return array('old_sku'=>'new_sku','old_sku'=>'new_sku'，……)
     * @author zqt
     */
    public function getRootCategoryInfo($flag=''){
        $conf = $this->getRequestConf(__FUNCTION__);
        if (empty($conf)){
            return false;
        }
        $result = callOpenSystem($conf);
        $data = json_decode($result,true);
        if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
        $data = $data['data'];
        if($flag == 'all'){
            return $data;
        }
        $rootCategoryInfo   =   array();
        foreach($data as $k=>$v){
            if(strpos($v['path'], '-')==false&&$v['is_delete']=='0'){
                $rootCategoryInfo[$v['id']] =   $v['name'];
            }
        }
        return $rootCategoryInfo;
    }
    
    /**
     * getGoodsStatus()
     * 获取物品状态
     * @return  array
     * @author wcx
     */
    public function getGoodsStatus($sku)
    {
        $conf = $this->getRequestConf(__FUNCTION__);
        if (empty($conf)){
            return false;
        }
        $conf['sku'] = $sku;
        $result = callOpenSystem($conf);
        $data = json_decode($result,true);
        if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
        return $this->changeArrayKey($data['data']);
    }
    
    public function getProductBySku($sku) {
        $conf = $this->getRequestConf(__FUNCTION__);
        if (empty($conf)){
            return false;
        }
        $conf['sku'] = $sku;
        $result = callOpenSystem($conf);
        $data = json_decode($result, true);
        if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
        return $this->changeArrayKey($data['data']);
    }
    
    /**
     * getSkuWeightInfo()
     * 获取sku*数量的重量
     * @return  float
     * @author wcx
     */
    public function getSkuWeightInfo($sku,$amount=1){
    	$skuInfo	=	$this->getProductBySku($sku);
    	$weight		=	$skuInfo['goodsWeight'];
    	return $weight*$amount;
    }
    /**
     * getSkuPMWeightInfo()
     * 获取包材*数量的重量
     * @return  float
     * @author wcx
     */
    public function getSkuPMWeightInfo($sku,$amount=1){
    	$skuInfo	=	$this->getProductBySku($sku);
    	$pmId		=	$skuInfo['pmId'];
    	$materlist = $this->key('id')->getMaterList();
        return isset($materlist[$pmId]) ? $materlist[$pmId]['pmWeight']*$amount : false;
    }
    /**
     * getSkuFeeInfo()
     * 获取sku成本
     * @return  float
     * @author wcx
     */
    public function getSkuFee($sku){
    	$skuInfo	=	$this->getProductBySku($sku);
    	return $skuInfo['goodsCost'];
    }
    /**
     * getSkuFeeInfo()
     * 获取sku包材成本
     * @return  float
     * @author wcx
     */
    public function getSkuPackageFee($sku,$amount=1){
    	$skuInfo	=	$this->getProductBySku($sku);
    	$pmId		=	$skuInfo['pmId'];
    	$materlist = $this->key('id')->getMaterList();
    	return isset($materlist[$pmId]) ? $materlist[$pmId]['pmCost']*$amount : false;
    }
    /**
     * 获取包材类型
     * @return Ambigous <number, boolean>
     * @author wcx
     */
    public function getSkuPackageType($sku){
    	$skuInfo	=	$this->getProductBySku($sku);
    	$pmId		=	$skuInfo['pmId'];
    	$materlist = $this->key('id')->getMaterList();
    	return isset($materlist[$pmId]) ? $materlist[$pmId]['pmName'] : false; 
    }
    
    /**
     * 根究条件获取产品列表
     * @param array $param
     * @return boolean|Ambigous <multitype:, array, multitype:unknown >
     * @author jbf
     */
    public function getProductList($param) {
        $conf = self::addParamToRequest($this->getRequestConf(__FUNCTION__), $param);
        if (empty($conf)){
            return false;
        }
        $result = callOpenSystem($conf);
        $data = json_decode($result, true);
        if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
        return $this->changeArrayKey($data['data']);
    }
    
    public function getProductDesc($spu) {
        $conf = $this->getRequestConf(__FUNCTION__);
        if (empty($conf)){
            return false;
        }
        $conf['spuSn'] = $spu;
        $result = callOpenSystem($conf);
        $data = json_decode($result, true);
        if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
        return $this->changeArrayKey($data['data']);
    }
    
    public function addParamToRequest($conf, $param=array()) {
        if (!empty($param)) {
            foreach ($param AS $key => $value) {
                if (!empty($value)) $conf[$key] = $value;
            }
        }
        
        return $conf;
    }
}
?>