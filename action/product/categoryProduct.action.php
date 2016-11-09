<?php
/**
 * 类名：CategoryProductAct
 * 功能：登录
 * 版本：v1.0
 * 作者：wcx
 * 时间：2015/01/04
 */
class CategoryProductAct extends CheckAct {
	
	public function __construct(){
		parent::__construct();
	}
	//获取地址列表信息
	public function act_getCategory() {
		$categorys	= M("GoodsCategory")->getData("*",'1',"order by id asc",1,9999);
		if(empty($categorys)){
			self::$errMsg['10007']	= get_promptmsg('10007',"分类");
			return false;
		}else{
			return $categorys;
		}
	}



}