<?php
/*
 *鉴权系统相关接口操作类(model)
 *@add by : linzhengxiang ,date : 20140528
 */
defined('WEB_PATH') ? '' : exit;
class InterfacePowerModel extends InterfaceModel {
	
	public function __construct(){
		parent::__construct();
	}
	   
	/**
	 * 用户登录走开放系统
	 * @param string $username
	 * @param string $password
	 * @return array
	 * @author lzx
	 */
    public function userLogin($username, $password){
    	$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['user_name'] 	= $username;
		$conf['pwd'] 		= $password;
		$conf['sysName'] 	= C('AUTH_SYSNAME');
		$conf['sysToken'] 	= C('AUTH_SYSTOKEN');
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if (isset($data['errCode'])&&$data['errCode']>0) {
			self::$errMsg[$data['errCode']] = "{$data['errMsg']}";
			return false;
		}else{
			return $data;
		}
	}
	
	/**
	 * 根据统一用户编号给鉴权系统，返回用户相关信息
	 * @param int $userId: 统一用户编号
	 * @return array
	 * @author lzx
	 */
	public function getUserInfo($userId){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['queryConditions'] = json_encode(array('userId' => intval($userId)));
		$conf['sysName'] 		 = C('AUTH_SYSNAME');
		$conf['sysToken'] 		 = C('AUTH_SYSTOKEN');
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if (isset($data['errCode'])&&$data['errCode']>0) {
			self::$errMsg[$data['errCode']] = "{$data['errMsg']}";
			return false;
		}else{
			return $data[0];
		}
	}
	
	/**
	 * 根据统一用户编号给鉴权系统，返回用户相关信息
	 * @param int $userId: 统一用户编号
	 * @return array
	 * @author lzx
	 */
	public function getUserPower($usertoken){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['userToken'] = $usertoken;
		$conf['sysName']   = C('AUTH_SYSNAME');
		$conf['sysToken']  = C('AUTH_SYSTOKEN');
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if (isset($data['errCode'])&&$data['errCode']>0) {
			self::$errMsg[$data['errCode']] = "{$data['errMsg']}";
			return false;
		}else{
			return $data['power'];
		}
	}
	
	/**
	 * 根据统一用户编号获取该用户权限下面的所有用户列表
	 * @param int $userId: 统一用户编号
	 * @param int $page
	 * @param int $num
	 * @return array
	 * @author lzx
	 */
	public function getUserList($userid, $page=1, $num=200){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['global_user_id'] = $userid;
        /*接口目前只支持返回全部，自己根据全部数据分页
		$conf['page']   		= $page;
		$conf['num']  			= $num;
        **/
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if (isset($data['errCode'])&&$data['errCode']>0) {
			self::$errMsg[$data['errCode']] = "{$data['errMsg']}";
			return false;
		}else{
			return $data;
		}
	}
    
    /**
	 * 修改用户统一密码密码
	 * @param $userId1 修改人Id
     * @param $userId2 被修改人Id
     * @param $userId2 密码
	 * @return array
	 * @author lzx
	 */
    public function userUpdatePsw($userId1, $userId2, $psw){
    	$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['userId1'] = $userId1;
		$conf['userId2'] = $userId2;
		$conf['psw']     = $psw;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
        if(!empty($data)){
            return $data;
        }else{
            return false;
        }
		
	}
	
	/**
	 * 新增用户走开放系统
	 * @param array $newuser
	 * @return array
	 * @author lzx
	 */
    public function userInsert($newuser){		
    	$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['newInfo']   = $newuser;
		$conf['action']    = 'addApiUser';
		$conf['sysName']   = C('AUTH_SYSNAME');
		$conf['sysToken']  = C('AUTH_SYSTOKEN');
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if (isset($data['errCode'])&&$data['errCode']>0) {
			self::$errMsg[$data['errCode']] = "{$data['errMsg']}";
			return false;
		}else{
			return $data;
		}
	}
	
	/**
	 * 新增用户走开放系统
	 * @param array $newuser
	 * @return array
	 * @author lzx
	 */
    public function userUpdate($newuser, $userToken){
    	$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['newInfo']   = $newuser;
		$conf['action']    = 'updateUserInfo';
		$conf['userToken'] = $userToken;
		$conf['sysName']   = C('AUTH_SYSNAME');
		$conf['sysToken']  = C('AUTH_SYSTOKEN');
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if (isset($data['errCode'])&&$data['errCode']>0) {
			self::$errMsg[$data['errCode']] = "{$data['errMsg']}";
			return false;
		}else{
			return $data;
		}
	}
	
	/**
	 * UserModel::userDelete()
	 * 删除用户走开放系统
	 * add by 管拥军 2013-08-23
	 * @return  bool
	 */
    public function userDelete($userToken){
    	$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['userToken'] = $userToken;
		$conf['sysName']   = C('AUTH_SYSNAME');
		$conf['sysToken']  = C('AUTH_SYSTOKEN');
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if (isset($data['errCode'])&&$data['errCode']>0) {
			self::$errMsg[$data['errCode']] = "{$data['errMsg']}";
			return false;
		}else{
			return $data;
		}
	}
	/**
	 * 新增用户走开放系统
	 * @param array $newDevelop
	 * @return array
	 * @author wcx
	 */
	public function addDevelopToPower($companyName,$principal,$address,$phone,$companyEnName,$email,$userPsd,$type="all"){
	    $conf = $this->getRequestConf(__FUNCTION__);
	    if (empty($conf)){
	        return false;
	    }
	    $conf['companyName']   = $companyName;
	    $conf['principal']     = $principal;
	    $conf['address']       = $address;
	    $conf['phone']         = $phone;
	    $conf['companyEnName'] = $companyEnName;
	    $conf['email']         = $email;
	    $conf['loginPsd']      = $userPsd;
	    $conf['type']          = $type;
	    $conf['cachetime']     = 0;
	    $result = callOpenSystem($conf);
	    $data   = json_decode($result,true);
	    
	    if (isset($data['errCode'])&&$data['errCode']>0) {
	        self::$errMsg[$data['errCode']] = "{$data['errMsg']}";
	        return false;
	    }else{
	        return $data;
	    }
	}
	
	/**
	 * 获取鉴权系统公司信息
	 * @param array $newDevelop
	 * @return array
	 * @author wcx
	 */
	public function getCompanyInfo(){
	    $conf = $this->getRequestConf(__FUNCTION__);
	    if (empty($conf)){
	        return false;
	    }
	    $conf['sysName']   = C('AUTH_SYSNAME');
	    $conf['sysToken']  = C('AUTH_SYSTOKEN');
	    $result = callOpenSystem($conf);
	    $data = json_decode($result,true);
	    return $this->changeArrayKey($data);
	   
	}
	
	/**
	 * 修改用户统一密码密码
	 * @param $loginName 被修改的登录名
	 * @param $psw 密码
	 * @param $version 修改版本  目前版本为 1.0
	 * @return array
	 * @author zjr
	 */
	public function updateGlobalUserPsw($loginName, $psw, $version='1.0'){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['version'] 	= $version;
		$conf['loginName'] 	= $loginName;
		$conf['userPwd']    = $psw;
		$conf['sysName']    = C('AUTH_SYSNAME');
	    $conf['sysToken']   = C('AUTH_SYSTOKEN');
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if(!empty($data)){
			return $data;
		}else{
			return false;
		}
	
	}
	
	/**
	 * 根据统一用户登录邮箱给鉴权系统，返回用户相关信息
	 * @param int $userLoginEmail: 统一用户邮箱
	 * @return array
	 * @author zjr
	 */
	public function getUserInfoByLoginEmail($userLoginEmail){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['queryConditions'] = json_encode(array('loginName' => $userLoginEmail));
		$conf['sysName'] 		 = C('AUTH_SYSNAME');
		$conf['sysToken'] 		 = C('AUTH_SYSTOKEN');
		$conf['cachetime'] 		 = 0;
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if (isset($data['errCode'])&&$data['errCode']>0) {
			self::$errMsg[$data['errCode']] = "{$data['errMsg']}";
			return false;
		}else{
			return $data[0];
		}
	}
	
	/**
	 * 获取鉴权中所有公司账号信息
	 * @return array
	 * @author zjr
	 */
	public function getAllCompanyInfo(){
		$conf = $this->getRequestConf(__FUNCTION__);
		if (empty($conf)){
			return false;
		}
		$conf['cachetime'] 		 = 0;
		$conf['sysName'] 		 = C('AUTH_SYSNAME');
		$conf['sysToken'] 		 = C('AUTH_SYSTOKEN');
		$result = callOpenSystem($conf);
		$data = json_decode($result,true);
		if (isset($data['errCode'])&&$data['errCode']>0) {
			self::$errMsg[$data['errCode']] = "{$data['errMsg']}";
			return false;
		}else{
			return $data;
		}
	}
	
	/**
	 * @description 修改鉴权系统账号信息
	 * @param array $userInfo 用户信息
	 * @return bool
	 * @author lzj
	 */
	public function updateUserStatus($email, $status, $unsign_id) {
		$conf = $this->getRequestConf(__FUNCTION__);
		if(empty($conf)) return false;
		$conf['email']		= isset($email) ? $email : '';
		$conf['status']		= isset($status) ? $status : '1';
		$conf['unsign_id']	= isset($unsign_id) ? $unsign_id : '0';
		$conf['sysName']	= C('AUTH_SYSNAME');
		$conf['sysToken']	= C('AUTH_SYSTOKEN');
		$result				= callOpenSystem($conf);
		$data				= json_decode($result, true);
		if(isset($data['errCode']) && $data['errCode'] > 0) {
			self::$errMsg[$data['errCode']] = "{$data['errMsg']}";
		}
		return $data;
	}
	
	/**
	 * @description 按公司岗位新添用户
	 * @author lzj
	 */
	public function addApiNewUser($email, $paw, $phone, $username, $system_id, $company_id, $job_id, $dept_id) {
		$conf = $this->getRequestConf(__FUNCTION__);
		if(empty($conf)) return false;
		$conf['email']		= isset($email) ? $email : '';
		$conf['paw']		= isset($paw) ? $paw : '';
		$conf['phone']		= isset($phone) ? $phone : '';
		$conf['user_name']	= isset($username) ? $username : '';
		$conf['system_id']	= isset($system_id) ? $system_id : 0;
		$conf['company_id']	= isset($company_id) ? $company_id : 0;
		$conf['job_id']		= isset($job_id) ? $job_id : 0;
		$conf['dept_id']	= isset($dept_id) ? $dept_id : 0;
		
		$conf['cachetime'] 	= 0;
		$conf['sysName'] 	= C('AUTH_SYSNAME');
		$conf['sysToken'] 	= C('AUTH_SYSTOKEN');
		$result				= callOpenSystem($conf);
		$data				= json_decode($result, true);
		if(isset($data['errCode']) && $data['errCode'] > 0) {
			self::$errMsg[$data['errCode']] = "{$data['errMsg']}";
		}
		
		return $data;
	}
}
?>