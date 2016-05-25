<?php
/**
 * 功能：订单详情
 * @author zjr
 * v 1.0
 * 时间：2015/01/14
 *
 */
class OrderDetailsView extends BaseView {
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        C(include WEB_PATH.'conf/order_conf.php');
    }
    /*
     * 显示速卖通订单
     */
	public function view_getSmtOrderDetails() {
		$orderId 	= $_REQUEST['orderId'];
		$list       = A('OrderDetails')->act_getSmtOrderDtail($orderId);
        if(empty($list)){
        	$this->error("该订单不存在","",3);
        	exit;
        }else{
	        $this->smarty->assign($list);
	        $this->smarty->display('user/order/orderDetails.html');
        }
	}

	/*
     * 显示订单详情
     */
	public function view_getOrderDetails() {
		$orderId 	= $_REQUEST['orderId'];
		$list       = A('OrderDetails')->act_getOrderDtail($orderId);
        if(empty($list)){
        	$this->error("该订单不存在","",3);
        	exit;
        }else{
	        $this->smarty->assign($list);
	        $this->smarty->display('user/order/orderDetail.html');
        }
	}
		
	/**
	 * 订单更新
	 */
	public function view_updateOrderInfo() {
		$orderSysId 	= $this->must($this->getParam('orderSysId'),"订单系统编号");
		$receiveCompany = $this->must($this->getParam('receiveCompany'),"去向公司ID");
		$skuCodes		= $this->getParam('skuCodes');
		$transportType	= $this->getParam('transportType');
		$countrySn		= $this->getParam('countrySn');
		$msgLists		= $this->getParam('msgLists');
		$data			= array(
			"orderSysId" 		=> $orderSysId,
			"receiveCompany"	=> $receiveCompany,
			"skuCodes"			=> $skuCodes,
			"transportType"		=> $transportType,
			"countrySn"			=> $countrySn,
			"msgLists"			=> $msgLists,
		);
		echo $this->ajaxReturn(A('OrderDetails')->act_updateSmtOrderDetail($orderSysId,$data));
	}

	/**
	 * 订单更新 最新兼容所有平台
	 */
	public function view_updateOrderInfos() {
		$orderSysId 	= $this->must($this->getParam('orderSysId'),"订单系统编号");
		$receiveCompany = $this->must($this->getParam('receiveCompany'),"去向公司ID");
		$childOrderList	= $this->getParam('childOrderList');
		$transportType	= $this->getParam('transportType');
		$shippingType	= $this->getParam('shippingType');
		$countrySn		= $this->getParam('countrySn');
		$data			= array(
			"orderSysId" 		=> $orderSysId,
			"receiveCompany"	=> $receiveCompany,
			"childOrderList"	=> $childOrderList,
			"transportType"		=> $transportType,
			"shippingType"		=> $shippingType,
			"countrySn"			=> $countrySn,
		);
		echo $this->ajaxReturn(A('OrderDetails')->act_updateOrderDetail($orderSysId,$data));
	}

	/**
	 * 更新订单收货地址
	 */
	public function view_updateReceiptAddr() {
		$orderSysId 	= $this->must($this->getParam('orderSysId'),"订单系统编号");
		$receiptAddress	= $this->getParam('receiptAddress');
		$buyerInfo		= $this->getParam('buyerInfo');
		$data			= array(
			"orderSysId" 		=> $orderSysId,
			"receiptAddress"	=> $receiptAddress,
			"buyerInfo"			=> $buyerInfo,
		);
		echo $this->ajaxReturn(A('OrderDetails')->act_updateReceiptAddr($data));
	}

	/**
	 * 更新报关信息
	 */
	public function view_updateDeclaration(){
		$orderSysId 				= $this->must($this->getParam('orderSysId'),"订单系统编号");
		$orderDeclarationContent	= $this->getParam('orderDeclarationContent');
		if($orderDeclarationContent['CustomsType'] == 'elseType'){
			$orderDeclarationContent['CustomsType'] = $this->getParam('elseType');
		}
		$data			= array(
			"orderSysId" 				=> $orderSysId,
			"orderDeclarationContent"	=> $orderDeclarationContent,
		);
		echo $this->ajaxReturn(A('OrderDetails')->act_updateDeclaration($data));
	}
	
}
?>