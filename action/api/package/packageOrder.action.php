<?php
/*
 *通用验证方法类
 *@add by : linzhengxiang ,date : 20140523
 */
class PackageOrderAct extends PackageAct{
	
	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
	}
	
	public function act_packageGetOrder($datas){
		$testdata = array();
		foreach($datas AS $id=>$data){
			$testdata[$id] = $data['order'];
		}
		unset($datas);
		return $testdata;
	}
}