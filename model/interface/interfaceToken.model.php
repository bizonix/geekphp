<?php
/*
 *token相关接口操作类(model)
 *@add by : linzhengxiang ,date : 20140611
 */
defined('WEB_PATH') ? '' : exit;
class InterfaceTokenModel extends InterfaceModel {
	
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 跟进平台和账号获取对应的token
	 * @param string $acount
	 * @param string $platform
	 * @return string
	 * @author lzx
	 */
	public function getToken($acount, $platform){
		return $token;
	}
}
?>