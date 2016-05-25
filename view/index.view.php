<?php
class IndexView extends BaseView {
	
	public function __construct(){
		parent::__construct();
	}
	
	public function view_index() {
            $this->smarty->display('homePage.html');
	}
	public function view_test() {
		$res = A('Track')->act_getChangesFee('CV','CNPSS');
		var_dump($res);exit;
		A('IShipperButt')->setConfig('12ef1361549b0b959658fd4b63f8bf2a');
		$res = A('IShipperButt')->postOrder(array('13971'));
		// $res = A('IShipperButt')->getallshipway();
		var_dump($res);exit;
		F("xmlhandle");
		$str ='<?xml version="1.0" encoding="UTF-8"?><Orders><!-- 订单在使用者系统的唯一标识ID --><OrderId>45646466</OrderId><OrderId>67498446</OrderId></Orders>';
print_r(XML_unserialize($str));exit;
		echo arrayToXml(array("fff"=>array("aaa"=>"333","sss"=>"bbbb")),"orders");exit;
		// $getCodeUrl ='http://gw.api.alibaba.com/auth/authorize.htm?client_id=8977791&site=aliexpress&redirect_uri=http://www.weclu.com/public/getSmtRefrashToken/&_aop_signature='.$this->Sign(array('client_id' => '8977791','redirect_uri' =>'http://www.weclu.com/public/getSmtRefrashToken/','site' => 'aliexpress'));
		A("WishButt")->setConfig();
		$res = A("WishButt")->retrieveUnfulfilledOrders();
		var_dump($res);exit;
		// echo $getCodeUrl;exit;
		// header("Location:{$getCodeUrl}");exit;AliexpressButt
		// $send = A("Public")->act_sendEmail("2853138090@qq.com",'checkPassword');
		A("AliexpressButt")->setToken("more",'{"appKey":"4221667","appSecret":"uDz8xleLwYsj","refreshToken":"24e2771e-d4e2-4670-8f3b-e00ddeef513b"}');
		// $res = A("AliexpressButt")->listLogisticsService();
		$res = A("AliexpressButt")->findOrderById('65841411250899');
		var_dump($res);exit;
	    /*
	    $_REQUEST['spu'] = '18857';
	    $_REQUEST['email'] = 'test900101@126.com';
	    $ret = A("ApiOpen")->act_checkIsOpenProduct();
	    var_dump($this->ajaxReturn($ret));exit;
	    $_GET['app_key']   = 'I5FQN_MPQRD_2UCGG_Z0X7L_7QR0V';
	    $_GET['orderIds']  = json_encode(array('12345678910069'));
	    $_GET['status']  = '1';
//  	    $test = A('ApiOpen')->act_getOrderInfo('I5FQN_MPQRD_2UCGG_Z0X7L_7QR0V',json_encode(array('64437687395873')));
	    
 	    
// 	    $test = M("InterfacePc")->getRootCategoryInfo('all');
	    //$test = M("OrderImport")->buildTransport();
	    $test = A('ApiOpen')->act_stopOrStarOrder();
	    */
	   
	   //测试抓单程序
	   $test 	= A("ApiIntegration")->act_getAliexpressOrder('more','1');
	    var_dump($this->ajaxReturn($test));exit;
	    A("AliexpressButt")->get_refresh_token();
	}

	
}
?>