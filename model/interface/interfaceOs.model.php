<?php
/*
 *海外仓相关接口操作类(model)
 *@add by : linzhengxiang ,date : 20140611
 */
defined('WEB_PATH') ? '' : exit;
class InterfaceOsModel extends InterfaceModel {
	
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 跟进sku获取海外仓对应sku的可用库存
	 * @param string $sku
	 * @return int
	 * @author lzx
	 */
	public function getSkuAvailableStock($sku){
		return $stock;
	}
}
?>