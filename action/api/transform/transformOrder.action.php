<?php
/*
 *通用验证方法类
 *@add by : linzhengxiang ,date : 20140523
 */
class TransformOrderAct extends TransformAct{
	
	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
		####################### start 扩展通用验证  ##########################
		####################### end   扩展通用验证  ##########################
	}
	
	public function act_transformGetOrder(){
		//xxxxx用做专门扩展验证
		self::$errMsg[123] = 'for you test error';
		return true;
	}
}