<?php
/**
 * PublicView
 * 功能：用于公共的ajax处理控制
 * @author wcx
 * v 1.0
 * 2014/06/26  
 */
class PublicView extends BaseView {
	
	public function __construct(){
		parent::__construct();
	}
	/**
	 * 验证验证码
	 */
	public function view_checkCode() {
		echo $this->ajaxReturn(A('Public')->act_checkCode());
	}
	
	/**
	 * 邮箱证码
	 */
	public function view_checkEmail() {
		echo $this->ajaxReturn(A('Public')->act_checkCode());
	}
	
	/**
	 * 认证认证分销商
	 */
	public function view_checkDistribution() {
		echo $this->ajaxReturn(A('Public')->act_checkDistributor());
	}
	
	/**
	 * 函数说明：查看基本信息、店铺完成情况，以便引导
	 */
	public function view_checkDistributorSituation(){
		echo $this->ajaxReturn(A('Public')->act_checkDistributorSituation());
	}
	
	/**
	 * 404页面显示 
	 * wcx
	 */
	public function view_showErr(){
	    if(isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])){
	        $location = $_SERVER['HTTP_REFERER'];
	    }else{
	        $location = '/dashboard/index';
	    }
		$this->error_404Jump(' 您要查看的页面不存在或已删除！', $location);
	}

	/**
	 * 获取速卖通refrashToken方法
	 * add wcx
	 */
	public function view_getSmtRefrashToken(){
		session_start();
		$company		=	isset($_REQUEST['data']) ? $_REQUEST['data'] : '';
		$data			=	explode('?',$company);
		list($company,$appKey,$appSecret,$account)	=	explode('_', $data[0],4);
		$code			=	substr($data[1],5);

		if(empty($code)) {
			$this->error_404Jump("授权失败，获取code失败");
			exit;
		}else{
			A("AliexpressButt")->savToken($company,$account,$code,$appKey,$appSecret);
			redirect_to(WEB_URL."order/orderList");
		}
		

	}

	public function getToken($appKey, $appSecret, $redirectUrl, $code, $getTokenUrl){
		$data =array(
			'grant_type'		=>'authorization_code',	
			'need_refresh_token'=>'true',				
			'client_id'			=>$appKey,				
			'client_secret'		=>$appSecret,			
			'redirect_uri'		=>$redirectUrl,			
			'code'				=>$code,
		);
		return	json_decode(Curl($getTokenUrl,$data),true);
	}
	//获取跳转页面
	public function act_getToken() {
		$account		=	isset($_REQUEST['account']) ? $_REQUEST['account'] : "";
		$accountId		=	isset($_REQUEST['accountId']) ? $_REQUEST['accountId'] : "";

		$appKey			=	isset($_REQUEST['appKey']) ? $_REQUEST['appKey'] : "";
		$appSecret		=	isset($_REQUEST['appSecret']) ? $_REQUEST['appSecret'] : "";
		if(empty($account) || empty($accountId) || empty($appKey) || empty($appSecret)) {
     		self::$errCode	=	'6804';
     		self::$errMsg	=	'请检查是否缺少关键数据!';
     		return false;
		}
		$accountArr		=	array(
								'account'	=>	$account,
								'accountId'	=>	$accountId,
							);
		$accountArr		=	json_encode($accountArr);
		C(include WEB_PATH.'conf/url_conf.php');
		$callback_url	=	C('SMT_GET_REFRESH_TOKEN_URL')."?account=$account";

		$getCodeUrl		=	"https://gw.api.alibaba.com/auth/authorize.htm?client_id=".$appKey ."&site=aliexpress&redirect_uri=".$callback_url."&_aop_signature=".$this->Sign(array('client_id' => $appKey,'redirect_uri' =>$callback_url,'site' => 'aliexpress'),$appSecret);
		header("Location:{$getCodeUrl}");
	}

	public function view_smtGetCodeByClientId($type='add'){
		F("api.aliexpress.Aliexpress");
		$appKey = $this->getParam('appKey');
		$appKey = $this->getParam('appKey');
		if(empty($clientId)) {
     		self::$errCode	=	'6804';
     		self::$errMsg	=	'请检查是否缺少关键数据!';
     		return false;
		}
		$callback_url	=	C('SMT_GET_REFRESH_TOKEN_URL')."/type/{$type}";

		$getCodeUrl		=	"http://gw.api.alibaba.com/auth/authorize.htm?client_id=4221667&site=aliexpress&redirect_uri=".$callback_url."&_aop_signature=".$this->Sign(array('client_id' => $appKey,'redirect_uri' =>$callback_url,'site' => 'aliexpress'),$appSecret);
		header("Location:{$getCodeUrl}");
	}

	/*
	 *	功能：根据平台ID获取跟踪号ID
	 *	by：wcx
	 */
	public function view_getPlatformCarrier(){
		$platformId = $_REQUEST['platformId'];
		echo $this->ajaxReturn(A('Public')->act_getPlatformCarrier($platformId));
	}

}
?>