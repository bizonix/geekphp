<?php
/**
 * 功能：统计管理
 * @author zjr
 * v 1.0
 * 时间：2015/05/15
 *
 */
class StatisticsView extends BaseView {
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

	/**
	 * 订单费用查询
	 */
	public function view_getOrderSales(){
		$platform 	  = $this->getParam('platform');
		$shop_id	  = $this->getParam('shop_id');
		$order_id  	  = $this->getParam('order_id');
		$come_from    = $this->getParam('come_from');
		$startTime    = $this->getParam('startTime');
		$endTime      = $this->getParam('endTime');
		$params = array(
		    'startTime'   => $startTime,
		    'endTime'     => $endTime,
		    'come_from'   => $come_from,
		    'order_id'    => $order_id,
		    'shop_id'     => $shop_id,
		    'platform'    => $platform,
		);
		$this->smarty->assign(A('Statistics')->act_getOrderSales($params));
		$this->smarty->display('user/statics/orderSales.html');
	}
}
?>