<?php
/*
 *订单系统
 *@add by : wcx
 */
defined('WEB_PATH') ? '' : exit;
class InterfaceOrderModel extends InterfaceModel {
	
	public function __construct(){
		parent::__construct();
	}
    
	/**
     * 取消订单
	 * @param varchar $recordNo
	 * @return array
	 * @author wcx
     */
	public function cancelOrder($data){
		$conf				= $this->getRequestConf(__FUNCTION__);
		$conf['orderRecordNumber']	= json_encode($data);
		$result				= callOpenSystem($conf);
		$data				= json_decode($result,true);
//		if ($data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
	}
	/**
     * 取消订单
	 * @param varchar $recordNo,varchar $acccount
	 * @return array
	 * @author wcx
     */
	public function getOrderInfo($recordNo,$account){
		$conf					= $this->getRequestConf(__FUNCTION__);
		$conf['recordnumber']	= $recordNo;
		if(!empty($account)){
			$conf['account']	= $account;
		}
		$result					= callOpenSystem($conf);
//		$result					= '{"status":"success","msg":"success","data":{"9927882":{"order":{"id":"9927882","recordNumber":"64020046488291","platformId":"2","accountId":"434","ordersTime":"1412489986","paymentMethod":"","paymentTime":"1412490112","onlineTotal":"3.11","actualTotal":"3.11","currency":"USD","ORtransport":"China Post Air Mail","usefulChannelId":"1,3,87,13,83,84,19,67,68,41,42,104,106,108,111,112","transportId":"0","actualShipping":"0.00","marketTime":"0","ShippedTime":"0","orderStatus":"212","orderType":"1000","site":"","orderAttribute":"1","pmId":"96","channelId":"0","calcWeight":"0.000","calcShipping":"0.000","orderAddTime":"1412654646","isSendEmail":"0","isNote":"0","isAllowCopy":"0","isAllowSplit":"0","isAllowCombinePackage":"0","isAllowCombineOrder":"0","isCopy":"0","isSplit":"0","combinePackage":"0","combineOrder":"0","completeTime":"0","storeId":"0","orderStore":"1","is_offline":"0","is_delete":"0","isExpressDelivery":"0","fromOrderType":"0","moveTime":"0"},"orderExtension":{"omOrderId":"9927882","declaredPrice":"3.11","initOderAmount":"3.11","buyerLoginid":"at1010322130","orderStatus":"WAIT_SELLER_SEND_GOODS","frozenStatus":"NO_FROZEN","logisticsStatus":"WAIT_SELLER_SEND_GOODS","issueStatus":"NO_ISSUE","loanStatus":"pay_success","fundStatus":"PAY_SUCCESS","sellerSignerFullname":"Jenny Huang","issueContent":"pay_success","feedback":"","payPalPaymentId":""},"orderDetail":{"219901":{"orderDetail":{"id":"219901","omOrderId":"9927882","recordNumber":"64020046488291","itemId":"997043735","itemPrice":"3.11","sku":"SV002933_S","onlinesku":"SV002933_S#","amount":"1","shippingFee":"0.00","firstReviews":"0","finalReviews":"0","firstReviewsTime":"0","finalReviewsTime":"0","createdTime":"1412654646","storeId":"2","is_delete":"0"},"orderDetailExtension":{"omOrderdetailId":"219901","initOrderAmtAmount":"0.00","itemTitle":"Womens Ladies Sexy Open Crotch Thongs G-string V-string Panties Knickers Underwear 6 Color Dropshipping 7260","itemURL":"http:\/\/www.aliexpress.com\/snapshot\/6269635778.html"}}},"fees_info":{"total_fee":5.345,"fee_detail":{"pmCost":"0.125","shipping_fee":"1.62","order_weight":"0.028","sku_cost":{"SV002933_S":3.6},"single_sku_cost":{"SV002933_S":"3.600"}}}},"is_from_split":false}}';
		$data					= json_decode($result,true);
		if (@$data['errCode']>0) self::$errMsg[$data['errCode']] = "[{$data['errCode']}]{$data['errMsg']}";
		return $this->changeArrayKey($data['data']);
	}
	/**
	 * 功能：同步订单信息到订单系统
	 * @param $orderdatas
	 */
    public function synOrderInfoToOrderSys($orderdatas=''){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['orderdatas'] = $orderdatas;
		$conf['cachetime']	= 0;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		//if ($data['error_response']['code'] != 200) self::$errMsg[$data['error_response']['code']] = "[{$data['error_response']['code']}]{$data['error_response']['msg']}";
		return $data;
	}
	
	/**
	 * 暂停/重启发货 操作接口
	 * @author jbf
	 */
	public function setSuspend($request) {
	    $conf = $this -> getRequestConf(__FUNCTION__);
	    if (empty($conf)) return false;
	    $conf['requestData']   = json_encode($request);
	    $conf['requestData']   = urlencode($conf['requestData']);
	    $conf['cachetime']     = 0;
	    Log::write("\nHold Request Param:".json_encode($conf));
	    $result    = callOpenSystem($conf);
	    Log::write("\nHold Source Results:".$result);
	    $data      = json_decode($result, true);
	    //	    if ($data['error_response']['code'] != 200) self::$errMsg[$data['error_response']['code']] = "[{$data['error_response']['code']}]{$data['error_response']['msg']}";
	    return $data;
	}
	
	/**
	 * @description 订单查询接口
	 * @param request $name Description
	 * @author lzj
	 */
	public function getOrderList($json) {
		$conf = $this->getRequestConf(__FUNCTION__);
		if(empty($conf)) return false;
		$conf['condition'] = $json;
		$result = callOpenSystem($conf);
		$data = json_decode($result, true);
		if(!empty($data['data']))return $this->changeArrayKey($data['data']);
		return $data;
	}
	
	public function getAllOrderStatusInfo(){
		$conf = $this->getRequestConf(__FUNCTION__);
		//var_dump($conf);exit;
		$result = callOpenSystem($conf);
		$data = json_decode($result, true);
		if(!empty($data['data']))return $this->changeArrayKey($data['data']);
		return $data;
	}
}