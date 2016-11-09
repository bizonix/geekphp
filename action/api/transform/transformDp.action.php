<?php
/*
 *通用验证方法类
 *@add by : linzhengxiang ,date : 20140523
 */
class TransformDpAct extends TransformAct{

	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
		####################### start 扩展通用验证  ##########################
		####################### end   扩展通用验证  ##########################
	}

	public function act_transformUpdateOpenServiceStatus(){
		//xxxxx用做专门扩展验证
	    $companyId     =   $_REQUEST['companyId'];
	    $serverId      =   $_REQUEST['serverId'];
	    $status        =   $_REQUEST['status'];
	    if(empty($companyId)){
	        self::$errMsg[123] = 'empty companyId';
	        return false;
	    }
	    if(empty($serverId)){
	        self::$errMsg[123] = 'empty serverId';
	        return false;
	    }
	    if(empty($status)){
	        self::$errMsg[123] = 'empty status';
	        return false;
	    }
		return true;
	}

	/*
	 * 返回分销商开放类别信息
	 * wcx
	 */
	public function act_transformOpenCategory(){
	    $companyId     =   $_REQUEST['companyId'];
	    $shopAccount   =   $_REQUEST['shopAccount'];
	    if(empty($companyId)){
	        self::$errMsg[123] = 'empty companyId';
	        return false;
	    }
	    if(empty($shopAccount)){
	        self::$errMsg[123] = 'empty shopAccount';
	        return false;
	    }
		return true;
	}
	/*
	 * 返回分销商信息
	 */
	public function act_transformGetDeveloperInfo(){
	    $dpId  =   @$_REQUEST['dpIdArr'];
		if(empty($dpId)){
		    self::$errMsg[123] = 'empty dp_id';
		    return false;
		}
		return true;
    }

	/*
	 * 返回SKU实时库存信息
	 * wcx
	 */
	public function act_transformSkuStock(){
	    $sku     =   $_REQUEST['sku'];
	    if(empty($sku)){
	        self::$errMsg[1234] = 'empty sku';
	        return false;
	    }
		return true;
	}
	/*
	* 返回SKU实时库存信息
	* wcx
	*/
	public function act_transformPushOrder(){
		    return true;
	}
	/**
	 * 返回分销商列表信息
	 * @author jbf
	 */
	public function act_transformFindDistributor() {
		$name = isset($_REQUEST['distributor']) ? trim($_REQUEST['distributor']) : '';
		if (empty($name)) return false;
		
		return true;
	}

	/**
	 * 推送独立商城订单到分销系统
	 * @author wcx
	 */
	public function act_transformReceiveOrders(){
		return true;

	}
	/**
	 * 推送分销商订单到分销系统
	 * @author wcx
	 */
	public function act_transformReceiveDistributorOrder(){
		return true;
	}
	/**
	 * 超卖系统中推送过来的接受信息
	 * @author wcx
	 */
	public function act_transformReciveOversoldSkus(){
	    $oversoldSkus = $_REQUEST['oversoldSkus'];
	    $oversoldSkus = json_decode($oversoldSkus,true);
	    if(empty($oversoldSkus)){
	        self::$errMsg[1237] = 'empty oversoldSkus';
	        return false;
	    }
	    return true;
	}
	/**
	 * 接收用户参数
	 * @author lzj
	 */
	public function act_transformPaProducts(){
		return true;
	}
	
	/**
	 * 接收用户参数
	 * @author wcx
	 */
	public function act_transformReturnDistributorCategoryInfo(){
	    $appkey = $_REQUEST['app_key'];
	    if(empty($appkey)){
	        self::$errMsg[1238] = 'empty appkey';
	        return false;
	    }
		return true;
	}
	/**
	 * 接收用户参数
	 * @author wcx
	 */
	public function act_transformReturnDistributorSpuIsOpen(){
	    $spu   = $_REQUEST['spu'];
	    $email = $_REQUEST['email'];
	    if(empty($spu)){
	        self::$errMsg[1238] = 'empty spu';
	        return false;
	    }
	    if(empty($email)){
	        self::$errMsg[1238] = 'empty email';
	        return false;
	    }
		return true;
	}
}