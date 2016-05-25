<?php
/**
 * 类名：DashboardAct
 * 功能: 监控板
 * 版本：v1.0
 * 作者：zjr
 * 时间：2015/05/06
 * errCode：
 */
class DashBoardView extends BaseView {
    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        C(include WEB_PATH.'conf/order_conf.php');
    }
	
	/**
	 * 收录售出的sku
	 */
	public function view_index(){
		$this->smarty->assign('todayOrders',A('Dashboard')->act_getTodayOrderBoard());
		$this->smarty->assign('historyOrders',A('Dashboard')->act_getHistoryOrderBoard());
		$this->smarty->assign(A('Dashboard')->act_getHasSendByShop());
		$this->smarty->display('user/dashboard.html');
	}

	public function view_getOrderSales(){
		$res = A('Statistics')->act_getOrderSales();
		$this->smarty->assign(A('Statistics')->act_getOrderSales());
		$this->smarty->display('user/statics/orderSales.html');
	}

}
