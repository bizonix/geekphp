<?php
/**
 * 类名：registerAct
 * 功能：登录
 * 版本：v1.0
 * 作者：wcx
 * 时间：2014/06/24
 * errCode：1060
 */
class RegisterAct extends CheckAct {
	
	public function __construct(){
		parent::__construct();
	}
	//获取地址列表信息
	public function act_register() {
		$email			=	isset($_REQUEST['email']) ? trim($_REQUEST['email']) : '';
		$userName		=	isset($_REQUEST['userName']) ? trim($_REQUEST['userName']) : '';
		$password		=	isset($_REQUEST['userPwd']) ? $_REQUEST['userPwd'] : '';
		$userPwdRetype	=	isset($_REQUEST['userPwdRetype']) ? $_REQUEST['userPwdRetype'] : '';
		$checkCode		=	isset($_REQUEST['checkCode']) ? $_REQUEST['checkCode'] : '';
		//验证字符合法
		if(!$this->act_checkUserName($userName) || !$this->act_checkEmailStr($email)){
			return false;
		}
		if($password != $userPwdRetype || !$password){
			self::$errMsg['20007'] = get_promptmsg(20007);
			return false;
		}
		if($checkCode != $_SESSION["verifycode"] || empty($checkCode)){
			self::$errMsg['10005'] = get_promptmsg(10005,"验证码");
			return false;
		}
		$userMod		=	M('User');
		$userDatas		=	$userMod->getData("*","email = '{$email}' or user_name = '{$userName}'");
		if(count($userDatas) == 0){
			//在平台增加用户
			$userData = array(
				'email'       => trim($email),  //邮箱
				'user_name'   => trim($userName),  //邮箱
				'user_pwd'    => md5(md5(trim($password))), //登录密码				
				'update_time' => time(), //更新时间
				'add_time' 	  => time(), //创建时间
				'status'	  => 0
			);
			$addUser = $userMod->insertData($userData);
			if(!empty($addUser)){
				//发送邮件
				$send = A("Public")->act_sendEmail($email,'checkRegister');
				if($send){
					self::$errMsg[200] = get_promptmsg(200,"注册");
					return true;
				}else{
					self::$errMsg[20008] = get_promptmsg(20008);
					return true;
				}
			}else{
				self::$errMsg[10001] = get_promptmsg(10001,"注册");
				return false;
			}
		}else{
			self::$errMsg[10003] = get_promptmsg(10003,"该邮箱或用户名");
			return false;
		}
	}

	/*
	 * 验证用户的用户名和邮箱是否可用
	 * wcx
	 */
	public function act_check(){
		$email 		= @$_REQUEST['email'];
		$userName 	= @$_REQUEST['userName'];
		if(empty($email) && empty($userName)){
			self::$errMsg[20004] = get_promptmsg(20004);
			return false;
		}
		$pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
		if ( !empty($email) && !preg_match( $pattern, $email ) ){
			self::$errMsg[10009] = get_promptmsg(10009,"邮箱");
			return false;
        }
		if(!empty($email)){
			$where	= "email = '{$email}'";
		}
		if(!empty($userName)){
			$where	= "user_name = '{$userName}'";
		}
		$userDatas		=	M("User")->getData("*",$where);
		if(count($userDatas) == 0){
			self::$errMsg[200] = get_promptmsg(200,"验证");
			return true;
		}else{
			self::$errMsg[10003] = get_promptmsg(10003,empty($email) ? "用户名" : "邮箱");
			return false;
		}
	}


}