<?php
/**
 * 类名：OrderDetailsModel
 * 功能：订单详细表
 * 版本：V1.0
 * 作者：wcx
 * 时间：2014-09-22
 */
class OrderDetailsModel extends CommonModel{

	public function __construct(){
		parent::__construct();
	}
	
	
	
	/**
	 * ShipfeeQueryModel::getStandardCountryName()
	 * 获得所有标准国家名称
	 * @return array
	 */
	public function getStandardCountryName(){
		
		$sql 	= " SELECT * FROM dp_countries_standard WHERE is_delete = 0 order by countryNameEn ";
		
		return $this->sql($sql)->limit("*")->key('id')->select();
	}
}
?>