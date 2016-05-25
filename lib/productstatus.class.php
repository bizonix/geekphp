<?php
/*
 *核心逻辑 统计 订单数据 变化sku 数据对应变化
 *
 *add by xiaojinhua
 * */
class ProductStatus{
	/*
	private	$waitingsend = array(0, 2, 615, 617, 625, 640, 642, 652, 654);
	private	$interceptsend = array(640);
	private	$shortagesend = array(642);
	private	$waitingaudit = array(652, 654);
	private $partpackage = array(123);
	 */

	private $dbcon;
	private static $_instance;
	private $orderType; //待发货 、 自动拦截 、待审核、部分包货、超大订单拦截
	public $rmqObj;
	public $exchange = 'data_analyze';
	public $statusArr;
	
	public function __construct(){
		global $dbConn;
		$this->dbcon = $dbConn;

		$rmq_config	= array(
				"ip"=> "112.124.41.121",
				"user"=>"valsun_sendOrder",
				"passwd"=>"sendOrder%123",
				"port" => "5672",
				"vhost"=>"valsun_datas"
			 );
		$this->rmqObj = new RabbitMQClass($rmq_config['user'],$rmq_config['passwd'],$rmq_config['vhost'],$rmq_config['ip']);//队列对象

		// 获取订单状态类型
		$statusObj = new StatusMenuModel();
		$orderStatusArr['waitingsend'] = $statusObj->getWaitingsend(); //一级状态
		$orderStatusArr['bigOrder'] = $statusObj->getBigOrder();
		$orderStatusArr['shortageSend'] = $statusObj->getInterceptsend();//自动拦截缺货
		$orderStatusArr['waitingAudit'] = $statusObj->getPendingAuditOrder(); //二级状态
		$orderStatusArr['largeInterceptOrder'] = $statusObj->getLargeInterceptOrder();
		$this->statusArr = $orderStatusArr;
	}

	/**
	 * 重置对应SKU状态数量
	 * @param string $sku
	 */
	public function resetSkuStatus($sku){
		//$actualStock = $this->get 
		

	}

	public function checkOrderStatus($status){
		$orderStatusArr = $this->statusArr; //各种订单状态的类型
		//$orderStatus	= "waitingAudit"; 	
		if(in_array($status,$orderStatusArr['waitingsend'])){
			$orderStatus = "waitingsend"; 
		}else if(in_array($status,$orderStatusArr['shortageSend'])){//自动拦截缺货的
			$orderStatus = "shortageSend"; 
		}else if(in_array($status,$orderStatusArr['bigOrder'])){
			$orderStatus = "bigOrder"; 
		}else if(in_array($status,$orderStatusArr['waitingAudit'])){
			$orderStatus = "waitingAudit"; 
		}else if(in_array($status,$orderStatusArr['largeInterceptOrder'])){//超大订单拦截数量
			$orderStatus = "interceptsend"; 
		}
		return $orderStatus;
	}

	// 订单id ，一级状态 二级状态
	public function updateSkuStatusByOrderStatus($orderInfoArr,$aimstatus="",$aimorderType=""){
		$dbcon = $this->dbcon;
		//$nowStatus = $this->getOrderStatus($oid); // 获取当前订单状态

		$updateFlag = array(); //更新状态成功与否标记
		foreach($orderInfoArr as $item){
			$oid = $item;
			$skuInfoArr = $this->getSkuByorder($oid); 
			$nowStatus = $skuInfoArr[0]['orderStatus']; // 获取当前订单状态
			$nowOrderType = $skuInfoArr[0]['orderType'];
			$orderBeforeStatus1 = $this->checkOrderStatus($nowStatus);// 判断移动订单移动前订单的类型 
			$orderBeforeStatus2 = $this->checkOrderStatus($nowOrderType);// 订单二级状态类型
			if(isset($orderBeforeStatus2)){//优先取二级状态
				$orderBeforeStatus = $orderBeforeStatus2;
			}else{
				$orderBeforeStatus = $orderBeforeStatus1;
			}
				
			$orderAfterStatus1 = $this->checkOrderStatus($aimstatus); // 判断移动订单移动后订单的类型
			$orderAfterStatus2 = $this->checkOrderStatus($aimorderType); // 判断移动订单移动后订单的类型
			if(isset($orderAfterStatus2)){
				$orderAfterStatus = $orderAfterStatus2;
			}else{
				$orderAfterStatus = $orderAfterStatus1;
			}
			//var_dump($nowStatus,$nowOrderType,$orderBeforeStatus,$orderAfterStatus);

			foreach($skuInfoArr as $skuItem){
				$realSkuInfo = $this->getRealSku($skuItem['sku']);
				if(count($realSkuInfo) == 0){// 不是组合料号
					if($aimstatus = ''){// 新进来的订单还没移动
						$flag = $this->checkDailyStatus($skuItem['sku'],'',$skuItem['amount'],$orderBeforeStatus);
					}else{// 移动的订单
						$flag = $this->checkDailyStatus($skuItem['sku'],'',$skuItem['amount'],$orderBeforeStatus,$orderAfterStatus);
					}
				}else{// 组合料号情况
					foreach($realSkuInfo as $cItem){
						$sku = $cItem['sku'];
						$amount = $cItem['count'] * $skuItem['amount'];
						$flag = $this->checkDailyStatus($sku,'',$amount,$orderBeforeStatus,$orderAfterStatus);
					}
				}
				$updateFlag[] = $flag;
			}
		}
		if(in_array(0,$updateFlag)){
			return false;
		}else{
			return true;
		}

	}


	/*
	 *判断sku 是否在每天统计表是否存在，存在即更新 不存在 添加
	 * */
	public function checkDailyStatus($sku,$spu,$amount,$beforeStatus,$afterStatus){
		$dbcon = $this->dbcon;
		if(empty($sku) || $sku == ""){
			return true;
		}
		$orderType = array(
						"waitingsend"=>"waitingSendCount",
						"shortageSend"=>"shortageSendCount",//自动拦截数量
						"waitingAudit" =>"waitingAuditCount", //等待审核数量	
						"bigOrder" => "todayBigOrderCount"
					);
		$sql = "select count(*) as totalnum from om_sku_daily_status where sku='{$sku}'";
		$sql = $dbcon->execute($sql);
		$rtnNum = $dbcon->fetch_one($sql);
		if(empty($amount)){
			$amount = 0;
			$beforeStatus = "waitingsend";
			$afterStatus  = "waitingsend";
		}		

		if($rtnNum['totalnum'] == 1 ){
			if(isset($afterStatus) && isset($beforeStatus) && $afterStatus != $beforeStatus){//移动的订单
				$sql = "UPDATE om_sku_daily_status set {$orderType[$beforeStatus]}={$orderType[$beforeStatus]}-{$amount},{$orderType[$afterStatus]}={$orderType[$afterStatus]}+{$amount} where sku='{$sku}'";
			}else{//新进来的订单
				$sql = "UPDATE om_sku_daily_status set {$orderType[$beforeStatus]}={$orderType[$beforeStatus]}+{$amount} where sku='{$sku}'";
				return  1; // 不进行计算
			}
		}else{
			if(empty($orderType[$beforeStatus])){
				$sql = "insert into om_sku_daily_status (sku,spu) values ('{$sku}','{$spu}')";
			}else{
				$sql = "insert into om_sku_daily_status (sku,spu,{$orderType[$beforeStatus]}) values ('{$sku}','{$spu}',$amount)";
			}
		}
		if($dbcon->execute($sql)){
			$exchange = $this->exchange;
			$this->rmqObj->queue_publish($exchange,$sql); //发布消息
			return 1;
		}else{
			return 0;
		}
	} 




	/*
	 * 通过订单id 获取订单当前所处的状态
	 * */
	public function getOrderStatus($oid){
		$dbcon = $this->dbcon;
		$sql = "select orderStatus from om_unshipped_order where id={$oid}"; 
		$sql = $dbcon->execute($sql);
		$rtnOrderStatus = $dbcon->fetch_one($sql);
		return $rtnOrderStatus['orderStatus'];
	} 

	/*
	 *通过订单id获取sku 和数量
	 *
	 * */
	public function getSkuByorder($id){ 
		$dbcon = $this->dbcon;
		$sql = "select a.orderStatus,a.orderType,b.sku,b.amount from om_unshipped_order as a left join om_unshipped_order_detail as b on a.id=b.omOrderId where a.id={$id}";
		$sql = $dbcon->execute($sql);
		$rtnArr = $dbcon->getResultArray($sql);
		return $rtnArr;
	}




	public function getRealSkuData($sku){
		//$dbcon = $this->dbcon;
		$data = array();
		$data['number1'] = WarehouseAPIModel::getSkuStock($sku,1);
		$data['number2'] = WarehouseAPIModel::getSkuStock($sku,2);
		//$number = $number1 + $number2;

		$data['waitingsend'] = $this->getWaitingSendCount($sku);
		//$availableStockCount = $number - $waitingsend; // 虚拟库存
		//计算sku是否预警
		//$data['getInterceptCount'] = $this->getInterceptSendCount($sku);
		//$data['lastDaySaleCount'] = $this->lastDaySaleCount($sku);
		//$data['lastDaySendCount'] = $this->lastDaySendCount($sku);
		//$data['lastWeekSaleCount'] = $this->lastWeekSaleCount($sku);
		$data['interceptsendCount'] = $this->getInterceptSendCount($sku); // 超大订单拦截数量
		$data['shortagesendCount'] = $this->getShortageSendCount($sku); //自动拦截数量
		$data['waitingauditCount'] = $this->getWaitingAuditCount($sku); //超大订单待审核数量
		//$data['averageDailyCount'] = $this->getAverageDailyCount($sku);
		$data['averageDailyCount'] = $this->calcNormalDailyCount($sku);
		return $data;
	}
	/*
	 * 重置SKU 库存实际数据
	 * 
	 * */
	public function resetSkuStock($sku,$storeid=1){
		$dbcon = $this->dbcon;
		$number1 = WarehouseAPIModel::getSkuStock($sku,1);
		$number2 = WarehouseAPIModel::getSkuStock($sku,2);
		$number = $number1 + $number2;
		$waitingsend = $this->getWaitingSendCount($sku);
		$availableStockCount = $number - $waitingsend; // 虚拟库存
		//计算sku是否预警
		$skuInfo = $this->getSkuDaysInfo($sku);
		$goodsdays = $skuInfo["goodsdays"];// 预警天数
		$averageDailyCount = $this->getAverageDailyCount($sku);
		if($averageDailyCount != 0){
			$canUseDay = floor($availableStockCount / $averageDailyCount);
			if($canUseDay <= $goodsdays){
				$is_warning = 1;
			}else{
				$is_warning = 0;
			}
		}else{
			$is_warning = 0;
		}
		$bigOrder = $this->getInterceptSendCount($sku);
		$shortagesendCount = $this->getShortageSendCount($sku); //自动拦截数量
		$waitingauditCount = $this->getWaitingAuditCount($sku); //超大订单待审核数量
		$lastDaySaleCount = $this->lastDaySaleCount($sku);
		$lastDaySendCount = $this->lastDaySendCount($sku);
		$lastWeekSaleCount = $this->lastWeekSaleCount($sku);
		$lastWeekSendCount = $this->lastWeekSendCount($sku);
		$lastMouthSaleCount = $this->lastMouthSaleCount($sku);
		$lastMounthSendCount = $this->lastMouthSendCount($sku);
		$sql = "select count(*) as number from om_sku_daily_status where sku='{$sku}'";
		$sql = $dbcon->execute($sql);
		$dataInfo = $dbcon->fetch_one($sql); 
		if($dataInfo['number'] > 0){
			$sql = "update om_sku_daily_status set 
				averageDailyCount={$averageDailyCount},
				actualStockCount={$number},
				waitingSendCount={$waitingsend},
				availableStockCount={$availableStockCount},
				lastDaySaleCount={$lastDaySaleCount},
				lastDaySendCount={$lastDaySendCount},
				lastWeekSaleCount={$lastWeekSendCount},
				lastWeekSendCount={$lastWeekSendCount},
				lastMouthSaleCount={$lastMouthSaleCount},
				lastMouthSendCount={$lastMounthSendCount},
				shortageSendCount={$shortagesendCount},
				waitingAuditCount={$waitingauditCount},
				interceptSendCount={$bigOrder},
				is_warning={$is_warning}
				WHERE sku='{$sku}'";
		}else{
			$sql = "insert into om_sku_daily_status set 
				averageDailyCount={$averageDailyCount},
				actualStockCount={$number},
				waitingSendCount={$waitingsend},
				availableStockCount={$availableStockCount},
				lastDaySaleCount={$lastDaySaleCount},
				lastDaySendCount={$lastDaySendCount},
				lastWeekSaleCount={$lastWeekSendCount},
				lastWeekSendCount={$lastWeekSendCount},
				lastMouthSaleCount={$lastMouthSaleCount},
				lastMouthSendCount={$lastMounthSendCount},
				is_warning={$is_warning},
				shortageSendCount={$shortagesendCount},
				waitingAuditCount={$waitingauditCount},
				interceptSendCount={$bigOrder},
				sku='{$sku}'";
		}
		if($dbcon->execute($sql)){
			$exchange = $this->exchange;
		//	$this->rmqObj->queue_publish($exchange,$sql); //发布消息
			echo "{$sql} 更新成功。。。\n";
		}else{
			echo $sql;
		}
	}


	//定时只更新均量 不进行库存统计更新
	public function resetSkuAverage($sku){
		$dbcon = $this->dbcon;
		$sql = "SELECT availableStockCount FROM  om_sku_daily_status where sku='{$sku}' ";
		$sql = $dbcon->execute($sql);
		$number = $dbcon->fetch_one($sql);
		$availableStockCount = $number['availableStockCount']; //可用库存
		$averageDailyCount = $this->calcAverageDailyCount($sku);	
		$skuInfo = $this->getSkuDaysInfo($sku);
		$goodsdays = $skuInfo["goodsdays"];// 预警天数
		if($averageDailyCount != 0){
			$canUseDay = floor($availableStockCount / $averageDailyCount);
			if($canUseDay <= $goodsdays){
				$is_warning = 1;
			}else{
				$is_warning = 0;
			}
		}
		if(!isset($is_warning)){
			$is_warning = 0;
		}

		$sql = "UPDATE `om_sku_daily_status` SET averageDailyCount={$averageDailyCount} ,is_warning={$is_warning} where sku='{$sku}'";

		if($dbcon->execute($sql)){
			$exchange = $this->exchange;
			//$this->rmqObj->queue_publish($exchange,$sql); //发布消息
			echo "{$sql} 更新成功。。。\n";
		}else{
			echo $sql;
		}


	}


	// 获取sku 采购天数 和预警天数 和缺货率 
	public function getSkuDaysInfo($sku){
		$dbcon = $this->dbcon;
		$sql = "select * FROM  `ph_goods_calc` where sku='{$sku}' limit 1";
		$sql = $dbcon->execute($sql);
		$skuInfo = $dbcon->fetch_one($sql);
		return $skuInfo;
	}

	// 获取sku 当前的均量
	public function getAverageDailyCount($sku){
		$dbcon = $this->dbcon;
		$sql = "select averageDailyCount from om_sku_daily_status where sku='{$sku}'"; 
		//echo $sql."\n";
		$sql = $dbcon->execute($sql);
		$data = $dbcon->fetch_one($sql);
		$averageDailyCount = $data["averageDailyCount"];
		if(empty($averageDailyCount)){
			$averageDailyCount = 0;
		}
		return $averageDailyCount;
	}
	
	
	/**
	 * 设置SKU各状态数量改变
	 * @param string $skus
	 */
    public function updateSkuStatus($sku){

		$dbcon = $this->dbcon;
		$sql = "UPDATE om_sku_daily_status SET {$skus['sql']} WHERE {$skus['where']}";
		return $this->dbcon->query($sql);

	}
	
	/**
	 * 计算SKU每日均量
	 * @param string $sku
	 */
	public function calcAverageDailyCount($sku){
		$dbcon = $this->dbcon;
		$skuInfo = $this->checkSkustatus($sku); 
		$start_time = $skuInfo["start_time"];
		$now = time();
		if($skuInfo["status"] == 1 || empty($skuInfo["status"])){// 在线
			/*
			if(($now - $start_time) > 30*60){ // 正常逻辑计算均量
				$dayilyNum = $this->calcNormalDailyCount($sku);
			}else{ // 
				$dayilyNum = $this->calcStopskuDailyCount($sku);
			}
			 */
			$dayilyNum = $this->calcNormalDailyCount($sku);
		}else{
			$sql = "select averageDailyCount from om_sku_daily_status where sku='{$sku}'";
			$sql = $dbcon->execute($sql);
			$data = $dbcon->fetch_one($sql);
			$dayilyNum = $data["averageDailyCount"];
		} 
		return $dayilyNum;
	}

	/*
	 *正常均量计算
	 *
	 * */
	public function calcNormalDailyCount($sku){
		$totalNum7 = $this->getPastDayCount($sku,7); 
		$totalNum15 = $this->getPastDayCount($sku,15);
		$totalNum30 = $this->getPastDayCount($sku,30);
		$dayilyNum = $totalNum7 / 7 * 0.7 + ($totalNum15 - $totalNum7) / 8* 0.2 + ($totalNum30 -$totalNum15) / 15 * 0.1;
		$dayilyNum = round($dayilyNum,2); //取两位小数
		return $dayilyNum;
	}

	/*
	 * 暂时停售sku均量计算
	 * */

	public function calcStopskuDailyCount($sku){
		$dbcon = $this->dbcon;
		$sql = "select averageDailyCount from om_sku_daily_status where sku='{$sku}'";
		$sql = $dbcon->execute($sql);
		$data = $dbcon->fetch_one($sql);
		$totayNum = $this->getPastDayCount($sku,1);
		$DailyCount = ($data["averageDailyCount"] + $totayNum) / 2;
		return $DailyCount;
	}

	/*
	 *检查sku 现在的状态
	 * */
	public function checkSkustatus($sku){
		$dbcon = $this->dbcon;
		$sql = "select start_time,status from ph_sku_status_change where sku='{$sku}' limit 1";
		$sql = $dbcon->execute($sql);
		$skuInfo = $dbcon->fetch_one($sql);
		return $skuInfo;
	}

	//获取待发货数量
	public function getWaitingSendCount($sku){
		$dbcon = $this->dbcon;
		$skuNumArr = $this->getSkucombine($sku); 
		$skuArr = array_keys($skuNumArr); // 返回数组的所有键值
		$skuStr = implode("','",$skuArr);

		$sql = "select a.orderStatus ,b.sku,b.amount from om_unshipped_order as a left join om_unshipped_order_detail as b on a.id=b.omOrderId where a.orderStatus in (100,550,1000) and b.sku in ('{$skuStr}') ";
		//echo $sql;
		$sql = $dbcon->execute($sql);
		$rtnArr = $dbcon->getResultArray($sql);
		$totalNum = 0;
		foreach($rtnArr as $item){
			$totalNum += $item["amount"] * $skuNumArr[$item["sku"]];
		}

		$sql = "select a.orderStatus ,b.sku,b.amount from om_unshipped_order as a left join om_unshipped_order_detail as b on a.id=b.omOrderId where 
				a.orderStatus=800 and a.orderType in(801,802,140,725,804,805)
				 and b.sku in ('{$skuStr}') ";
		$sql = $dbcon->execute($sql);
		$rtnArr = $dbcon->getResultArray($sql);
		foreach($rtnArr as $item){
			$totalNum += $item["amount"] * $skuNumArr[$item["sku"]];
		}

		$sql = "select a.orderStatus ,b.sku,b.amount from om_unshipped_order as a left join om_unshipped_order_detail as b on a.id=b.omOrderId where 
				a.orderStatus=900 and a.orderType in(990,901,902,903,206)
				 and b.sku in ('{$skuStr}') ";
		$sql = $dbcon->execute($sql);
		$rtnArr = $dbcon->getResultArray($sql);
		foreach($rtnArr as $item){
			$totalNum += $item["amount"] * $skuNumArr[$item["sku"]];
		}

		$sql = "select a.orderStatus ,b.sku,b.amount from om_unshipped_order as a left join om_unshipped_order_detail as b on a.id=b.omOrderId where 
				a.orderStatus=200 and a.orderType=204 
				 and b.sku in ('{$skuStr}') ";
		$sql = $dbcon->execute($sql);
		$rtnArr = $dbcon->getResultArray($sql);
		foreach($rtnArr as $item){
			$totalNum += $item["amount"] * $skuNumArr[$item["sku"]];
		}
		return $totalNum;
	}

	//获取超大订单拦截数量
	public function getInterceptSendCount($sku){
		$dbcon = $this->dbcon;
		$skuNumArr = $this->getSkucombine($sku); 
		$skuArr = array_keys($skuNumArr); // 返回数组的所有键值
		$skuStr = implode("','",$skuArr);

		//$sql = "select a.orderStatus ,b.sku,b.amount from om_unshipped_order as a left join om_unshipped_order_detail as b on a.id=b.omOrderId where a.orderStatus=200 and a.orderType in (201,202,700,203) and b.sku in ('{$skuStr}') ";
		$sql = "select a.orderStatus ,b.sku,b.amount from om_unshipped_order as a left join om_unshipped_order_detail as b on a.id=b.omOrderId where a.orderStatus=200 and a.orderType in (205) and b.sku in ('{$skuStr}') ";
		$sql = $dbcon->execute($sql);
		$rtnArr = $dbcon->getResultArray($sql);
		$totalNum = 0;
		foreach($rtnArr as $item){
			$totalNum += $item["amount"] * $skuNumArr[$item["sku"]];
		}

		return $totalNum;
	}

	//获取自动拦截数量
	public function getShortageSendCount($sku){
		$dbcon = $this->dbcon;
		$skuNumArr = $this->getSkucombine($sku); 
		$skuArr = array_keys($skuNumArr); // 返回数组的所有键值
		$skuStr = implode("','",$skuArr);

		$sql = "select a.orderStatus ,b.sku,b.amount from om_unshipped_order as a left join om_unshipped_order_detail as b on a.id=b.omOrderId where a.orderStatus=300  and b.sku in ('{$skuStr}') ";
		//echo $sql;
		$sql = $dbcon->execute($sql);
		$rtnArr = $dbcon->getResultArray($sql);
		$totalNum = 0;
		foreach($rtnArr as $item){
			$totalNum += $item["amount"] * $skuNumArr[$item["sku"]];
		}

		return $totalNum;
	}

	//获取超大订单待审核数量 二级状态下 的sku 数量
	public function getWaitingAuditCount(){
		$orderStatusArr = $this->statusArr; // 获取所有订单的状态
		$bigOrder = $orderStatusArr['bigOrder'];
		$statusStr = implode(",",$bigOrder);
		$secondStatus = $orderStatusArr["waitingAudit"];
		$secondStatusStr = implode(",",$secondStatus);
		$skuNum = $this->getStatusSkuNum($sku,$statusStr,$secondStatusStr);
		return $skuNum;
	}
	
	//部分包货订单
	public function getPartPackageOrder($oid){
		$statusObj = new StatusMenuModel();
		$waitingsendStatus = $statusObj->getWaitingsend();
		$statusStr = implode(",",$waitingsendStatus);
		$skuNum = $this->getStatusSkuNum($sku,$statusStr);
		return $skuNum;
	}

	//部分审核通过订单
	public function getPartAuditOrder($oid){
	}

	//收集每日SKU状态
	public function collectSkustatus(){
	}


	/*
	 *获取某一类型状态下sku 的数量
	 * */
	public function getStatusSkuNum($sku,$statusStr,$secondStatusStr=null){
		$dbcon = $this->dbcon;
		$skuNumArr = $this->getSkucombine($sku); 
		$skuArr = array_keys($skuNumArr); // 返回数组的所有键值
		$skuStr = implode("','",$skuArr);
		if(isset($secondStatusStr)){
			$controlStr = "a.orderStatus in ({$statusStr}) and a.orderType in ($secondStatusStr)";
		}else{
			$controlStr = "a.orderStatus in ({$statusStr})";
		}
		$sql = "select a.orderStatus ,b.sku,b.amount from om_unshipped_order as a left join om_unshipped_order_detail as b on a.id=b.omOrderId where {$controlStr} and b.sku in ('{$skuStr}') ";
		$sql = $dbcon->execute($sql);
		$rtnArr = $dbcon->getResultArray($sql);
		$totalNum = 0;
		foreach($rtnArr as $item){
			$totalNum += $item["amount"] * $skuNumArr[$item["sku"]];
		}

		$sql = "select a.orderStatus ,b.sku,b.amount from om_shipped_order as a left join om_shipped_order_detail as b on a.id=b.omOrderId where {$controlStr} and b.sku in ('{$skuStr}') ";
		$sql = $dbcon->execute($sql);
		$rtnArr = $dbcon->getResultArray($sql);
		foreach($rtnArr as $item){
			$totalNum += $item["amount"] * $skuNumArr[$item["sku"]];
		}
		return $totalNum;
	}

	/*
	 *sku 关系转换 得到所有组合情况的sku
	 * */

	public function getSkucombine($sku){
		$dbcon = $this->dbcon;
		$sql = "select 	combineSku,count from pc_sku_combine_relation where sku='{$sku}'";
		$sql = $dbcon->execute($sql);
		$skuInfo = $dbcon->getResultArray($sql);
		$data = array();
		$data[$sku] = 1;
		foreach($skuInfo as $item){
			$data[$item['combineSku']] = $item["count"];
		}
		return $data;
	}

	// 通过组合料号得到 真实sku 
	public function getRealSku($combineSku){
		$dbcon = $this->dbcon;
		$sql = "select sku,count from pc_sku_combine_relation where combineSku='{$combineSku}'";
		$sql = $dbcon->execute($sql);
		$skuInfo = $dbcon->getResultArray($sql);
		return $skuInfo;
	}



	/**
	 * 获取过去N天进入系统正常订单数量和
	 * @param string $sku
	 * @param int $d
	 */
	public function getPastDayCount($sku, $d){
		$dbcon = $this->dbcon;
		$now = time();
		$beforeDtime = $now - $d*24*60*60;
		$skuNumArr = $this->getSkucombine($sku); 
		$skuArr = array_keys($skuNumArr); // 返回数组的所有键值
		//$skuStr = implode("','",$skuArr);
		$totalNum = 0;
		foreach($skuNumArr as $sku=>$realtimes){
			$maxnums = $esale>=5 ? ceil(10*$esale/$realtimes) : 50; // 剔除超大订单
			$sql = "select sum(a.amount) as qty from om_unshipped_order_detail as a left join om_unshipped_order as b  on b.id=a.omOrderId 
					where a.sku='{$sku}'
					and b.ordersTime > {$beforeDtime}
					and a.amount < {$maxnums}
					limit 1
					";
			//echo $sql."\n";
			$sql = $dbcon->execute($sql);
			$rtn = $dbcon->fetch_one($sql);
			$totalNum += $rtn['qty'] * $realtimes;

			$sql = "select sum(a.amount) as qty from om_shipped_order_detail as a left join om_shipped_order as b  on b.id=a.omOrderId 
					where a.sku='{$sku}'
					and b.ordersTime > {$beforeDtime}
					and a.amount < {$maxnums}
					limit 1
					";
			//echo $sql."\n";
			$sql = $dbcon->execute($sql);
			$rtn = $dbcon->fetch_one($sql);
			$totalNum += $rtn['qty'] * $realtimes;
		}
		//echo "{$d} 天的正常销量{$totalNum}\n";
		return $totalNum;
	}


	public function lastDaySaleCount($sku){
		$lastDay = time() - 24*60*60;
		$dateStr = date("Y-m-d",$lastDay);
		$begin = strtotime($dateStr."00:00:00");
		$end = strtotime($dateStr."23:59:59");
		$totalNum = $this->getPeriodSkuAmount($sku,$begin,$end);
		return $totalNum;
	}

	public function lastDaySendCount($sku){
		$lastDay = time() - 24*60*60;
		$dateStr = date("Y-m-d",$lastDay);
		$begin = strtotime($dateStr."00:00:00");
		$end = strtotime($dateStr."23:59:59");
		$totalNum = $this->getPeriodSkuSendAmount($sku,$begin,$end);
		return $totalNum;
	}

	public function lastWeekSaleCount($sku){
		$now = time();
		$lastWeek = $now - 7*24*60*60;
		$dateStr = date("Y-m-d",$lastWeek);
		$begin = strtotime($dateStr."00:00:00");
		$end = $now;
		$totalNum = $this->getPeriodSkuAmount($sku,$begin,$end);
		return $totalNum;
	}

	public function lastWeekSendCount($sku){
		$now = time();
		$lastWeek = $now - 7*24*60*60;
		$dateStr = date("Y-m-d",$lastWeek);
		$begin = strtotime($dateStr."00:00:00");
		$end = $now;
		$totalNum = $this->getPeriodSkuSendAmount($sku,$begin,$end);
		return $totalNum;
	}

	public function lastMouthSaleCount($sku){
		$now = time();
		$lastWeek = $now - 30*24*60*60;
		$dateStr = date("Y-m-d",$lastWeek);
		$begin = strtotime($dateStr."00:00:00");
		$end = $now;
		$totalNum = $this->getPeriodSkuAmount($sku,$begin,$end);
		return $totalNum;
	}

	public function lastMouthSendCount($sku){
		$now = time();
		$lastWeek = $now - 30*24*60*60;
		$dateStr = date("Y-m-d",$lastWeek);
		$begin = strtotime($dateStr."00:00:00");
		$end = $now;
		$totalNum = $this->getPeriodSkuSendAmount($sku,$begin,$end);
		return $totalNum;
	}

	public function todayNormalOrderCount($sku){
	}

	public function todayBigOrderCount($sku){
	}

	public function todayMaxOrderCount($sku){
	}

	//获取某一个时间段sku 的发货数量
	public function getPeriodSkuSendAmount($sku,$begin,$end){
		$dbcon = $this->dbcon;
		$skuNumArr = $this->getSkucombine($sku); 
		$skuArr = array_keys($skuNumArr); // 返回数组的所有键值
		$skuStr = implode("','",$skuArr);
		$totalNum = 0;
		$sql = "select a.sku,a.amount from om_shipped_order_detail as a left join om_shipped_order as b  on b.id=a.omOrderId 
			left join om_shipped_order_warehouse as c on a.id=c.omOrderId
			where a.sku in ('{$skuStr}')
			and c.weighTime > {$begin}
			and c.weighTime < {$end}
			";
		$sql = $dbcon->execute($sql);
		$rtn = $dbcon->getResultArray($sql);
		foreach($rtn as $item){
			$totalNum += $item["amount"] * $skuNumArr[$item["sku"]];;
		}
		return $totalNum;
	}

	// 获取某一个时间段sku 的数量
	public function getPeriodSkuAmount($sku,$begin,$end){
		$dbcon = $this->dbcon;
		$skuNumArr = $this->getSkucombine($sku); 
		$skuArr = array_keys($skuNumArr); // 返回数组的所有键值
		$skuStr = implode("','",$skuArr);
		$totalNum = 0;
		$sql = "select a.sku,a.amount from om_unshipped_order_detail as a left join om_unshipped_order as b  on b.id=a.omOrderId 
			where a.sku in ('{$skuStr}')
			and b.ordersTime > {$begin}
			and b.ordersTime < {$end}
			";
		$sql = $dbcon->execute($sql);
		$rtn = $dbcon->getResultArray($sql);
		foreach($rtn as $item){
			$totalNum += $item["amount"] * $skuNumArr[$item["sku"]];;
		}


		$sql = "select a.sku,a.amount from om_shipped_order_detail as a left join om_shipped_order as b  on b.id=a.omOrderId 
			where a.sku in ('{$skuStr}')
			and b.ordersTime > {$begin}
			and b.ordersTime < {$end}
			";
		$sql = $dbcon->execute($sql);
		$rtn = $dbcon->getResultArray($sql);
		foreach($rtn as $item){
			$totalNum += $item["amount"] * $skuNumArr[$item["sku"]];;
		}
		return $totalNum;
	}


	public function getSkuFirstSale($sku){
		$dbcon = $this->dbcon;
		$sql = "SELECT b.orderAddTime FROM  om_unshipped_order_detail  as a left join om_unshipped_order as b on a.id=b.omOrderId where a.sku='{$sku}' order by b.orderAddTime asc limit 1";
		$sql = $dbcon->execute($sql);
		$time = $dbcon->fetch_one($sql);
		return $time['orderAddTime'];
	}

	public function getSkuLastSale($sku){
		$dbcon = $this->dbcon;
		$sql = "SELECT b.orderAddTime FROM  om_unshipped_order_detail  as a left join om_unshipped_order as b on a.id=b.omOrderId where a.sku='{$sku}' order by b.orderAddTime desc limit 1";
		$sql = $dbcon->execute($sql);
		$time = $dbcon->fetch_one($sql);
		return $time['orderAddTime'];
	}
}
 
?>
