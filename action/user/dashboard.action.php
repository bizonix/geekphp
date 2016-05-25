<?php
/**
 * 类名：DashboardAct
 * 功能: 监控板
 * 版本：v1.0
 * 作者：zjr
 * 时间：2015/05/06
 * errCode：
 */
class DashboardAct extends CheckAct {
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 获取今日订单总数
	 */
	public function act_getTodayOrderBoard(){
		$time = strtotime(date('Y-m-d',time()));
		$companyId = get_usercompanyid();
		$retData = array();
		$dataCount = MC("select handle_status,count(*) as count from we_order where company_id = $companyId and create_time > {$time} group by handle_status");
		if(!empty($dataCount)){
			foreach ($dataCount as $v) {
				foreach (C('ORDER_STATICS_STATUS') as $kk => $vv) {
					if(in_array($v['handle_status'],$vv)){
						@$retData[$kk] += $v['count'];
					}
				}
			}
		}
		@$retData['total'] = @$retData['handle_no']+@$retData['handle_in']+@$retData['handle_fd']+@$retData['handle_ed'];
		return $retData;
	}

	/**
	 * 获取历史
	 */
	public function act_getHistoryOrderBoard(){
		$companyId = get_usercompanyid();
		$retData = array();
		$dataCount = MC("select handle_status,count(*) as count from we_order where company_id = $companyId and is_delete=0 group by handle_status");
		if(!empty($dataCount)){
			foreach ($dataCount as $v) {
				foreach (C('ORDER_STATICS_STATUS') as $kk => $vv) {
					if(in_array($v['handle_status'],$vv)){
						@$retData[$kk] += $v['count'];
					}
				}
			}
		}
		@$retData['total'] = $retData['handle_no']+$retData['handle_in']+$retData['handle_fd']+$retData['handle_ed'];
		//获取销售额和成本
		@$retData['sales'] = A('Statistics')->act_getTotalFee(time(),$companyId);
		return $retData;
	}

	/*
	 * 获取已发货各店铺销量
	 */
	public function act_getHasSendByShop($startTimeStr='2015/01/01',$endTimeStr='2015/12/31'){
		$companyId 	= get_usercompanyid();
		$retData 	= array();
		$startTime 	= strtotime($startTimeStr);
		$endTime 	= strtotime($endTimeStr);
		$skuSales 	= MC("select sales_price,month,shop_id from ".C('DB_PREFIX').'sku_sales_'.date('Y',$startTime)." where company_id = {$companyId} and month >= {$startTime} and month <= {$endTime} and is_delete=0");
		$shops 		= M('Shops')->getAllData('shop_account,id',"belong_company = {$companyId}",'id');
		$ykeys = array('total');
		$colors = array("#81d5d9", "#a6e182", "#67bdf8","#43BCED","#B07C4D","#254E46","#2C2494","#E97E74","");
		$lineColors = array();
		$showData = array();
		foreach ($skuSales as $value) {
			$month = date('Y-m',$value['month']);
			$shopAccount = @$shops[$value['shop_id']]['shop_account'];
			@$showData[$month]['month'] = $month;
			@$showData[$month]['total'] += empty($value['sales_price']) ? 0.00 : $value['sales_price'];
			if(!in_array($shopAccount, $ykeys)){
				$ykeys[] = $shopAccount;
				if(count($ykeys) > count($colors)){
					$color = $colors[(count($ykeys)%count($colors))-1];
				}else{
					$color = $colors[count($ykeys)-1];
				}
				$lineColors[] = $color;
			}
			@$showData[$month][$shopAccount] += empty($value['sales_price']) ? 0.00 : $value['sales_price'];
		}
		//修复数据
		$data = array();
		foreach ($showData as $month => $shopInfo) {
			foreach ($ykeys as $ykey) {
				@$showData[$month][$ykey] = isset($showData[$month][$ykey]) ? $showData[$month][$ykey] : null;
			}
			$data[] = $showData[$month];
		}
		$retData = array('showData' => json_encode($data),'xkey' => 'month', 'ykeys'=> json_encode($ykeys),'lineColors'=>json_encode($lineColors));
		return $retData;
	}
	
}
