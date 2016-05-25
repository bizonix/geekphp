<?php
/*
 *采购系统相关接口操作类(model)
 *@add by : linzhengxiang ,date : 20140528
 */
defined('WEB_PATH') ? '' : exit;
class InterfacePurchaseModel extends InterfaceModel{
	
	public function __construct(){
		parent::__construct();
	}
	
	public function getAdjustransportFromPurchase($get=1){
		require_once WEB_PATH."api/include/functions.php";
		
		$url	= 'http://gw.open.valsun.cn:88/router/rest?';
		$paramArr= array(
			/* API系统级输入参数 Start */
			'method'	=> 'purchase.getAdjustransport',  //API名称
			'format'	=> 'json',  //返回格式
			'v'			=> '1.0',   //API版本号
			'username'	=> 'purchase',
			/* API系统级参数 End */				 

			/* API应用级输入参数 Start*/
			'get'		=> $get
			/* API应用级输入参数 End*/
		);
		$result = callOpenSystem($paramArr);
		//var_dump($result);
		$data = json_decode($result,true);
		/*var_dump($data);
		if(!isset($data['data'])){
			return array();
		}*/
		$__liquid_items_array = array();
		foreach($data as $dataValue){
			$__liquid_items_array[$dataValue['category']] = $dataValue['skulist'];
		}
		/*foreach($data['data'] as $dataValue){
			$__liquid_items_array[$dataValue['category']] = $dataValue['skulist'];
		}*/
		return $__liquid_items_array;
	}
	
	/**
	 * 根据SKU获取对应的已订购数量
	 * @param string $sku
	 * @return int 已购数量
	 * @author lzx
	 */
	public function getReserveCount($sku){
		return rand(0, 100);
	}
	
	/**
	 * 根据SKU和数量检测是否满足超大订单的条件
	 * @param string $sku
	 * @return int 已购数量
	 * @author lzx
	 */
	public function check_sku($skuDetail, $amount = 1){
		$sku = $skuDetail['sku'];
		$purchaseId = $skuDetail['purchaseId'];
		//var_dump($sku,$goodsinfo);
		if (empty($skuDetail)||empty($purchaseId)){
			//echo "\n该料号{$sku}没有添加采购人员!\n";
			return true;
		}
		
		//$goodsCountInfo = OldsystemModel::qccenterGetErpGoodscount($sku);
		$goodsCountInfo = M("InterfaceWh")->getSkuStock($sku);
		if (empty($goodsCountInfo['data'])){
			//echo "\n该料号{$sku}没有库存信息!\n";
			return true;
		}
		
		/*$sql = "SELECT * FROM om_sku_daily_status WHERE sku='{$sku}'";
		$sql = self::$dbConn->query($sql);
		$sku_info = self::$dbConn->fetch_array($sql);
		
		if(empty($sku_info)){
			echo "\n该料号{$sku}没有统计信息!\n";
			return true;
		}*/
		
		$goods_count = $goodsCountInfo['data'];
		
		$everyday_sale = $sku_info['AverageDailyCount'];
		$takenum = ceil($everyday_sale*10);
		
		$actuallaygoods = $goods_count;
		if($actuallaygoods == 0){
			$goods_bili = 0;
		}else{
			$goods_bili = $num / $actuallaygoods;
		}
		if ($num>9 && $num>$takenum){
			//echo "\n该料号{$sku}超出10天的销售量,数量{$num},实际库存{$goods_count},每天销售量{$everyday_sale}!\n";
			return false;
		}else if($goods_bili>0.5 && $num>$takenum && $actuallaygoods > 0 && $takenum > 0){
			//echo "\n该料号{$sku}超出10天的销售量,且发货数量大于库存数量一半,数量{$num},实际库存{$goods_count},每天销售量{$everyday_sale}!\n";
			return false;
		}else{
			//echo "\n通过料号检测,数量{$num},实际库存{$goods_count}!\n";
			return true;
		}
	}
	
}
?>