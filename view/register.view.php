<?php
/**
 * RegisterView
 * 功能：用于用户注册控制
 * @author 邹军荣
 * 2014/06/26
 *
 */
class RegisterView extends BaseView {

	public function __construct(){
		parent::__construct();
	}
	
	public function view_register() {
        $this->smarty->display('registered.html');
	}
	public function view_registerPost(){
		$reg	=	A('Register');
		$pub	=	A('Public');
		$checkCode = $pub->act_checkCode();		//验证验证码
		if($checkCode){
			if($pub->act_checkEmail()){//验证邮箱是否已经注册
				if($reg->act_register()){//开始注册
					echo $this->ajaxReturn($pub->act_sendEmail("checkRegister"));//发送邮件
				}
			}
		}
		echo $this->ajaxReturn();
		
	}
	/*
	 * 注册成功后跳转
	 * 
	 */
	public function view_registerLocation() {
		$flag 	= $_REQUEST['flag'];
		$email	= trim($_REQUEST['email']);
		if($flag == "resend"){
			$sendStatus = A('Public')->act_sendEmail("checkRegister");
			if($sendStatus){
				$flag = 'resendOk';
			}else{
				$flag = 'resendError';
			}
		}
		$mailType	= explode("@", $email);
		$emailAddrs = C('EMAILADDRESS');
		//替换特殊邮箱edu
		$splitArray = explode(".", $mailType[1]);
		if(in_array("edu", $splitArray)){
			$mailType[1]				= str_replace($splitArray[0], "**", $mailType[1]);
			$emailAddrs[$mailType[1]]	= str_replace("**", $splitArray[0], $emailAddrs[$mailType[1]]);
		}
			
		if(!empty($emailAddrs[$mailType[1]])){
			$this->smarty->assign('emailAddress',$emailAddrs[$mailType[1]]);
		}else{
			write_log(WEB_PATH."log/noCachedEmail.log", $mailType[1]);
			$this->smarty->assign('emailAddress',"unknown");
		}
		$this->smarty->assign("flag",$flag);
		$this->smarty->assign("email",$email);
		$this->smarty->display('registerLocation.html');
	}
	
	public function view_checkEmailIsExistPost() {
		echo $this->ajaxReturn(A('Public')->act_checkEmail());//验证邮箱是否已经注册
	}
}
?>