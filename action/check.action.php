<?php
/*
 *通用验证方法类
 */
class CheckAct extends CommonAct{

	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
	}

	/**
	 * 验证用户名是否合法，字母，下划线，数字，字母开头，6-16
	 * @param string $userName
	 */
	protected function act_checkUserName ($userName){
		if (preg_match("/^[a-zA-Z][_a-zA-Z0-9]{5,15}$/", $userName)===0){
			$this::$errMsg[20009] =   get_promptmsg(20009);
			return false;
		}
		return true;
	}

	/**
	 * 验证邮箱否合法
	 * @param string $email
	 */
	protected function act_checkEmailStr ($email){
		if (preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', $email)===0){
			$this::$errMsg[20010] =   get_promptmsg(20010);
			return false;
		}
		return true;
	}

	/**
	 * 验证国家简码是否符合规范和是否存在
	 * @param string $shortcode
	 */
	protected function act_checkCountryCode ($shortcode){
		if (preg_match("/^[A-Z]{2,3}$/", $shortcode)===0){
			$this::$errMsg[10001] =   get_promptmsg(10001,$shortcode);
			return false;
		}
		/*查询模型验证 待完成*/
		return true;
	}

	/**
	 * 验证国家名称是否符合规范和是否存在
	 * @param string $shortcode
	 */
	protected function act_checkCountryName ($countryname){
		if (preg_match("/^[A-Z]{1}/", $countryname)===0){
			$this::$errMsg[10002] =   get_promptmsg(10002,$countryname);
			return false;
		}
		/*查询模型验证 待完成*/
		return true;
	}

	/**
	 * 验证SKU是否符合规范和是否存在
	 * @param string $sku
	 */
	protected function act_checkSkuEffect ($sku){
		if (preg_match("/^[A-Z0-9]{3}/", $sku)===0){
		    $this::$errMsg[10003] =   get_promptmsg(10003,$sku);
			return false;
		}
		/*查询模型验证 待完成*/
		return true;
	}

	protected function act_formatField(){

	}

    /*
     * 验证一下数据是否有特殊字符
     */
    protected function act_filterScript($sring){
    	return preg_replace("/<script[^>]*>.*<\/script>/si", '', $sring);
    }
    /*
     * InterfaceVersion添加修改时数据判断
     */
    protected function act_InterfaceVersionDataCheck($data){
        if(empty($data)){
            return false;
        }
    }
    /**
     * 获取订单状态新增数据
     */
    public function act_getOrderRelationshipData(){
    	$data	=	array();
    	if(!empty($_REQUEST['id'])){
    		$data['id']		=	$_REQUEST['id'];
    	}
    	if(!empty($_REQUEST['orderSys_status'])){
    		$data['orderSys_status']	=	$_REQUEST['orderSys_status'];
    	}
    	if(!empty($_REQUEST['dpSys_status_id'])){
    		$data['dpSys_status_id']=	$_REQUEST['dpSys_status_id'];
    	}
    	if(!empty($_REQUEST['dpSys_declare'])){
    		$data['dpSys_declare']	=	$_REQUEST['dpSys_declare'];
    	}else{
    		$data['dpSys_declare']	=	'';
    	}
    	
    	return $data;
    }
}
?>