<?php
/*
 *通用验证方法类
 *@add by : linzhengxiang ,date : 20140523
 */
class PackageDpAct extends PackageAct{
	
	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
	}
	
	public function act_packageUpdateOpenServiceStatus($datas){
		return $datas;
	}
	
	/*
	 * 返回分销商的开放类别信息
	 * zjr
	 */
	public function act_packageOpenCategory($datas){
		return $datas;
	}
	/*
	 * 返回从超卖系统中推送过来的接受信息
	 * zjr
	 */
	public function act_packageReciveOversoldSkus($datas){
		return $datas;
	}
	/*
	 * 返回分销商信息
	 * zjr
	 */
	public function act_packageGetDeveloperInfo($datas){
	    return $datas;
	}
	
	/*
	 * 返回分销商信息提供给独立商城的接口
	 * zjr
	 */
	public function act_packagePushOrder($datas){
	    return $datas;
	}
	
	/**
	 * 返回分销商列表
	 * @param array $datas
	 * @return array
	 * @author jbf
	 */
	public function act_packageFindDistributor($datas) {
		return $datas;
	}
	/*
	*返回订单是否插入成功的信息
	*
	*/
	public function act_packageReceiveOrders($data){
		if(!empty($data)){
			$returnData	=	array();
		    foreach ($data as $k=>$v){
		        if($v[0]=='200'){
					$returnData['success'][]	=	$k;
				}else{
					$returnData['error'][$k]	=	array(
						"errCode"	=>	$v[0],
						"errMsg"	=>	$v[1],
					);
				}
		    }
		    return $returnData;
		}else{
		    return false;
		}
	}
	/*
	* 返回分销商订单是否插入成功的信息
	* zjr
	*/
	public function act_packageReceiveDistributorOrder($data){
		if(!empty($data)){
			$returnData	=	array();
		    foreach ($data as $k=>$v){
		        if($v[0]=='200'){
					$returnData['success'][]	=	$k;
				}else{
					$returnData['error'][$k]	=	array(
						"errCode"	=>	$v[0],
						"errMsg"	=>	$v[1],
					);
				}
		    }
		    return $returnData;
		}else{
		    return false;
		}
	}
	/**
	 * 返回产品信息sku数组
	 * @author lzj
	 */
	public function act_packagePaProducts($data){
		return !empty($data) ? $data : null;
	}
	
	/**
	 * 返回分销商的类别信息
	 * @author zjr
	 */
	public function act_packageReturnDistributorCategoryInfo($data){
		return !empty($data) ? $data : null;
	}
	
	/**
	 * 返回SPU对分销商是否开放
	 * @author zjr
	 */
	public function act_packageReturnDistributorSpuIsOpen($data){
		return !empty($data) ? $data : null;
	}
}