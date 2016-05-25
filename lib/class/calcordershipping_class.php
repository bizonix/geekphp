<?php
/*
 * 订单运输方式选择和运费计算
 * @add by : linzhengxiang ,date : 20140611
 */

class CalcOrderShipping {

	private $errMsg = array();				//装载重量计算过程中的异常信息（无重量、无包材等），异常信息需要提交到数据库统一管理
	private $orderData = array();
	
	public function __construct(){

	}
	
	/**
	 * 赋值订单变量
	 * @param array $orderData
	 * @author lzx
	 */
	public function setOrder($orderData){
		$this->orderData = $orderData;
	}
	
	/**
	 * 获取错误信息
	 * @eturn array 错误信息数据需要打到订单相关表中，记录错误编号用于订单查询
	 * @author lzx 
	 */
	public function getErrMsg(){
		return $this->errMsg;
	}
	
	/**
	 * 订单重量计算
	 * @return float $orderweight
	 * @author herman.xi 20140620
	 */
	public function calcOrderWeight() {
		if (empty($this->orderData)){
			//请先初始化订单，需要自己写到消息提示配置里面。然后复制给$this->errMsg,供前段调用
			return false;
		}
		//var_dump($this->orderData); return false;
		$orderDetailData = $this->orderData['orderDetail'];
		//var_dump($$orderDetailData); exit;
		$orderWeight = 0; //初始化要返回的订单重量变量
		$pweight = 0; //初始化包材重量
		$orderCosts = 0;
		$orderPrices = 0;
		foreach($orderDetailData as $detailValue){
			$orderDetailValue = $detailValue['orderDetail'];
			$sku = $orderDetailValue['sku'];
			//echo $sku; echo "\n";
			$amount = $orderDetailValue['amount'];
			$itemPrice = $orderDetailValue['itemPrice'];
			$skuinfo = M("InterfacePc")->getSkuInfo($sku);
			//var_dump($skuinfo); echo "\n";
			$skuinfoDetail = $skuinfo['skuInfo'];
			//组合料号
			if(count($skuinfoDetail) == 1){
				foreach($skuinfoDetail as $ssku => $skuinfoDetailValue){
					//$ssku = $skuinfoDetail[0]['sku'];
					$scount = $skuinfoDetailValue['amount'];
					$skuDetail = $skuinfoDetailValue['skuDetail'];
					//$goodsinfo = GoodsModel::getSkuInfo($ssku);//获取单料号信息
					if($skuDetail){
						$pmId = $skuDetail['pmId'];
						$goodsWeight = $skuDetail['goodsWeight'];
						$pmCapacity = $skuDetail['pmCapacity'];
						//$goodsCost = $skuDetail['goodsCost'];
					}
					$pmInfo = M("InterfacePc")->getMaterInfoById($pmId);//获取包材信息
					//var_dump($pmInfo); echo "\n";
					if($pmInfo){
						$pweight = $pmInfo['pmWeight'];
					}
					if($scount <= $pmCapacity){
						$orderWeight += $pweight + ($goodsWeight * $scount);
					}else{
						if (!empty($pmCapacity)) {
							$orderWeight += (1 + ($scount-$pmCapacity)/$pmCapacity*0.6)*$pweight + ($goodsWeight * $scount);
						} else {
							$orderWeight += $pweight + ($goodsWeight * $scount);	
						}	
					}
				}
			}else if(count($skuinfoDetail) > 1){
				foreach($skuinfoDetail as $ssku => $skuinfoDetailValue){
					//$ssku = $skuinfoDetailValue['sku'];
					$scount = $skuinfoDetailValue['amount'];
					$skuDetail = $skuinfoDetailValue['skuDetail'];
					//$goodsinfo = M("InterfacePc")->getSkuInfo($ssku);//获取单料号信息
					if($skuDetail){
						$pmId = $skuDetail['pmId'];
						$goodsWeight = $skuDetail['goodsWeight'];
						$pmCapacity = $skuDetail['pmCapacity'];
						$goodsCost = $skuDetail['goodsCost'];
					}
					$pmInfo = M("InterfacePc")->getMaterInfoById($pmId);//获取包材信息
					if($pmInfo){
						$pweight = $pmInfo['pmWeight'];
					}
					$orderWeight += ($scount/$pmCapacity)*0.6*$pweight + ($goodsWeight * $scount);
				}
			}
		}
		return array($orderWeight,$pmId);
	}
	
	/**
	 * 综合调用函数返回最后计算出来的运费和运输方式
	 */
	public function calcOrderCarrierAndShippingFee(){
		if (empty($this->orderData)){
			//请先初始化订单，需要自己写到消息提示配置里面。然后复制给$this->errMsg,供前段调用
			return false;
		}
		
		if (!$carriers = $this->calcOrderCarriers()){
			//记录错误，需要自己写到消息提示配置里面。
			return false;
		}
		
		if (!$shippingfees = $this->calcOrderShippingFee($carriers)){
			//记录错误，需要自己写到消息提示配置里面。
			return false;
		}
		return $this->chooseOrderShipping($shippingfees);
	}

	/**
	 * 运输方式匹配
	 * @return array $carriers
	 * @author lzx
	 */
	public function calcOrderCarriers() {

		#1、对应平台录入平台运输方式和对应可以走的运输方式，如果为匹配返回false，提示用户添加对应匹配关系
		
		#2、获取特殊料号运输方式对应转化，剔除掉对应被转化运输方式
		
		#3、根据平台获取对应平台suffix进行扩展运输方式检验确认.demo 如下
		$extenmthod = "calc".ucfirst($suffix)."OrderExtension";
		if(method_exists($this, $extenmethod)){
			$this->$extenmethod();
		}
		return $carriers;
	}
	
	/**
	 * 根据上面的运输方式到运输方式管理系统获取运费
	 * @param array $carriers
	 * @return array
	 * @author lzx
	 */
	public function calcOrderShippingFee($carriers){
		return $shippingfees;
	}
	
	/**
	 * 根据运输方式和价格确定最后真正走的运输方式
	 * @param array $shippingfees
	 * @return array
	 * @author lzx
	 */
	public function chooseOrderShipping($shippingfees){
		$extenmthod = "choose".ucfirst($suffix)."OrderShippingExtension";
		if(method_exists($this, $extenmethod)){
			$this->$extenmethod();
		}
		return $carriers;
	}
	
	/**
	 * 对应ebay平台特殊运输方式转换，
	 * demo：如ebay在某段时间内不能内什么运输方式，在这里可以剔除掉
	 */
	private function calcEbayOrderExtension(){
		
	}
	
	/**
	 * 对应亚马逊平台特殊运输方式转换
	 */
	private function calcAmazonOrderExtension(){
		
	}
	#####################  可以扩展多个平台运输方式选择  一定要按照平台表：suffix 递增 订单信息，明细扩展表后缀,命名规则##########################
	
	/**
	 * 对应ebay最优运输方式选择和价格差别选择
	 * demo： 如EUB的价格高于平台的10%还是会选择EUB
	 */
	private function chooseEbayOrderShippingExtension($shippingfees){
		
	}
	
	/**
	 * 对应速卖通最优运输方式选择和价格差别选择
	 */
	private function chooseAliexpressOrderShippingExtension($shippingfees){
		
	}
	#####################  可以扩展多个平台最优运输方式选择和价格差别选择  一定要按照平台表：suffix 递增 订单信息，明细扩展表后缀,命名规则##########################
}