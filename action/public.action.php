<?php
/**
 * PublicAct
 * 功能：用于公共的ajax处理动作
 * @author wcx
 * v 1.0
 * 2014/06/26 
 */
class PublicAct extends CheckAct {
	
	public function __construct(){
		parent::__construct();
	}
	
	//验证验证码
	public function act_checkCode () {
		$checkCode = isset($_REQUEST['checkCode']) ? $_REQUEST['checkCode'] : '';
		if($checkCode==$_COOKIE['verifycode']) {
			return true;
		} else {
			self::$errMsg[10005] = get_promptmsg(10005,"验证码");//验证码错误
			return false;
		}
	}
	
	/*
	 * 发送验证邮箱
	 * wcx
	 */
	public function act_sendEmail($email,$flag=''){
		if(!$this->act_checkEmailStr($email)){
			return false;
		}
		$dataCount = M("User")->getDataCount("email = '{$email}'");
		if(!$dataCount){
			self::$errMsg['10002']	= get_promptmsg("10002","该邮箱");
			return false;
		}
		$toEmail = array(
				'0'    =>  array('email' => $email, 'userName' => '分销商'),
		);
		$title		= '维库卖家平台 邮箱验证！ ';
		if($flag == 'checkPassword') {
			$content	= '<a target="_blank" style="color: #006699;word-wrap: break-word;table-layout:fixed;" href="'.WEB_URL.'login/newPwd/sendTime/'.time().'/flag/checkPassword/email/'.$email.'/auth/'.substr(md5(md5($email)), 0,16).'".>'.WEB_URL.'login/newPwd/sendTime/'.time().'/flag/checkPassword/email/'.$email.'/auth/'.substr(md5(md5($email)), 0,16).'</a>';
			$content	= '<p>您好，您于 '.date("Y-m-d H:i:s",time()).' 在维库卖家平台操作<font color="red">密码修改验证</font>，系统自动为您发送了这封邮件</p><p>您可以点击以下链接验证，验证之后即可修改密码：</p><p style="word-wrap: break-word;">'.$content.'</p>';
		}elseif($flag == 'checkRegister'){
			$content	= '<a target="_blank" style="color: #006699;word-wrap: break-word;table-layout:fixed;" href="'.WEB_URL.'login/activeUser/sendTime/'.time().'/flag/checkRegister/email/'.$email.'/auth/'.substr(md5(md5($email)), 0,16).'".>'.WEB_URL.'login/activeUser/sendTime/'.time().'/flag/checkRegister/email/'.$email.'/auth/'.substr(md5(md5($email)), 0,16).'</a>';
			$content	= '<p>您好，您于 '.date("Y-m-d H:i:s",time()).' 在维库卖家平台操作<font color="red">注册账号</font>，系统自动为您发送了这封激活邮件</p><p>您可以点击以下链接验证，验证之后即可完成激活：</p><p style="word-wrap: break-word;">'.$content.'</p>';
		}else {
			self::$errMsg[10018] = get_promptmsg(10018,"邮件");
			return false;
		}
		$emailStyle = file_get_contents(WEB_PATH."html/template/v1/emailTemplates.html");
		$emailStyle = preg_replace('/\{title\}/i',$title,$emailStyle);
		$emailStyle = preg_replace('/\{content\}/i',$content,$emailStyle);
		$emailStyle = preg_replace('/\{webUrl\}/i',WEB_URL,$emailStyle);
		$emailStyle = preg_replace('/\{tips\}/i',"专为卖家服务，您的需求是我们努力方向！",$emailStyle);
		//实例化邮件对象
		include_once WEB_PATH.'lib/PHPMailer/sendEmail.php';
		$sendmail = sendEmail($toEmail, $title, $emailStyle);
		if(strlen($sendmail) > 1) {		//如果邮件发送失败，则将错误信息返回到$sendmail变量内，
			self::$errMsg[20006] = get_promptmsg(20006);
			return false;
		}
		if($flag == 'checkPassword'){
		    self::$errMsg[200] = get_promptmsg(10017,"忘记密码");
		}elseif($flag == 'checkRegister'){
		    self::$errMsg[200] = get_promptmsg(10017,"激活");
		}
		return true;
	}
	
	/**
	 * 功能：获取平台运输方式
	 * wcx
	 */
	public function act_getPlatformCarrier($platformId){
		if(empty($platformId)){
			self::$errMsg[10007]   =   get_promptmsg(10007,"平台ID");
			return false;
		}
		$res = M("PlatformCarrier")->getAllData("*","platformId = {$platformId}");
		if(empty($res)){
			self::$errMsg[10007]	= get_promptmsg(10007,"平台运输方式");
			return false;
		}
		return $res;
	}

	
	
}
?>