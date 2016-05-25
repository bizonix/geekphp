<?php
/**
 * 类名：ShopsModel
 * 功能：店铺管理
 * 版本：V1.0
 * 作者：zjr
 * 时间：2015-01-18
 */
class ShopsModel extends CommonModel{

	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 获取用户平台店铺
	 */
	public function getUserPlatformShops($companyId,$platform,$fileds = '*'){
	    if(empty($companyId)){
	        self::$errMsg['10019'] = get_promptmsg('10019','companyId');
	        return false;
	    }
	    if(empty($platform)){
	        self::$errMsg['10019'] = get_promptmsg('10019','platform');
	        return false;
	    }
	    $shops = $this->getAllData($fileds,array('belong_company' => $companyId , 'platform' => $platform),'id');
	    return $shops;
	}
	
}
?>