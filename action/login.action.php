<?php
/**
 * 类名：LoginAct
 * 功能：登录
 * 版本：v1.0
 * 作者：邹军荣
 * 时间：2014/06/25
 * errCode：
 */
class LoginAct extends CheckAct {
	public function __construct(){
		parent::__construct();
	}

	/*
	 * 获取地址列表信息
	 */
	public function act_login() {
        
		$loginName		=	isset($_REQUEST['loginName']) ? trim($_REQUEST['loginName']) : '';
		$loginPwd		=	isset($_REQUEST['loginPwd']) ? trim($_REQUEST['loginPwd']) : '';

		if(stripos($loginName, "@") && stripos($loginName, ".")){
            if(!$this->act_checkEmailStr($loginName)) return false;
			$where	= "email = '{$loginName}'";
		}else{
            if(!$this->act_checkUserName($loginName)) return false;
			$where	= "user_name = '{$loginName}'";
		}
        $userMod        =   M('User');
		$where	.= ' and user_pwd = "'.md5(md5($loginPwd)).'"';
		//获取erp_account的做大值
		$datas		=	$userMod->getData("*",$where," order by id desc ",1,1);
		if (!empty($datas)) {
			$userInfo		=	$datas[0];
			if($userInfo['user_pwd'] == md5(md5($loginPwd))){
				$this->act_registerLoginInfo($userInfo);
				self::$errMsg[200] = get_promptmsg(200,'登录');
				return true;
			}else{
				self::$errMsg[10005] = get_promptmsg(10005,'密码');
				return false;
			}
		}else {
		    self::$errMsg[20003] = get_promptmsg(20003);
		    return false;
		}
	}

    /*
     * 注册登录信息至cookie和session
     */
    public function act_registerLoginInfo($userInfo){
        $dpInfo     =   array(
                "id"                =>  $userInfo['id'],
                "user_name"         =>  $userInfo['user_name'],
                "email"             =>  $userInfo['email'],
                "status"            =>  $userInfo['status'],
                "type"              =>  $userInfo['type'],
                "level"             =>  $userInfo['level'],
                "company_id"        =>  empty($userInfo['company_id']) ? 0 : $userInfo['company_id'],
        );
        setcookie('user',_authcode(json_encode($dpInfo),'ENCODE'),0,"/");
        $_SESSION['loginStatus'] = "in";
    }
    
    /*
     * 验证的邮箱的动作
     * zjr
     */
    public function act_checkEmail(){
    	$email		= isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
    	$auth 		= isset($_REQUEST['auth']) ? $_REQUEST['auth'] : '';
    	$sendTime 	= isset($_REQUEST['sendTime']) ? $_REQUEST['sendTime'] : 0;
    	$flag 	 	= isset($_REQUEST['flag']) ? $_REQUEST['flag'] : '';
    	if($flag == 'checkPassword'){
    		if((time()-$sendTime)/(3600*24)>1) return 2;
	    	if($email&&$auth){
	    		if(substr(md5(md5($email)), 0,16)==trim($auth)){
	    			setcookie('email', $email,time()+3600,"/");
	    			setcookie('auth', $auth,time()+3600,"/");
	    			setcookie('sendTime', $sendTime,time()+3600,"/");
	    			return 1;    //邮件验证成功
	    		}
	    	}
    	}elseif($flag == 'checkRegister'){
    		if($email&&$auth){
    			if(substr(md5(md5($email)), 0,16)==trim($auth)){
    				$acticeStatus = M('User')->updateDataWhere(array("status"=>1),array("email"=>$email));
    				if($acticeStatus > 0) {
    					return 11;    //邮件激活成功
    				}else {
    					return 12;   //邮件激活失败
    				}
    			}
    		}
    	}else{
    		return 4; 		// "未知邮件验证类型，无法完成操作！";
    	}
    	return 0;			// 操作失败，未知原因！
    }
    
    /*
     * 修改密码的动作
     * zjr
     */
    public function act_updatePwd(){
    	$newPwd		        = isset($_REQUEST['newPwd']) ? $_REQUEST['newPwd'] : '';
        $newPwdRetype       = isset($_REQUEST['newPwdRetype']) ? $_REQUEST['newPwdRetype'] : '';
    	$email				= isset($_COOKIE['email']) ? $_COOKIE['email'] : '';
    	$auth 				= isset($_COOKIE['auth']) ? $_COOKIE['auth'] : '';
    	$sendTime 			= isset($_COOKIE['sendTime']) ? $_COOKIE['sendTime'] : 0;
        if(!$newPwd || $newPwd != $newPwdRetype){
            self::$errMsg['20007'] = get_promptmsg(20007);
            return false;
        }
    	if((time()-$sendTime)/(3600*24)>1) {
            self::$errMsg['20011'] = get_promptmsg(20011);
            return false;
        }
    	if($email&&$auth){
    		if(substr(md5(md5($email)), 0,16) == trim($auth)){
    			$userInfor = M('User')->getData("*","email = '".$email."'");
    			M('User')->begin();
    			$updatePwd = M('User')->updateDataWhere(array("user_pwd"=>md5(md5($newPwd))),array("email"=>$email));
    			if($updatePwd) {
					M('User')->commit();
                    self::$errMsg['200'] = get_promptmsg(200);
					return true;
				}else{
                    M('User')->rollback();
                    self::$errMsg['1000'] = get_promptmsg(1000);
                    return false;
				}
    		}
    	}
        self::$errMsg['20012'] = get_promptmsg(20012);
        return false;
    }
    
    /*
     * 修改密码的动作
     * zjr
     */
    public function act_acitveUser(){
    	$userpassword		= isset($_REQUEST['userpassword']) ? $_REQUEST['userpassword'] : '';
    	$email				= isset($_COOKIE['email']) ? $_COOKIE['email'] : '';
    	$auth 				= isset($_COOKIE['auth']) ? $_COOKIE['auth'] : '';
    	$sendTime 			= isset($_COOKIE['sendTime']) ? $_COOKIE['sendTime'] : 0;
    	if((time()-$sendTime)/(3600*24)>1) return 2;
    	if($email&&$auth){
    		if(substr(md5(md5($email)), 0,16)==trim($auth)){
    			$updatePwd = M('Developer')->updateDataByColumn("email",$email,array("login_pwd"=>md5(md5($userpassword))));
    			if($updatePwd > 0) {
    				return 1;
    			}else{
    				return 3;
    			}
    		}
    	}
    	return 0;
    }
    
    /*
     * 验证是否已经登录
     * zjr
     */
    public function act_checkLogin(){
    	if($_SESSION['loginStatus'] == "out"){
    		return "out";
    	}else{
    		$_SESSION['loginStatus'] = "in";
    		return $_COOKIE['now_url'];
    	}
    }
    
    /*
     * 退出登录操作
     * zjr
     */
    public function act_logout(){
    	setcookie('user','',0,"/");
    	setcookie('adminUser','',0,"/");
    	$_SESSION['loginStatus'] = "out";
    }
}