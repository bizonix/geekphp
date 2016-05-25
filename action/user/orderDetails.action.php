<?php
/**
 * 类名：OrderDetailsAct
 * 功能：订单详情管理
 * 版本：v1.0
 * 作者：zjr
 * 时间：2015/01/14
 * errCode：
 */
class OrderDetailsAct extends CheckAct {
	public function __construct(){
		parent::__construct();
	}
	/*
	 * 获取列表数据
	 */
	public function act_getSmtOrderDtail($orderId){
		$companyId = get_usercompanyid();
		// M("OrderDetails")->setDbPrefix(get_userid());
		$mainData	= M("Order")->getData("*","id='{$orderId}' and company_id = '{$companyId}'");
		if(empty($mainData)){
			self::$errMsg['10010']	= get_promptmsg('10010');
			return false;
		}
		// M("Company")->updateData('1',array('cn_name'=>'环环网络科技','short_name'=>'环环网','legal_person'=>'huanhuan','address'=>'深圳市华南城1号交易广场6楼'));
		$companys	= M("Company")->getAllData('*','1','id');
		M("OrderDetails")->setTablePrefix('_'.date('Y_m',$mainData[0]["create_time"]));
		$orderDtail = M("OrderDetails")->getData("*","id={$orderId}");
		// print_r($orderDtail);exit;
	    return array("mainData" => $mainData[0],"detailData" => json_decode($orderDtail[0]['source_details'],true),"companys"=>$companys);
	}

	/*
	 * 获取列表数据
	 */
	public function act_getOrderDtail($orderId){
		$companyId = get_usercompanyid();
		// M("OrderDetails")->setDbPrefix(get_userid());
		$mainData	= M("Order")->getData("*","id='{$orderId}' and company_id = '{$companyId}'");
		if(empty($mainData)){
			self::$errMsg['10010']	= get_promptmsg('10010');
			return false;
		}
		// M("Company")->updateData('1',array('cn_name'=>'环环网络科技','short_name'=>'环环网','legal_person'=>'huanhuan','address'=>'深圳市华南城1号交易广场6楼'));
		$companys	= M("Company")->getAllData('*','1','id');
		$carriers	= M("Carrier")->getAllData('id,carrier_name_cn,carrier_abb','1','carrier_abb','order by id asc');
		$platformCarriers	= M("PlatformCarrier")->getAllData('id,serviceName,displayName',"platformId =".$mainData[0]['source_platform'],'serviceName');
		M("OrderDetails")->setTablePrefix('_'.date('Y_m',$mainData[0]["create_time"]));
		$orderDtail = M("OrderDetails")->getData("*","id={$orderId}");
		// print_r($orderDtail);exit;
	    return array("mainData" => $mainData[0],"detailData" => $orderDtail[0],"companys"=>$companys,'carriers'=>$carriers,'platformCarriers'=>$platformCarriers);
	}

	/*
	 * 获取列表数据
	 */
	public function act_updateSmtOrderDetail($orderSysId,$info){
		$mainData	= M("Order")->getData("*","id='{$orderSysId}'");
		if(empty($mainData)){
			self::$errMsg['10007']	= get_promptmsg("10007","订单");
			return false;
		}
		M(Order)->begin();
		//修改订单去向公司
		M("OrderDetails")->setTablePrefix('_'.date('Y_m',$mainData[0]["create_time"]));
		$retData	= M("Order")->updateData($orderSysId,array("delivery_from"=>$info['receiveCompany'],"transport_type"=>$info['transportType']));
		if(empty($retData)){
			self::$errMsg['10001']	= get_promptmsg("10001","更新");
			M(Order)->rollback();
			return false;
		}
		//获取订单详情信息
		$detailData	= M("OrderDetails")->getData('*',array("id"=>$orderSysId));
		if(empty($detailData)){
			self::$errMsg['10007']	= get_promptmsg('10007','订单');
			M(Order)->rollback();
			return false;
		}
		$detailData	= json_decode($detailData[0]['source_details'],true);
		//更新子订单的skuCode
		$childOrderList = $detailData['detail']['childOrderList'];
		for($i=0;$i<count($childOrderList);$i++) {
			$productId = (string)$childOrderList[$i]['productId'];
			log::write("skuCodes = ".$info['skuCodes'][$productId]);

			if(count($info['skuCodes'][$productId]) > 0){
				foreach ($info['skuCodes'][$productId] as $kkk => $vvv) {
					// if($i>0 && $kkk == 0) $i++;
					$detailData['detail']['childOrderList'][$i]['skuCode']	= $info['skuCodes'][$productId][$kkk];
					$detailData['v']['productList'][$i]['skuCode']			= $info['skuCodes'][$productId][$kkk];
					//订单的运输方式
					$detailData['v']['productList'][$i]['logisticsServiceName'] = $info['transportType'];
					//订单产品留言
					$detailData['v']['productList'][$i]['memo']			= $info['msgLists'][$productId][$kkk];
					$i++;
				}
				$i--;
			}
		}
		//-------------
		//更新订单国家和运输方式
		$detailData['detail']['receiptAddress']['country'] 			= $info['countrySn'];
		$detailData['detail']['buyerInfo']['country'] 				= $info['countrySn'];
		$detailData['v']['productList'][0]['logisticsServiceName'] 	= $info['transportType'];
		//-------------
		$updateDetailRet = M("OrderDetails")->updateData($orderSysId,array("source_details"=>json_encode($detailData)));
		if(empty($updateDetailRet)){
			self::$errMsg['10001']	= get_promptmsg('10001','修改操作');
			M(Order)->rollback();
			return false;
		}
		//查看映射类目是否存在，存在则修改
		$successNums = 0;
		foreach ($info['skuCodes'] as $k => $v) {
			$whereData = array(
				"platform_product_id" => $k,
				"platform" => "2",
				"product_belong_company" => get_usercompanyid(),
			);
			$productMapSkuCount = M("ProductMapSku")->getDataCount($whereData);
			if($productMapSkuCount == 0){
				$insertData = array(
					"platform"					=> '2',
					"platform_product_id"		=> $k,
					"sku"						=> implode(",", $v),
					"product_belong_company"	=> get_usercompanyid(),
					"sku_belong_company"		=> $info['receiveCompany'],
					"update_time"				=> time(),
					"add_time"					=> time(),
				);
				$retData = M("ProductMapSku")->insertData($insertData);
				if(!empty($retData)){
					$successNums++;
				}
			}else{
				$updateData = array(
					"sku"					=> implode(",", $v),
					"sku_belong_company"	=> $info['receiveCompany'],
					"update_time"			=> time(),
				);
				$retData = M("ProductMapSku")->updateDataWhere($updateData,$whereData);
				if(!empty($retData)){
					$successNums++;
				}
			}
		}
		if($successNums == count($info['skuCodes'])){
			self::$errMsg['200']	= get_promptmsg('200');
			M(Order)->commit();
			return true;
		}else{
			self::$errMsg['10001']	= get_promptmsg('10001','操作sku商品映射表');
			M(Order)->rollback();
			return false;
		}
	}

	/*
	 * 更新订单数据
	 */
	public function act_updateOrderDetail($orderSysId,$info){
		$mainData	= M("Order")->getData("*","id='{$orderSysId}'");
		if(empty($mainData)){
			self::$errMsg['10007']	= get_promptmsg("10007","订单");
			return false;
		}
		M(Order)->begin();
		//修改订单去向公司
		$retData	= M("Order")->updateData($orderSysId,array("delivery_from"=>$info['receiveCompany'],"transport_type"=>$info['transportType']));
		if(empty($retData)){
			self::$errMsg['10001']	= get_promptmsg("10001","更新");
			M(Order)->rollback();
			return false;
		}
		//获取订单详情信息
		M("OrderDetails")->setTablePrefix('_'.date('Y_m',$mainData[0]["create_time"]));
		$detailData	= M("OrderDetails")->getData('receiptAddress,buyerInfo',array("id"=>$orderSysId));
		if(empty($detailData)){
			self::$errMsg['10007']	= get_promptmsg('10007','订单');
			M(Order)->rollback();
			return false;
		}
		$detailData	= $detailData[0];
		//-------------
		//更新订单国家
		$receiptAddress = json_decode($detailData['receiptAddress'],true);
		$receiptAddress['country'] 			= $info['countrySn'];
		$buyerInfo		= json_decode($detailData['buyerInfo'],true);
		$buyerInfo['country'] 				= $info['countrySn'];
		//-------------
		$updateDetailRet = M("OrderDetails")->updateData($orderSysId,array("shipping_type"=>$info['shippingType'],"receiptAddress"=>json_encode($receiptAddress),"buyerInfo"=>json_encode($buyerInfo),"childOrderList"=>json_encode($info['childOrderList'])));
		if(empty($updateDetailRet)){
			self::$errMsg['10001']	= get_promptmsg('10001','修改操作');
			M(Order)->rollback();
			return false;
		}
		M(Order)->commit();
		//计算不含运费的费用
		$info = array(
				'orderSysId'	 => $orderSysId,
				'shipping_type'	 => $info['shippingType'],
				'receiptAddress' => $receiptAddress,
				'childOrderList' => $info['childOrderList'],
				'simple_detail'	 => $mainData[0]['simple_detail']
			);
		$checkRes = $this->act_checkForecastInfo($info);
		if(!$checkRes){
			return false;
		}
		/*$statics = array();
		$itemNums	 = 0;
		foreach ($info['childOrderList'] as $k => $v) {
			$statics[trim($v['productAttributes']['sku'])] += $v['lotNum'];
			$itemNums	 += $v['lotNum'];
		}
		$skuArr = array_keys($statics);
		if(!empty($skuArr)){
			$skuStr = implode("','",$skuArr);
			$skuFees = M("Goods")->getAllData("sku,checkCost","sku IN ('{$skuStr}')","sku");
			$skuFees = array_values($skuFees);
			$oldOrderFee = json_decode($mainData[0]['simple_detail'],true);
			$skuNumPrice = array();
			$processFee  = 0;
			$goods_fee 	 = 0;
			$package_fee = 0;
			$shipping_fee= 0;
			//开始计算
			foreach ($skuFees as $k => $v) {
				$checkCost = json_decode($v['checkCost'],true);
				$skuNumPrice[$k] = array(
					'sku'		 => trim($v['sku']),
					'num'		 => $statics[$v['sku']],
					'dpPrice' 	 => $checkCost['dpPrice'],
					'packageFee' => $checkCost['pmFee'],
					'price'		 => $checkCost['price'],
					'sku_shippint_fee'	=> 0,   //后续扩展拉取展示运费
				);
				$goods_fee += $checkCost['dpPrice'] * $statics[$v['sku']];
				$package_fee += $checkCost['pmFee'] * $statics[$v['sku']];
				if($processFee === 0) {
					$processFee = $checkCost['processFee'] + 0.5 + ($statics[$v['sku']] - 1) * 0.05;
				}else{
					$processFee += $statics[$v['sku']] * 0.05;
				}
			}

			//计算后的费用整合进源数据，以便进行更新
			
			//整合详细费用信息
			foreach ($skuNumPrice as $k => $v) {
				foreach ($oldOrderFee['orderFee']['skuNumPrice'] as $kk => $vv) {
					if(trim($vv['sku']) == $v['sku']){
						$vv['sku']						= $v['sku'];
						$vv['num'] 						= $v['num'];
						$vv['dpPrice'] 					= $v['dpPrice'];
						$vv['packageFee'] 				= $v['packageFee'];
						$vv['price'] 					= $v['price'];
						//如果运费存在，则覆盖原始运费，以最新的为准
						if($v['sku_shippint_fee'] !== 0){
							$vv['sku_shippint_fee'] = $v['sku_shippint_fee'];
						}
						$skuNumPrice[$k] = $vv;
						break;
					}
				}
				$shipping_fee += $skuNumPrice[$k]['sku_shippint_fee'] * $skuNumPrice[$k]['num'];

			}
			$oldOrderFee['orderFee']['skuNumPrice']	= $skuNumPrice;
			//整合总的预估费用信息
			$oldOrderFee['item_count'] = $itemNums;
			$oldOrderFee['orderFee']['forecastInfo']['goods_fee'] 	= $goods_fee;
			$oldOrderFee['orderFee']['forecastInfo']['order_fee'] 	= $processFee;
			$oldOrderFee['orderFee']['forecastInfo']['shipping_fee'] = $shipping_fee;
			$oldOrderFee['orderFee']['forecastInfo']['package_fee'] = $package_fee;
			$oldOrderFee['orderFee']['forecastInfo']['total_fee'] 	= $goods_fee + $processFee + $shipping_fee + $package_fee;

			$retData	= M("Order")->updateData($orderSysId,array("simple_detail"=>json_encode($oldOrderFee)));
			if(empty($retData)){
				self::$errMsg['10001']	= get_promptmsg("10001","费用更新");
				return false;
			}
		}*/
		//查看映射类目是否存在，存在则修改
		/*$successNums = 0;
		foreach ($info['skuCodes'] as $k => $v) {
			$whereData = array(
				"platform_product_id" => $k,
				"platform" => $mainData[0]['source_platform'],
				"product_belong_company" => get_usercompanyid(),
			);
			$productMapSkuCount = M("ProductMapSku")->getDataCount($whereData);
			if($productMapSkuCount == 0){
				$insertData = array(
					"platform"					=> $mainData[0]['source_platform'],
					"platform_product_id"		=> $k,
					"sku"						=> implode(",", $v),
					"product_belong_company"	=> get_usercompanyid(),
					"sku_belong_company"		=> $info['receiveCompany'],
					"update_time"				=> time(),
					"add_time"					=> time(),
				);
				$retData = M("ProductMapSku")->insertData($insertData);
				if(!empty($retData)){
					$successNums++;
				}
			}else{
				$updateData = array(
					"sku"					=> implode(",", $v),
					"sku_belong_company"	=> $info['receiveCompany'],
					"update_time"			=> time(),
				);
				$retData = M("ProductMapSku")->updateDataWhere($updateData,$whereData);
				if(!empty($retData)){
					$successNums++;
				}
			}
		}
		if($successNums == count($info['skuCodes'])){
			self::$errMsg['200']	= get_promptmsg('200');
			M(Order)->commit();
			return true;
		}else{
			self::$errMsg['10001']	= get_promptmsg('10001','操作sku商品映射表');
			M(Order)->commit();
			return false;
		}*/
		self::$errMsg['200']	= get_promptmsg('200');
		return true;
	}

	/*
	 * 计算订单预估费用
	 * 参数：$info = array("childOrderList"=>"","orderSysId"=>"","simple_detail"=>"")
	 */
	public function act_checkForecastInfo($info){
		//获取spu的运费
		$shipUnitFee = A('Track')->act_getChangesFee($info['receiptAddress']['country'],$info['shipping_type']);
		if(!empty($shipUnitFee)){
			$unitPrice = $shipUnitFee['unitPrice'];
		}else{
			$unitPrice = 0;
		}
		//计算不含运费的费用
		$statics = array();
		$itemNums	 = 0;
		foreach ($info['childOrderList'] as $k => $v) {
			$statics[trim($v['productAttributes']['sku'])] += $v['lotNum'];
			$itemNums	 += $v['lotNum'];
		}
		$skuArr = array_keys($statics);
		if(!empty($skuArr)){
			$skuStr = implode("','",$skuArr);
			$skuFees = M("Goods")->getAllData("spu,sku,goodsWeight,checkCost","sku IN ('{$skuStr}')","sku");
			$skus 	 = array_keys($skuFees);
			$skuDiff = array_diff($skuArr, $skus);  //过滤出产品中心不存在的产品
			if(!empty($skuDiff)){
				self::$errMsg['10030']	= get_promptmsg("10030","(".implode($skuDiff).")");
				return false;
			}
			$skuFees = array_values($skuFees);
			$oldOrderFee = json_decode($info['simple_detail'],true);
			$skuNumPrice = array();
			$processFee  = 0;
			$goods_fee 	 = 0;
			$package_fee = 0;
			$shipping_fee= 0;
			
			//开始计算
			foreach ($skuFees as $k => $v) {
				$checkCost = json_decode($v['checkCost'],true);
				$skuNumPrice[$k] = array(
					'sku'		 => trim($v['sku']),
					'num'		 => $statics[$v['sku']],
					'dpPrice' 	 => $checkCost['dpPrice'],
					'packageFee' => $checkCost['pmFee'],
					'price'		 => $checkCost['price'],
					'sku_shippint_fee'	=> sprintf("%.2f",($v['goodsWeight']*$unitPrice)+($statics[$v['sku']]/$itemNums)*$shipUnitFee['handlefee']),   //后续扩展拉取展示运费
				);
				$goods_fee += $checkCost['dpPrice'] * $statics[$v['sku']];
				$package_fee += $checkCost['pmFee'] * $statics[$v['sku']];
				if($processFee === 0) {
					$processFee = $checkCost['processFee'] + 0.5 + ($statics[$v['sku']] - 1) * 0.05;
				}else{
					$processFee += $statics[$v['sku']] * 0.05;
				}
			}

			//计算后的费用整合进源数据，以便进行更新
			
			//整合详细费用信息
			foreach ($skuNumPrice as $k => $v) {
				foreach ($oldOrderFee['orderFee']['skuNumPrice'] as $kk => $vv) {
					if(trim($vv['sku']) == $v['sku']){
						$vv['sku']						= $v['sku'];
						$vv['num'] 						= $v['num'];
						$vv['dpPrice'] 					= $v['dpPrice'];
						$vv['packageFee'] 				= $v['packageFee'];
						$vv['price'] 					= $v['price'];
						//如果运费存在，则覆盖原始运费，以最新的为准
						if($v['sku_shippint_fee'] !== 0){
							$vv['sku_shippint_fee'] = $v['sku_shippint_fee'];
						}
						$skuNumPrice[$k] = $vv;
						break;
					}
				}
				$shipping_fee += $skuNumPrice[$k]['sku_shippint_fee'] * $skuNumPrice[$k]['num'];

			}
			$oldOrderFee['orderFee']['skuNumPrice']	= $skuNumPrice;
			//整合总的预估费用信息
			$oldOrderFee['item_count'] = $itemNums;
			$oldOrderFee['orderFee']['forecastInfo']['goods_fee'] 	= $goods_fee;
			$oldOrderFee['orderFee']['forecastInfo']['order_fee'] 	= $processFee;
			$oldOrderFee['orderFee']['forecastInfo']['shipping_fee'] = $shipping_fee;
			$oldOrderFee['orderFee']['forecastInfo']['package_fee'] = $package_fee;
			$oldOrderFee['orderFee']['forecastInfo']['total_fee'] 	= $goods_fee + $processFee + $shipping_fee + $package_fee;

			$retData	= M("Order")->updateData($info['orderSysId'],array("simple_detail"=>json_encode($oldOrderFee)));
			if(empty($retData)){
				self::$errMsg['10001']	= get_promptmsg("10001","费用更新");
				return false;
			}
		}
		return true;

	}

	/**
	 * 修改买家地址信息
	 */
	public function act_updateReceiptAddr($data){
		if(empty($data)){
			self::$errMsg['10008'] = get_promptmsg('10008','修改参数');
			return false;
		}
		if(empty($data['orderSysId']) || empty($data['receiptAddress'] || empty($data['buyerInfo']))){
			self::$errMsg['10008'] = get_promptmsg('10008','未获取到参数');
			return false;
		}
		$mainData = M("Order")->getSingleData("create_time","id={$data['orderSysId']}");
		M("OrderDetails")->setTablePrefix('_'.date('Y_m',$mainData["create_time"]));
		$updateRet = M("OrderDetails")->updateData($data['orderSysId'],array("receiptAddress" => json_encode($data['receiptAddress']),"buyerInfo" => json_encode($data['buyerInfo'])));
		if(!$updateRet){
			self::$errMsg['10001'] = get_promptmsg('10001',"修改操作");
			return false;
		}
		return true;
	}

	/**
	 * 修改报关信息
	 */
	public function act_updateDeclaration($data){
		if(empty($data)){
			self::$errMsg['10008'] = get_promptmsg('10008','修改参数');
			return false;
		}
		if(empty($data['orderSysId']) || empty($data['orderDeclarationContent'])){
			self::$errMsg['10008'] = get_promptmsg('10008','未获取到参数');
			return false;
		}
		$mainData = M("Order")->getSingleData("create_time","id={$data['orderSysId']}");
		M("OrderDetails")->setTablePrefix('_'.date('Y_m',$mainData["create_time"]));
		$updateRet = M("OrderDetails")->updateData($data['orderSysId'],array("orderDeclarationContent" => json_encode($data['orderDeclarationContent'])));
		if(!$updateRet){
			self::$errMsg['10001'] = get_promptmsg('10001',"修改操作");
			return false;
		}
		return true;
	}
}
