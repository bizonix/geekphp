<?php
/**
 * 功能：控制登录方面的一系列动作
 * @author wcx
 * v 1.0
 * 时间：2014/06/27
 *
 */
class LoginView extends BaseView {

	public function __construct(){
		parent::__construct();
	}
	
	/*
	 * 登录页面的控制
	 * wcx
	 */
	public function view_login() {
		$this->smarty->display('login.html');
	}

	/*
	 * 登录页面的控制
	 * wcx
	 */
	public function view_newPwd() {
		$res = A("Login")->act_checkEmail();
		if($res == 1){
			$this->smarty->display('login.html');
		}else{
			redirect_to("/login/login");
		}
	}

	/*
	 * 来自登录页面的请求控制
	 * wcx
	 */
	public function view_loginPost(){
		$this->ajaxReturn(A('Login')->act_login());
	}

	/*
	 * 退出登录
	 * wcx
	 */
	public function view_logout(){
		A('login')->act_logout();
		redirect_to("/index/index");
	}
	
	/*
	 * 忘记密码功能的显示
	 * wcx
	 */
	public function view_forget() {
		$this->smarty->display('login.html');
	}

	/*
	 * 忘记密码功能的显示
	 * wcx
	 */
	public function view_register() {
		if(isset($_REQUEST['retcode'])) {
			$retData		= json_decode($_REQUEST['retcode'],true);
		}
		$this->smarty->assign('retData',empty($retData) ? array() : $retData);
		$this->smarty->display('login.html');
	}
	
	/*
	 * 用户注册
	 * wcx
	 */
	public function view_registerd() {
		$registerAct	= A("Register");
		$res 			= $registerAct->act_register();
		$this->ajaxReturn($res);
	}

	/*
	 * 忘记密码页面的请求控制
	 * wcx
	 */
	public function view_forgetPost() {
		$email 		= isset($_REQUEST['email']) ? trim($_REQUEST['email']) : '';
		$sendEmail 	= 	A('Public')->act_sendEmail($email,"checkPassword");
		if($sendEmail) {
			$mailType	= explode("@", $email);
			$emailAddrs = C('EMAILADDRESS');
			//替换特殊邮箱edu
			$splitArray = explode(".", $mailType[1]);
			if(in_array("edu", $splitArray)){
				$mailType[1]				= str_replace($splitArray[0], "**", $mailType[1]);
				$emailAddrs[$mailType[1]]	= str_replace("**", $splitArray[0], $emailAddrs[$mailType[1]]);
			}
			$emailConf = array();
			if(!empty($emailAddrs[$mailType[1]])){
				$emailConf = array('address'=>$emailAddrs[$mailType[1]],"email"=>$email,"status"=>true);
			}else{
				log::writeLog("newEmail = ".$mailType[1],"email","no_cached",'y');
				$emailConf = array('address'=>'',"email"=>$email,"status"=>true);
			}
		}else {
			$emailConf = array('address'=>'',"email"=>$email,"status"=>false);
		}
		$this->ajaxReturn($emailConf);
	}
	
	/*
	 * 通过邮箱链接返回信息的处理
	* wcx
	*/
	public function view_updatePwd(){
		$this->ajaxReturn(A('Login')->act_updatePwd());
	}
	
	/*
	 * 激活邮件处理
	 * wcx
	 */
	public function view_activeUser(){
		$res 	= 	A('Login')->act_checkEmail();
		if($res==11){
			$this->smarty->assign('activeMsg',"恭喜你，成功激活，马上登录吧！");
		}else{
			$this->smarty->assign('activeMsg',"激活失败，请重新激活！");
		}
		$this->smarty->display('login.html');
		
	}

	/*
	 * 验证用户的邮箱和用户名
	 * wcx
	 */
	 
	public function view_check(){
		echo $this->ajaxReturn(A("Register")->act_check());
	}
}
?>