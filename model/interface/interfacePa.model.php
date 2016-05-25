<?php
/*
 *产品中心相关接口操作类(model)
 *@add by : linzhengxiang ,date : 20140528
 */
defined('WEB_PATH') ? '' : exit;
class InterfacePaModel extends InterfaceModel {
	
	public function __construct(){
		parent::__construct();
	}
    
	/**
     * 获取不运送国家
	 * @param int $siteId
	 * @return array
	 * @author zjr
     */
	public function getExcludeShippingCountry($siteID){
		$conf = $this->getRequestConf(__FUNCTION__);
		$conf['siteId'] = $siteID;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
    }
    
	/**
     * 获取运送国家
	 * @param int $siteId
	 * @return array
	 * @author zjr
     */
	public function getShippingCountry($siteID){
		$conf = $this->getRequestConf(__FUNCTION__);
		$conf['siteId'] = $siteID;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
    }
    
	/**
     * 同步分分销商店铺信息
	 * @param int $siteId
	 * @return array
	 * @author zjr
     */
	public function synDistributorShopInfor($compayId,$shopInfo){
		$conf = $this->getRequestConf(__FUNCTION__);
		$conf['compayId'] 	= $compayId;
		$conf['dpShopInfo'] = $shopInfo;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
    }
	/**
     * 同步分分销商开放类目信息
	 * @param int $siteId
	 * @return array
	 * @author zjr
     */
	public function synDistributorOpenCategory($compayId,$category){
		$conf = $this->getRequestConf(__FUNCTION__);
		$conf['companyId'] 	= $compayId;
		$conf['category'] = $category;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
    }
    /**
     * 整合ebay刊登系统的数据库（建库，分配权限，屏蔽价格，屏蔽分类）
     * @param int $compayId
     * @return array
     * @author wcx
     */
    public function createEbayDB($compayId,$cateIds){
        $conf = $this->getRequestConf(__FUNCTION__);
        $conf['compayId'] = $compayId;
        $conf['cateIds']  = $cateIds;
        $result = callOpenSystem($conf);

        $data = json_decode($result,true);
        if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
        return $this->changeArrayKey($data['data']);
    }
    /**
     * 整合ebay刊登系统的数据库（获取log和整合情况）
     * @param int $compayId
     * @return array
     * @author wcx
     */
    public function getCreateEbayDBInfo($compayId,$cateIds){
        $conf = $this->getRequestConf(__FUNCTION__);
        $conf['compayId'] = $compayId;
        $result = callOpenSystem($conf);
        $data = json_decode($result,true);
        if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
        return $this->changeArrayKey($data['data']);
    }
    /**
     * 取得PA产品数据，根据产品类别获取推送至用户的SPU与SKU
     * @param string $user_key 用户key
     * @param int $category_id 分类ID
     * @param string $url_callback 回调地址
     */
    public function getPaProductsByCategory($request_arr) {
		$conf					 = $this->getRequestConf(__FUNCTION__);
		$conf['user_key']		 = $request_arr['user_key'];
		$conf['category_id']	 = $request_arr['category_id'];
		$conf['url_callback']	 = $request_arr['url_callback'];
		$conf['allow_categorys'] = $request_arr['allow_categorys'];
		$conf['spu']			 = $request_arr['spu'];
		$result					 = callOpenSystem($conf);
		$data					 = json_decode($result, true);
		if(isset($data['errCode']) && $data['errCode'] > 0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data);
    }
    
    /**
     * 调用PA系统接口用户屏蔽分类
     * @author zjr
     */
    public function updateOpenCategory($companyId) {
		$conf					 = $this->getRequestConf(__FUNCTION__);
		$conf['companyId']	     = $companyId;
		$result					 = callOpenSystem($conf);
		$data					 = json_decode($result, true);
		if(isset($data['errCode']) && $data['errCode'] > 0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data);
    }
    /**
     * 调用PA系统接口添加账号
     * @author zjr
     */
    public function addDistributorAccount($ebayAccount,$paypal,$platFormId,$companyId,$siteId) {
		$conf					 = $this->getRequestConf(__FUNCTION__);
		$conf['ebay_account']	 = $ebayAccount;
		$conf['paypal']	         = $paypal;
		$conf['platformId']	     = $platFormId;
		$conf['companyId']	     = $companyId;
		$conf['siteId']			 = $siteId;
		$result					 = callOpenSystem($conf);
		$data					 = json_decode($result, true);
		if(isset($data['errCode']) && $data['errCode'] > 0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data);
    }
    /**
     * 调用PA系统修改添加账号
     * @author zjr
     */
    public function updateDistributorAccount($ebayAccount,$status,$companyId,$platformId,$siteId) {
		$conf					 = $this->getRequestConf(__FUNCTION__);
		$conf['ebay_account']	 = $ebayAccount;
		$conf['status']	         = $status;
		$conf['companyId']	     = $companyId;
		$conf['platformId']		 = (string)$platformId;
		$conf['siteId']		     = (string)$siteId;
		$result					 = callOpenSystem($conf);
		$data					 = json_decode($result, true);
		if(isset($data['errCode']) && $data['errCode'] > 0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data);
    }
    
}
?>