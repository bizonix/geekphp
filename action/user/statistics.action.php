<?php
/**
 * 类名：StatisticsAct
 * 功能: 店铺管理
 * 版本：v1.0
 * 作者：zjr
 * 时间：2014/01/18
 * errCode：
 */
class StatisticsAct extends CheckAct {
	public function __construct(){
		parent::__construct();
		C(include WEB_PATH.'conf/order_conf.php');
	}
	
	/**
	 * 根据时间收录售出的sku
	 */
	public function insertSalesSku($startTime,$endTime){
		if(empty($startTime)){
			self::$errMsg['10007']	= get_promptmsg('10007','起始时间');
			return false;
		}
		if(empty($endTime)){
			self::$errMsg['10007']	= get_promptmsg('10007','结束时间');
			return false;
		}
		$retData = array();
		$orders = M('Order')->getAllData('*',"handle_status = '12' and update_time >= $startTime and update_time <= $endTime");
		foreach ($orders as $order) {
			$tmpData = $order;
			M("OrderDetails")->setTablePrefix('_'.date('Y_m',$order["create_time"]));
			$detailData = M('OrderDetails')->getSingleData('childOrderList',"id = {$order['id']}");
			if(!empty($detailData)){
				$tmpData['childOrderList'] = $detailData['childOrderList'];
			}
			$res1 = $res2 = array();
			$res1 = M('SkuSales')->insertSkuSalesDatas($tmpData);
			$res2 = M('OrderSales')->insertSkuSalesDatas($tmpData);
			$res  = array_merge($res1,$res2);
			$retData[$order['order_id']] = $res;
		}
		
		return $retData;
		
	}

	/**
	 * 根据时间收录售出的sku
	 */
	public function importStatics($orderIds){
		if(empty($orderIds)){
			self::$errMsg['10007']	= get_promptmsg('10007','订单号');
			return false;
		}
		$retData = array();
		$orderIdStr = implode("','",$orderIds);
		$orders = M('Order')->getAllData('*',"id IN ('{$orderIdStr}')");
		foreach ($orders as $order) {
			$tmpData = $order;
			M("OrderDetails")->setTablePrefix('_'.date('Y_m',$order["create_time"]));
			$detailData = M('OrderDetails')->getSingleData('childOrderList',"id = {$order['id']}");
			if(!empty($detailData)){
				$tmpData['childOrderList'] = $detailData['childOrderList'];
			}
			$res1 = $res2 = array();
			$res1 = M('SkuSales')->insertSkuSalesDatas($tmpData);
			$res2 = M('OrderSales')->insertSkuSalesDatas($tmpData);
			$res  = array_merge($res1,$res2);
			$retData[$order['order_id']] = $res;
		}
		
		return $retData;
		
	}

	/**
	 * query orderFee
	 * zjr
	 */
	public function act_getOrderSales($params = array()){
		$company_id = get_usercompanyid();
		if(empty($company_id)){
		    self::$errMsg['10007']	= get_promptmsg('10007','公司ID');
			return false;
		}
		$whereStr 	  = "company_id = '{$company_id}'";
		if(!empty($params['platform'])){
			$whereStr .= " and platform = '".mysql_real_escape_string($params['platform'])."'";
		}
		if(!empty($params['shop_id'])){
			$whereStr .= " and shop_id = '".mysql_real_escape_string($params['shop_id'])."'";
		}
		if(!empty($params['order_id'])){
			$whereStr .= " and order_id = '".mysql_real_escape_string($params['order_id'])."'";
		}
		if(!empty($params['come_from'])){
		    $whereStr .= " and come_from = '".mysql_real_escape_string($params['come_from'])."'";
		}
	    if(empty($params['startTime'])){
			$startTime = time();
		}else{
		    $startTime = strtotime($params['startTime']);
		    $whereStr  .= " and date >= {$startTime}";
		}
		if(!empty($params['endTime'])){
		    $endTime = strtotime($params['endTime']) + 3600*24-1;
		    $whereStr  .= " and date <= {$endTime}";
		}
		M("OrderSales")->setTablePrefix('_'.date('Y',$startTime));
		$count	  = M("OrderSales")->getDataCount($whereStr);
		$p 		  = new Page ($count,10);
		$orderSales = M("OrderSales")->getData("*",$whereStr,"order by id desc",$this->page,$this->perpage);
		$page 		= $p->fpage();
		$shops = M("Shops")->getAllData("*","belong_company = {$company_id}","id");
		$platforms = M("Platform")->getAllData("*","1","id");
		//获取所有涉及到的公司
		$relationCompany = M("CompanyRelation")->getAllData("*","to_company = $company_id",'belong_company');
		$companyIds      = array_keys($relationCompany);
		if($companyIds){
		    $companyIdsStr = implode(",", $companyIds);
		    $companys	= M("Company")->getAllData('id,cn_name',"id IN ({$companyIdsStr})",'id');
		}else{
		    $companys = array();
		}
		//统计订单总价
		$totalFee = $this->act_getTotalFee($startTime,$company_id,$whereStr);
		return array("shops"=>$shops,'platforms'=>$platforms,"orderSales"=>$orderSales,"page"=>$page,"count"=>$count,'totalFee'=>$totalFee,'companys'=>$companys);
	}

	public function act_getTotalFee($startTime,$company_id,$whereArr=array()){
	    $retData = array('amount' => '0.00','cost' => '0.00');
		if(empty($startTime)){
			self::$errMsg['10007']	= get_promptmsg('10007','初始时间');
			return $retData;
		}
		if(empty($company_id)){
			self::$errMsg['10007']	= get_promptmsg('10007','团体ID');
			return $retData;
		}
		$where = "company_id = {$company_id}";
		if(!empty($whereArr)){
		    if(is_array($whereArr)){
		        $where .= isset($whereArr['platform']) && !empty($whereArr['platform']) ? " and platform = '{$whereArr['platform']}' " : "";
		        $where .= isset($whereArr['shop_id']) && !empty($whereArr['shop_id']) ? " and shop_id = '{$whereArr['shop_id']}' " : "";
		        $where .= isset($whereArr['order_id']) && !empty($whereArr['order_id']) ? " and order_id = '{$whereArr['order_id']}' " : "";
		        $where .= isset($whereArr['come_from']) && !empty($whereArr['come_from']) ? " and come_from = '{$whereArr['come_from']}' " : "";
		    }else{
		        $where = $whereArr;
		    }
		}
		$preFix = date('Y',$startTime);
		$salesArr = MC("select sum(sales_amount) as amount,sum(order_cost) as cost from we_order_sales_{$preFix} where {$where} and is_delete=0");
		if(!empty($salesArr)){
			return $salesArr[0];
		}else{
			return $retData;
		}
	}

}
