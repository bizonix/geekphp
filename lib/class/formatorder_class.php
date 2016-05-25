<?php
/*
 * 订单添加相关格式化
 * @add by : linzhengxiang ,date : 20140611
 */

class FormatOrder{
	
	private $orderbelong = 0;
	private $errMsg = array();				//装载拦截过程中的异常信息，异常信息需要提交到数据库统一管理
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
	 * 订单拦截总流程
	 */
	public function interceptOrder(){
		if (empty($this->orderData)){
			//请先初始化订单，需要自己写到消息提示配置里面。然后复制给$this->errMsg,供前段调用
			return false;
		}
		//可以根据这个获取orderstatus所以只需要这一个就可以了
		$ordertype = 0;
		if ($ordertype = $this->interceptIllegalOrder()){
			$this->orderData['order']['orderType'] = $ordertype;
			$this->orderData['order']['orderStatus'] = M('StatusMenu')->getStatusMenuList($ordertype);
			return $this->orderData;
		}
		if ($ordertype = $this->interceptMissInfoOrder()){
			$this->orderData['order']['orderType'] = $ordertype;
			$this->orderData['order']['orderStatus'] = M('StatusMenu')->getStatusMenuList($ordertype);
			return $this->orderData;
		}
		//为后面的超大订单拦截和缺货拦截使用
		$this->orderbelong = $this->MarkOverSeaOrder();
		if ($ordertype = $this->interceptLargeOrder()){
			$this->orderData['order']['orderType'] = $ordertype;
			$this->orderData['order']['orderStatus'] = M('StatusMenu')->getStatusMenuList($ordertype);
			return $this->orderData;
		}
		if ($ordertype = $this->interceptOutOfStockOrder()){
			$this->orderData['order']['orderType'] = $ordertype;
			$this->orderData['order']['orderStatus'] = M('StatusMenu')->getStatusMenuList($ordertype);
			return $this->orderData;
		}
		#####  其他需要拦截的以此类推    ######
		//通过所有拦截为正常待处理订单
		return $this->orderData;
	}
	
	/**
	 * 非订单拦截，只拦截黑名单和非法邮箱， 待开发对接paypal验证订单付款
	 * @eturn array 返回ordertype
	 * @author lzx 
	 */
	public function interceptIllegalOrder(){
		//$orderType = $this->orderData['order']['orderType'];
		$accountId = $this->orderData['order']['accountId'];
		$PayPalEmailAddress = $this->orderData['orderExtensionAliexpress']['PayPalEmailAddress'];
		if(!empty($PayPalEmailAddress) && !in_array(strtolower($PayPalEmailAddress),A('PaypalEmail')->act_getPaypalEmailByAccountId($accountId))){
			return $ordertype = C('STATEPENDING_EXCPAY'); //付款邮箱如果不在对应邮箱中
		}
		
		$platformUsername = $this->orderData['userinfo']['platformUsername'];
		$username = $this->orderData['userinfo']['username'];
		$email = $this->orderData['userinfo']['email'];
		$street = $this->orderData['userinfo']['street'];
		$phone = $this->orderData['userinfo']['phone'];
		
		$black_list = array('platformUsername'=>$platformUsername,'username'=>$username,'usermail'=>$email,'street'=>$street,'phone'=>$phone,'account'=>$accountId);
		if(A('Blacklist')->act_isExitInBlacklist($black_list)){
			return $ordertype = C('STATEPENDING_BL'); //付款邮箱如果不在对应邮箱中
		}
		return false;
	}
	
	/**
	 * 拦截信息不全订单，包括国家格式不正确、订单料号缺失、订单金额对比订单详情金额不匹配、运输方式无法识别、料号有误、仓位不存在等
	 * @eturn array 返回ordertype
	 * @author lzx 
	 */
	public function interceptMissInfoOrder(){
		$accountId = $this->orderData['order']['accountId'];
		$transportId = $this->orderData['order']['transportId'];
		$CarrierName = M("InterfaceTran")->getCarrierNameById($transportId);
		$actualTotal = $this->orderData['order']['actualTotal'];
		$orderType = $this->orderData['order']['orderType'];
		$countryName = $this->orderData['userinfo']['countryName'];
		$orderDetail = $this->orderData['detail'];
		$trueTotal = 0;
		$is_no_location = false;
		foreach($orderDetail as $detail){
			$trueTotal += $detail['base']['itemPrice'] * $detail['base']['amount'];
			//$skuinfo = M("InterfacePc")->getSkuinfo($detail['base']['sku']);
			$skulocation = M("InterfaceWh")->getSkuPosition($detail['base']['sku']);//获取仓位数组，包含多仓位
			if(!$skulocation){
				$is_no_location = true;
			}
			//$skuinfoDetail = $skuinfo['skuInfo'][$detail['base']['sku']]['skuDetail'];
		}
		$platformUsername = $this->orderData['userinfo']['platformUsername'];
		$username = $this->orderData['userinfo']['username'];
		$email = $this->orderData['userinfo']['email'];
		$street = $this->orderData['userinfo']['street'];
		$phone = $this->orderData['userinfo']['phone'];
		
		if(in_array($email, array("", "Invalid Request")) && $CarrierName=='EUB'){//EUB运输方式匹配
			return $orderType = C('STATESYNCINTERCEPT_AB');//移动到同步异常订单中
		}else if($trueTotal != $actualTotal && $orderType == C('STATEPENDING_CONV')){
			//ebay total 和单价数量不一致问题移动异常订单
			return $orderType = C('STATESYNCINTERCEPT_AB');//移动到同步异常订单中
		}else if(empty($countryName)){
			return $orderType = C('STATESYNCINTERCEPT_AB');//移动到同步异常订单中
		}else if(empty($transportId) || !$CarrierName){
			return $orderType = C('STATESYNCINTERCEPT_AB');//移动到同步异常订单中
		}
		
		if(in_array($accountId, M('Account')->getAccountNameByPlatformId(2))){
			$status683 = false;
			if(in_array($countryName, array('Russian Federation', 'Russia')) && strpos($CarrierName, '中国邮政')!==false && str_word_count($username) < 2){
				$status683 = true;
			}
			if(in_array($countryName, array('Belarus','Brazil','Brasil','Argentina','Ukraine')) && str_word_count($username) < 2){
				$status683 = true;
			}
			if($status683){
				$orderType = C('STATESYNCINTERCEPT_AD');
			}
		}
		
		if($is_no_location){
			return $orderType = C('STATESYNCINTERCEPT_NL');//无仓位订单移动到同步异常订单 add by Herman.Xi @20131129
		}
		return false;
	}
	
	/**
	 * 标记订单是否包括海外仓料号，0为国内订单、1为包含海外料号和国内料号订单、2为美国A仓订单（先考虑手动拆分，如果业务部门OK考虑自动拆分）
	 * 预留数字3、4、5、6.....扩展到多个海外仓订单
	 * 该功能关联到后面的缺货拦截和超大订单拦截
	 */
	public function MarkOverSeaOrder(){
		$contain_os_item = false;
		//$ow_status = array();
		$allskuinfo = array();
		$orderDetail = $this->orderData['detail'];
		foreach($orderDetail as $detail){
			$sku = $detail['base']['sku'];
			$skuinfo = M("InterfacePc")->getSkuinfo($sku);
			foreach($skuinfo['skuInfo'] as $or_sku => $skuinfoDetailValue){
				$amount = $skuinfoDetailValue['amount'];
				$or_sku = $skuinfoDetailValue['skuDetail']['sku'];
				$allskuinfo[] = $or_sku;
				
				if(preg_match("/^US01\+.*/", $or_sku, $matchArr) || preg_match("/^US1\+.*/", $or_sku, $matchArr) ){
					//$log_data .= "[".date("Y-m-d H:i:s")."]\t包含海外仓料号订单---{$ebay_id}-----料号：{$or_sku}--!\n\n";
					$contain_os_item = true;
					if(strpos($or_sku,"US01+") !== false){
						$matchStr=substr($matchArr[0],5);//去除前面
						//$matchStr = str_replace("US1+", "", $or_sku);
					}else{
						//$matchStr=substr($matchArr[0],5);//去除前面
						$matchStr = str_replace("US1+", "", $or_sku);
					}
					$n=strpos($matchStr,':');//寻找位置
					if($n){$matchStr=substr($matchStr,0,$n);}//删除后面
					
					if(preg_match("/^0+(\w+)/",$matchStr,$matchArr)){
						$matchStr = $matchArr[1];
					}
					
					$sql = "update ebay_orderdetail set sku ='{$matchStr}' where ebay_id = {$orderdetail['ebay_id']} "; //add by Herman.Xi 替换海外仓料号为正确料号
					$dbcon->execute($sql);
					$virtualnum = check_oversea_stock($matchStr); //检查海外仓虚拟库存  预留接口
				    //insert_mark_shipping($ebay_id);
					/*if($virtualnum >= 0){
						$ow_status[] = 705;
					}else{
						$ow_status[] = 714; //海外仓缺货
					}*/
				}
				
				/*if(!$contain_os_item && empty($ebay_note) && $totalweight <=2){
					//如果不是海外仓的，就去检查是否为B仓的料号
					$location = get_sku_location($or_sku);
					if(strpos($location,'WH') === 0 || strpos($location,'HW') === 0){
						$contain_wh_item = true;
					}
				}*/
			}
		}
		
		if($contain_os_item){
			//$orderType = C('STATEPENDING_OVERSEA');
			//$log_data .= "[".date("Y-m-d H:i:s")."]\t更新海外仓料号订单状态为{$orderType}---{$ebay_id}--{$sql}-!\n\n";
			//if($orderType == 705){
				$totalweight = calcWeight($ebay_id);  //预留接口
				$skunums	 = checkSkuNum($ebay_id); //预留接口
				if($skunums === true){
					continue;
				}else if ($totalweight>20) {
					if($skunums==1){
						usCalcShipCost($ebay_id); //预留接口
					}
				} else {
					usCalcShipCost($ebay_id); //预留接口
				}
			//}
			return $orderType = C('STATEPENDING_OVERSEA');
		}
		
		return false;
	}

	/**
	 * 超大订单拦截，只拦截超大订单
	 * @eturn array 返回ordertype
	 * @author lzx 
	 */
	public function interceptLargeOrder(){
		$is_640 = false;
		//$orderStatus = $this->orderData['order']['orderStatus'];
		$orderType = $this->orderData['order']['orderType'];
		$accountId = $this->orderData['order']['accountId'];
		$orderDetail = $this->orderData['detail'];
		foreach($orderDetail as $detail){
			$hava_goodscount = true;
			$sku 		= $detail['base']['sku'];
			$amount 	= $detail['base']['amount'];
			$skuinfo 	= M("InterfacePc")->getSkuinfo($sku);
			/***筛选订单中的超大订单料号 Start ***/
			foreach($skuinfo['skuInfo'] as $or_sku => $skuinfoDetailValue){
				//$allnums = $skuinfoDetailValue['amount'];
				if (!M("InterfacePurchase")->check_sku($skuinfoDetailValue['skuDetail'], $amount)){
					//超大订单状态
					$is_640 = true;
					break;
				}
			}
		}
		if($is_640){
			if(in_array($accountId, M('Account')->getAccountNameByPlatformId(2))){
				return $orderType = C('STATEOVERSIZEDORDERS_CONFIRM');
			}else{
				return $orderType = C('STATEOVERSIZEDORDERS_PEND');
			}
		}
		return false;
	}

	/**
	 * 缺货拦截，只拦截订单是否有货
	 * @eturn array 返回ordertype
	 * @author lzx 
	 */
	public function interceptOutOfStockOrder(){
		$record_details = array();
		$orderStatus = $this->orderData['order']['orderStatus'];
		$orderType = $this->orderData['order']['orderType'];
		$accountId = $this->orderData['order']['accountId'];
		$transportId = $this->orderData['order']['transportId'];
		$countryName = $this->orderData['userinfo']['countryName'];
		$username = $this->orderData['userinfo']['username'];
		$orderDetail = $this->orderData['detail'];
		foreach($orderDetail as $detail){
			$hava_goodscount = true;
			$sku = $detail['base']['sku'];
			$skuinfo = M("InterfacePc")->getSkuinfo($sku);
			/***筛选订单中的超大订单料号 Start ***/
			foreach($skuinfo['skuInfo'] as $or_sku => $skuinfoDetailValue){
				$allnums = $skuinfoDetailValue['amount'];
				
				//$salensend = getpartsaleandnosendall($or_sku, $defaultstoreid);//预留接口
				
				//$sql = "UPDATE ebay_sku_statistics SET salensend = $salensend WHERE sku = '$or_sku' ";
				//$dbcon->execute($sql);
				//$log_data .= "[".date("Y-m-d H:i:s")."]\t---{$sql}\n\n";
				//$log_data .= "订单===$ebay_id===料号==$or_sku===实际库存为{$skuinfo['realnums']}===B仓库库存为{$skuinfo['secondCount']}===需求量为{$allnums}===待发货数量为{$salensend}===\n";
				$realnums = isset($skuinfo['realnums']) ? $skuinfo['realnums'] : 0;
				$secondCount = isset($skuinfo['secondCount']) ? $skuinfo['secondCount'] : 0;
				if(in_array($orderType, array(C('STATEOUTOFSTOCK_KD'),C('STATEOUTOFSTOCK_AO'),C('STATEOUTOFSTOCK_PO'),C('STATEOUTOFSTOCK_BKD')))){
					$remainNum = $realnums + $secondCount - $allnums - $salensend;
				}else{
					$remainNum = $realnums + $secondCount - $salensend;	
				}
				if($remainNum < 0){
					$hava_goodscount = false;
					break;
				}
			}
			if($hava_goodscount){$record_details[] = $detail;}
		}
		$count_record_details = count($record_details);
		$count_orderdetaillist = count($orderDetail);
		//$orderType = $orderStatus; //原始状态
		if($count_record_details == 0){
			//更新至自动拦截发货状态
			if (!in_array($transportId, M("InterfaceTran")->getCarrierNameList(0, true))){//非快递
				$orderType = C('STATEOUTOFSTOCK_KD');
			}else {
				$orderType = C('STATEOUTOFSTOCK_AO');
			}
			return $orderType;
		}else if($count_record_details < $count_orderdetaillist){
			//更新至自动部分发货状态
			if (!in_array($transportId, M("InterfaceTran")->getCarrierNameList(0, true))){
				$orderType = C('STATEOVERSIZEDORDERS_PEND');
				if(in_array($accountId, $SYSTEM_ACCOUNTS['cndirect']) || in_array($accountId, $SYSTEM_ACCOUNTS['dresslink'])){
					$orderType = C('STATEOUTOFSTOCK_BKD');//add by Herman.Xi@20131202 部分包货料号订单进入
				}
			}else {
				$orderType = C('STATEOUTOFSTOCK_PO');
			}
			return $orderType;
		}else if($count_record_details == $count_orderdetaillist){
			//正常发货状态
			if(in_array($accountId,$SYSTEM_ACCOUNTS['ebay平台']) || in_array($accountId,$SYSTEM_ACCOUNTS['海外销售平台'])){
				$status683 = false;
				if(in_array($countryName, array('Belarus','Brazil','Brasil','Argentina','Ukraine')) && str_word_count($username) < 2){
					$status683 = true;
				}
				if($status683){
					$orderType = C('STATESYNCINTERCEPT_AD');
				}
				if(in_array($orderType, array(C('STATEOUTOFSTOCK_KD'),C('STATEOUTOFSTOCK_AO'),C('STATEOUTOFSTOCK_PO'),C('STATEOUTOFSTOCK_BKD'),C('STATESYNCINTERCEPT_NL')))){
					//$orderType = 618;//ebay订单自动拦截有货不能移动到待处理和有留言 modified by Herman.Xi @ 20130325(移动到缺货需打印中)
					/*if($ebay_note != ''){
						echo "有留言\t";
						$orderType = 593;
					}else{*/
						$orderType = C('STATESYNCINTERCEPT_QXP');
					//}
				}else{
					/*if($ebay_note != ''){
						echo "有留言\t";
						$orderType = 593;
					}else{*/
						$orderType = C('STATEPENDING_CONV');
					//}
				}
			}else if(in_array($accountId, M('Account')->getAccountNameByPlatformId(2)) /*|| in_array($accountId, $SYSTEM_ACCOUNTS['B2B外单'])*/){
				$orderType = C('STATEPENDING_ALIEXPRESS');
				$status683 = false;
				if(in_array($countryName, array('Russian Federation', 'Russia')) && strpos($CarrierName, '中国邮政')!==false && str_word_count($username) < 2){
					$status683 = true;
				}
				if(in_array($countryName, array('Belarus','Brazil','Brasil','Argentina','Ukraine')) && str_word_count($ebay_username) < 2){
					$status683 = true;
				}
				if($status683){
					$orderType = C('STATESYNCINTERCEPT_AD');
				}
			}else if(in_array($accountId, M('Account')->getAccountNameByPlatformId(4))){//aliexpress
				$orderType = C('STATEPENDING_DHGATE');
			}else if(in_array($accountId, M('Account')->getAccountNameByPlatformId(10))){//dresslink.com
				$orderType = C('STATEPENDING_CONV');
			}else if(in_array($accountId, M('Account')->getAccountNameByPlatformId(8))){//cndirect.com
				$orderType = C('STATEPENDING_CONV');
			}else if(in_array($accountId, M('Account')->getAccountNameByPlatformId(11))){//Amazon
				if(in_array($orderType, array(C('STATEOUTOFSTOCK_KD'),C('STATEOUTOFSTOCK_AO'),C('STATEOUTOFSTOCK_PO'),C('STATEOUTOFSTOCK_BKD'),C('STATESYNCINTERCEPT_NL')))){
					if (in_array($transportId, M("InterfaceTran")->getCarrierNameList(0, true))){
						$orderType = C('STATESYNCINTERCEPT_QXP'); //modified by Herman.Xi @20131106 刘丽需要修改成缺货需打印中
					}else if($CarrierName == 'FedEx'){
						$orderType = C('STATEPENDING_OFFLINE'); //modified by Herman.Xi @20131213 刘丽需要修改线下订单导入
					}else{
						$orderType = C('STATEOVERSIZEDORDERS_TA'); //modified by Herman.Xi @20131119 刘丽需要修改成待打印线下和异常订单
					}
				}else{
					$orderType = C('STATEPENDING_CONV');
				}
			}else{
				$orderType = C('STATEPENDING_CONV');
			}
			return $orderType;
		}
		return false;
	}
	
	/**
	 * 有留言订单拦截
	 * @eturn array 返回ordertype
	 * @author lzx 
	 */
	public function interceptHaveMessageOrder(){
		$orderType = $this->orderData['order']['orderType'];
		$feedback = $this->orderData['extens']['feedback'];
		//if(in_array($accountId,$SYSTEM_ACCOUNTS['ebay平台']) || in_array($accountId,$SYSTEM_ACCOUNTS['海外销售平台'])){
			if($feedback != ''){
				//echo "有留言\t";
				return $orderType = C('STATEPENDING_MSG');
			}
		//}
		return false;
	}
	
	/**
	 * 超重订单拦截
	 * @eturn array 返回ordertype
	 * @author lzx 
	 */
	public function interceptOverWeightOrder(){
		$orderType = $this->orderData['order']['orderType'];
		$calcWeight = $this->orderData['order']["calcWeight"];
		if($calcWeight > 2){
			//echo "\t 超重订单";
			return $orderType = C('STATEPENDING_OW');
		}
		return false;
	}
	
	/**
	 * 快递订单拦截
	 * @eturn array 返回ordertype
	 * @author lzx 
	 */
	public function interceptExpressOrder(){
		$orderType = $this->orderData['order']['orderType'];
		$accountId = $this->orderData['order']['accountId'];
		$transportId = $this->orderData['order']['transportId'];
		if (!in_array($transportId, M("InterfaceTran")->getCarrierNameList(0, true)) && !empty($transportId)){
			if(in_array($accountId,$SYSTEM_ACCOUNTS['ebay平台']) || in_array($accountId,$SYSTEM_ACCOUNTS['海外销售平台'])){
				$orderType = C('STATEOVERSIZEDORDERS_TA');//ebay和海外都跳转到 待打印线下和异常订单
			}else{
				$orderType = C('STATEPENDING_OFFLINE');
			}
			return $orderType;
		}
		return false;
	}
	
	/**
	 * 刷单订单拦截
	 * @eturn array 返回ordertype
	 * @author lzx 
	 */
	public function interceptBrushTradeOrder(){
		//预留接口
	}
	
	/**
	 * 无需发货订单拦截
	 * @eturn array 返回ordertype
	 * @author lzx 
	 */
	public function interceptUnshippingOrder(){
		//预留接口
	}
	###可扩展专线测试拦截等
}
?>