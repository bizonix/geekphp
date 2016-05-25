<?php
/**
 * 类名：ShopsAct
 * 功能: 店铺管理
 * 版本：v1.0
 * 作者：zjr
 * 时间：2014/01/18
 * errCode：
 */
class ShopsAct extends CheckAct {
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 獲取店鋪信息 根据公司ID
	 */
	public function act_getCompanyShops($companyId){
		$where 	  = array("belong_company" => $companyId);
		$count	  = M("Shops")->getDataCount($where);
		$p 		  = new Page ($count,10);
		$shopInfo = M("Shops")->getData("*",$where,"order by id desc",$this->page,$this->perpage);
		if(empty($shopInfo)){
			self::$errMsg['10007']	= get_promptmsg('10007','店铺');
			return false;
		}
		$page 		= $p->fpage();
		$platforms = M("Platform")->getAllData("*","1","id");
		return array("shops"=>$shopInfo,"platforms"=>$platforms,"page"=>$page,"count"=>$count);
	}

	/**
	 * 获取所有店鋪信息 根据公司ID
	 */
	public function act_getCompanyAllShops($companyId){
		$where 	  = array("belong_company" => $companyId);
		$shopInfo = M("Shops")->getAllData("*",$where,"id");
		if(empty($shopInfo)){
			self::$errMsg['10007']	= get_promptmsg('10007','店铺');
			return false;
		}
		return array("shops"=>$shopInfo);
	}

	/**
	 * 獲取店鋪信息
	 */
	public function act_getShopInfo($shopAccount,$platform){
		$shopInfo = M("Shops")->getData("*",array("shop_account" => $shopAccount, "platform" => $platform, "belong_company" => get_usercompanyid()));
		if(empty($shopInfo)){
			self::$errMsg['10007']	= get_promptmsg('10007','店铺');
			return false;
		}
		return $shopInfo;
	}

	/**
	 * 获取店铺信息
	 */
	public function act_getShopInfoById($shopId){
		$shopInfo = M("Shops")->getData("*",array("id" => $shopId));
		if(empty($shopInfo)){
			self::$errMsg['10007']	= get_promptmsg('10007','店铺');
			return false;
		}
		$shopInfo = $shopInfo[0];
		if($shopInfo['platform'] != 4){
			$shopInfo['token']	= json_decode($shopInfo['token'],true);
		}else{
			$token = $shopInfo['token'];
			unset($shopInfo['token']);
			$shopInfo['token']['appKey']	= $token;
		}
		return $shopInfo;
	}

	/**
	 * 添加店铺
	 */
	public function act_addShop($shopAccount,$platform,$tokenConf){
		$shops = A("Shops")->act_getShopInfo($shopAccount,$platform);
		if(empty($shops)){
			$insertData = array(
				"shop_account"		=> $shopAccount,
				"platform"			=> $platform,
				"belong_company"	=> get_usercompanyid(),
				"token"				=> $tokenConf,
				"add_time"			=> time(),
				"creater"			=> get_username(),
				"update_time"		=> time(),
			);
			$insertRet = M("Shops")->insertData($insertData);
			if(empty($insertRet)){
				self::$errMsg['10001']	= get_promptmsg('10001','添加店铺');
				return false;
			}else{
				self::$errMsg['200']	= get_promptmsg('200');
				return true;
			}
		}else{
			$updateData = array(
				"token"	=> $tokenConf,
				"update_time"	=> time(),
			);
			$whereData = array(
				"shop_account" => $shopAccount, 
				"platform" => $platform, 
				"belong_company" => get_usercompanyid()
			);
			$updateRet = M("Shops")->updateDataWhere($updateData,$whereData);
			if(empty($updateRet)){
				self::$errMsg['10001']	= get_promptmsg('10001','修改店铺');
				return false;
			}else{
				self::$errMsg['200']	= get_promptmsg('200');
				return true;
			}
		}
	}

	public function act_updateRefrashToken($shopId){
		$shopInfo = M("Shops")->getSingleData("*",array("id"=>$shopId));
		switch ($shopInfo['platform']) {
			case '2':
				$token = json_decode($shopInfo['token'],true);
				A("AliexpressButt")->getAppCode($shopInfo['shop_account'],$token['appKey'],$token['appSecret']);
				break;
			default:
				# code...
				break;
		}
	}

	/**
	 * 删除店铺信息
	 */
	public function act_deleteShop($shopId){
		$shopInfo = M("Shops")->getData("*",array("id" => $shopId,"belong_company"=>get_usercompanyid()));
		if(empty($shopInfo)){
			self::$errMsg['10007']	= get_promptmsg('10007','店铺');
			return false;
		}
		$deleteFlag = M("Shops")->deleteData($shopId);
		if($deleteFlag){
			self::$errMsg['200']	= get_promptmsg('200');
			return true;
		}else{
			self::$errMsg['10001']	= get_promptmsg('10001','删除店铺');
			return false;
		}
	}
}
