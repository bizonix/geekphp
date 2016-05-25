<?php
/**
 * 功能：订单管理管理
 * @author zjr
 * v 1.0
 * 时间：2014/12/16
 *
 */
class OrderView extends BaseView {
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        C(include WEB_PATH.'conf/order_conf.php');
    }
    /*
     * 功能： 订单显示
     * by zjr
     */
	public function view_orderlist() {
		$orderList = A("Order")->act_getOrderList();
		$this->smarty->assign($orderList);
		$this->smarty->display('user/order/orderList.html');
	}

	/*
     * 功能： 過濾订单显示
     * by zjr
     */
	public function view_queryOrderlist() {
		$shopId				= $_REQUEST['shopId'];
		$comeFrom			= $_REQUEST['comeFrom'];
		$deliveryFrom		= $_REQUEST['deliveryFrom'];
		$orderStatus		= $_REQUEST['orderStatus'];
		$handleStatus		= $_REQUEST['handleStatus'];
		$orderSysId			= $_REQUEST['orderSysId'];
		$orderId			= $_REQUEST['orderId'];
		$platformId			= $_REQUEST['platformId'];
		$sellerShipStatus	= $_REQUEST['sellerShipStatus'];
		$orderList = A("Order")->act_queryOrderList($shopId,$comeFrom,$deliveryFrom,$orderStatus,$handleStatus,$orderSysId,$orderId,$platformId,$sellerShipStatus);
		$this->smarty->assign($orderList);
		$this->smarty->display('user/order/orderList.html');
	}
	
	/**
	 * 功能：抓取订单
	 * zjr
	 */
	public function view_fechOrders(){
		$shopId 		= $_REQUEST['shopId'];
		$orderStatus 	= $_REQUEST['orderStatus'];
		$startTime 		= date("m/d/Y H:i:s",strtotime($_REQUEST['startTime']));	
		$endTime 		= date("m/d/Y H:i:s",strtotime($_REQUEST['endTime'])-1+3600*24);
		$ret = A("ApiIntegration")->act_getAliexpressOrder($shopId,$orderStatus,$startTime,$endTime,1,50);
		$this->ajaxReturn($ret);
	}

	/**
	 * 功能：推送订单至
	 * zjr
	 */
	public function view_pushOrders(){
		$orderSysIds = $_REQUEST['orderSysIds'];
		$ret = A("Order")->act_pushOrders($orderSysIds);
		$this->ajaxReturn($ret);
	}

	/**
	 * 功能：合并订单
	 * zjr
	 */
	public function view_mergeOrders(){
		$orderSysIds = $_REQUEST['orderSysIds'];
		$ret = A("Order")->act_mergeOrders($orderSysIds);
		$this->ajaxReturn($ret);
	}

	/**
	 * 功能：改变订单状态
	 * zjr
	 */
	public function view_updateHandleStatus(){
		$ordersId = array_filter(explode(",", $_REQUEST['ordersId']));
		$handleStatus = $_REQUEST['handleStatus'];
		$ret = A("Order")->act_updateOrderHandleStatus($ordersId,$handleStatus);
		$this->ajaxReturn($ret);
	}
	/**
	 * 功能：删除订单
	 * zjr
	 */
	public function view_deleteOrders(){
		$ordersId = $_REQUEST['ordersId'];
		$ret = A("Order")->act_deleteOrders($ordersId);
		$this->ajaxReturn($ret);
	}

	/**
	 * 功能：标记发货
	 * zjr
	 */
	public function view_sellersShippment(){
		$platformId 	= $_REQUEST['platformId'];
		$orderId 	 	= $_REQUEST['orderId'];
		$trackingNumber = $_REQUEST['trackingNumber'];
		$transportType 	= $_REQUEST['transportType'];
		$description 	= $_REQUEST['description'];
		$sendType 		= $_REQUEST['sendType'];
		$transportUrl 	= $_REQUEST['transportUrl'];
		$ret = A("Order")->act_sellerShippment($platformId,$orderId,$trackingNumber,$transportType,$description,$sendType,$transportUrl);
		$this->ajaxReturn($ret);
	}

}
?>