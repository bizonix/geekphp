<?php
/**
*类名：Auth
*功能：与鉴权系统交互
*作者：冯赛明
*版本：V1.5
*最后修改时间：2013-11-4
*/

class Auth{
	//private static $actionURL= 'http://localhost/html/api/json.php';//本地环境
	//private static $actionURL  = 'http://power.valsun.cn/api/json.php';//	
	private static $actionURL  = 'http://power.valsun.cn/api/json.php';//192.168.200.122测试环境
	//private static $actionURL  = 'dev.power.valsun.cn/api/json.php';//192.168.200.222开发环境	
	private static $systemName = 'Ordermanage';//接口系统名称  类型：string	
	private static $token      = 'eccd25ddf4cddea9c46cf77fb6d78fa4';//类型：string
	//private static $systemName = 'Oversea';//接口系统名称  类型：string	
	//private static $token      = '9973cdbddab4c0fbbe9d733b33ef6aa0';//类型：string
	public static $errCode	   = "0";
	public static $errMsg	   = "";
	
	public function __construct()
	{
	}
	
	/*
	*功能：1、用户远程登录
	*参数为:用户名、密码、所属公司编号
	*/
	public static function login($user_name='',$pwd='',$companyId='')
	{   
		$pwd       = self::strToHex($pwd);
		$params = 'mod=LoginAct&act=login&userName='.$user_name.'&pwd='.$pwd.'&companyId='.$companyId;  
		$data=self::http(self::$actionURL,$params);  
		if(!$data)
		{
			self::$errCode='0001';
			self::$errMsg ='Login error';			
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		return $data;		
	}
	
	/*
	*功能：2、通过用户的token获取其操作权限
	*/
	public static function	getAccess($userToken='')
	{
		$data=Auth::getUserInfo($userToken);
		$result=json_decode($data,true);
		if(!empty($result['power']))
		{
			return json_encode($result['power']);
		}
		self::$errCode='0002';
		self::$errMsg ='Get access error';		
		return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
	}	
	
	/*
	*功能：3、获取某个用户信息
	*/
	public static function getUserInfo($userToken='')
	{
		$param='mod=UserAct&act=getUserInfo&userToken='.$userToken;		
		$data=self::http(self::$actionURL,$param);
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode='0003';
			self::$errMsg ='Get user info error';			
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];			
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));				
		}
		return $data;
	}	
	
	/*
	*功能：4、获取你的系统所有用户信息
	*/
	public static function getAllUserInfo()
	{
		$param='mod=UserAct&act=getAllUserInfo';		
		$data=self::http(self::$actionURL,$param);
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode='0004';
			self::$errMsg ='Get all user info error';			
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		return $data;
	}
	
	/*
	*功能：5、修改用户信息
	*参数：新的用户信息、用户token
	*/
	public static function updateUserInfo($newInfo,$userToken)
	{
		$newInfo = self::strToHex($newInfo);
		$param='mod=UserAct&act=updateUserInfo&newInfo='.$newInfo.'&userToken='.$userToken;		
		$data=self::http(self::$actionURL,$param);
		$rt=json_decode($data,true);
		if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!$data)
		{   
			self::$errCode = '0005';
			self::$errMsg  = 'Update user info error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));		
		}
		return json_encode($data);
	}	
	
	/*
	*功能：6、新增用户信息（已经被弃用）
	*参数：新的用户信息
	*/
	public static function addApiUser($newInfo)
	{
		
		$param='mod=UserAct&act=addApiUser&newInfo='.$newInfo;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode='0006';
			self::$errMsg ='Add api user';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));				
		}
		return $data;
	}	
	
	/*
	*功能：7、删除用户信息（已经被弃用）
	*参数：要被删除的用户的token
	*/
	public static function deleteApiUser($userToken)
	{
		$param='mod=UserAct&act=deleteApiUser&userToken='.$userToken;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0007';
			self::$errMsg  = 'Delete api user error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));				
		}
		return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));
	}	
	
	/*
	*功能：8、获取系统所有菜单信息
	*/
	public static function getApiMenus()
	{
		$param='mod=MenuAct&act=getApiMenus';		
		$data=self::http(self::$actionURL,$param);
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode='0008';
			self::$errMsg ='Get api menus error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		return $data;
	}
	
	/*
	*功能：9、修改系统某个菜单信息
	*参数：新的菜单信息、要修改的菜单编号
	*/
	public static function updateApiMenus($newMenus,$menuId)//json格式string类型、int类型
	{
		$param='mod=MenuAct&act=updateApiMenus&newMenus='.$newMenus.'&menuId='.$menuId;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0010';
			self::$errMsg  = 'Update api menus error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		return $data;
	}
	
	/*
	*功能：10、获取部门信息
	*/
	public static function getApiDept($companyId)
	{
		$param='mod=DeptAct&act=getApiDept&companyId='.$companyId;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0011';
			self::$errMsg  = 'Get api dept error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return $data;
	}
	
	/*
	* 功能：11、获取岗位信息
	*/
	public static function getApiJob($deptId)
	{
		$param='mod=JobAct&act=getApiJob&deptId='.$deptId;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0012';
			self::$errMsg  = 'Get api job error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return $data;
	}	
	
	/*
	*功能：12、获取所有公司信息
	*/
	public static function getApiCompany()
	{
		$param='mod=CompanyAct&act=getApiCompany';		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0013';
			self::$errMsg  = 'Get api company error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return $data;
	}	
	
	/*
	*功能：13、增加岗位信息
	*/
	public static function addApiJob($newJob)
	{
		$param='mod=JobAct&act=addApiJob&newJob='.$newJob;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0014';
			self::$errMsg  = 'Add api job error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}				
		return $data;
	}	
	
	/*
	*功能：14、修改岗位信息
	*/
	public static function updateApiJob($newJob)
	{
		$param='mod=JobAct&act=updateApiJob&newJob='.$newJob;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0015';
			self::$errMsg  = 'Update api job error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));
	}	
	
	/*
	*功能：15、删除岗位信息
	*/
	public static function deleteApiJob($jobId)
	{
		$param='mod=JobAct&act=deleteApiJob&jobId='.$jobId;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0016';
			self::$errMsg  = 'Delete api job error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));
	}	
	
	/*
	*功能：16、修改岗位权限信息
	*/
	public static function updateApiJobPower($newJobpower)
	{
		$param='mod=JobPowerAct&act=updateApiJobPower&newJobpower='.$newJobpower;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0017';
			self::$errMsg  = 'Update api jobPower error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));
	}	
	
	/*
	*功能：17、增加岗位权限信息
	*/
	public static function addApiJobPower($newJobpower)
	{
		$param='mod=JobPowerAct&act=addApiJobPower&newJobpower='.$newJobpower;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0018';
			self::$errMsg  = 'Add api jobPower error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return $data;
	}	
	
	/*
	*功能：18、删除岗位权限信息
	*/
	public static function deleteApiJobPower($jobpowerId)
	{
		$param='mod=JobPowerAct&act=deleteApiJobPower&jobpowerId='.$jobpowerId;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0019';
			self::$errMsg  = 'Delete api jobPower error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));
	}
	
	/*
	*功能：19、获取单个岗位权限信息
	*/
	public static function getApiJobPower($jobpowerId)
	{
		$param='mod=JobPowerAct&act=getApiJobPower&jobpowerId='.$jobpowerId;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0020';
			self::$errMsg  = 'Get api jobPower error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return $data;
	}
	
	/*
	*功能：20、获取所有岗位权限信息
	*/
	public static function getAllApiJobPower()
	{
		$param='mod=JobPowerAct&act=getAllApiJobPower';		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0021';
			self::$errMsg  = 'Get all api jobPower error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return $data;
	}
	
	/*
	*功能：21、外接系统新增部门信息
	*/
	public static function addApiDept($newDept)
	{
		$param='mod=DeptAct&act=addApiDept&newDept='.$newDept;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0022';
			self::$errMsg  = 'Add api dept error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return $data;
	}
	
	/*
	*功能：22、外接系统修改部门信息
	*/
	public static function updateApiDept($newDept)
	{
		$param='mod=DeptAct&act=updateApiDept&newDept='.$newDept;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0023';
			self::$errMsg  = 'Update api dept error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));
	}
	
	/*
	*功能：23、外接系统删除部门信息
	*/
	public static function deleteApiDept($deptId)
	{
		$param='mod=DeptAct&act=deleteApiDept&deptId='.$deptId;		
		$data=self::http(self::$actionURL,$param); 
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0024';
			self::$errMsg  = 'Delete api dept error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));
	}
	
	/*
	*功能：24、外接系统获取统一用户信息
	*/
	public static function getApiGlobalUser($queryConditions)
	{		
		$queryConditions = json_encode($queryConditions);
		$queryConditions = self::strToHex($queryConditions);
		$param='mod=GlobalUserAct&act=getApiGlobalUser&queryConditions='.$queryConditions;		
		$data=self::http(self::$actionURL,$param);  
		$rt=json_decode($data,true);
		if(!$rt)
		{
			self::$errCode = '0025';
			self::$errMsg  = 'Get api global user error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return $data;
	}
	
	/*
	*功能：25、删除统一用户信息
	*/
	public static function deleteApiGlobalUser($userId)
	{
		$param='mod=GlobalUserAct&act=deleteApiGlobalUser&userId='.$userId;		
		$data=self::http(self::$actionURL,$param); //echo $data;
		$rt=json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0026';
			self::$errMsg  = 'Delete api global user error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return json_encode(array('errCode'=>0,'errMsg'=>''));
	}
	
	/*
	*功能：26、增加统一用户信息
	*/
	public static function addApiGlobalUser($newInfo)
	{   
		$newInfo = self::strToHex($newInfo);
		$param = 'mod=GlobalUserAct&act=addApiGlobalUser&newInfo='.$newInfo;		
		$data = self::http(self::$actionURL,$param); 
		$rt = json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0027';
			self::$errMsg  = 'Add api global user error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return $data;
	}
	
	/*
	*功能：27、修改统一用户信息
	*/
	public static function updateApiGlobalUser($newInfo)
	{   
		$newInfo = self::strToHex($newInfo);
		$param = 'mod=GlobalUserAct&act=updateApiGlobalUser&newInfo='.$newInfo;		
		$data = self::http(self::$actionURL,$param); 
		$rt = json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0028';
			self::$errMsg  = 'Update api global user error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return $data;
	}
	
	/*
	*功能：28、获取岗位为“采购”的用户(朱清庭单独提出的需求2013-10-14)
	*/
	public static function getApiPurchaseUsers()
	{
		$param = 'mod=GlobalUserAct&act=getApiPurchaseUsers';		
		$data = self::http(self::$actionURL,$param); //echo $data;
		$rt = json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0028';
			self::$errMsg  = 'Get api purchase user error';
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return json_encode($rt);
	}
	
	/*
	*功能：29、获取用户登录记录信息
	*/
	public static function getApiSession($queryConditions)
	{
		$param = 'mod=SessionAct&act=getApiSession&queryConditions='.$queryConditions;		
		$data = self::http(self::$actionURL,$param); 
		$rt = json_decode($data,true);
		if(!$data)
		{
			self::$errCode = '0029';
			self::$errMsg  = 'Get api session error';		
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}
		else if(!empty($rt['errCode']) and $rt['errCode']>'0')
		{   
			self::$errCode = $rt['errCode'];
			self::$errMsg  = $rt['errMsg'];	
			return json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg));	
		}		
		return json_encode($rt);
	}
	
	public function __call($name,$params)
	{
		echo '<br/>你调用的方法'.$name.'不存在<br/>';
	}
	
	public static function __callStatic($name,$params)
	{
		echo '<br/>你调用的方法'.$name.'不存在<br/>';
	}
	
	public function __get($var)
	{
		echo '<br/>你调用的属性'.$var.'不存在<br/>';
	}
	
	/*
	*方法功能：字符串转换为16进制显示
	*/
	public static function strToHex($string)   
	{   
	  $hex="";   
	  for($i=0;$i<strlen($string);$i++)   
	  $hex .= dechex(ord($string[$i]));   
	  $hex  = strtoupper($hex);   
	  return $hex;   
	} 
	
	/*
	*方法功能：对参数实行md5加密
	*/
	public static function http($url,$urlPost)
	{
		$urlPost.='&systemName='.self::$systemName;//加上系统名称
		$key=md5('json.php?'.$urlPost.self::$token);//对参数加密
	    $urlPost.='&key='.$key;
		//$urlPost = self::strToHex($urlPost);
		return(self::curl($url,$urlPost));		
	}
	
	/*
	*方法功能：远程传输数据
	*/
	public static function curl($url,$urlPost)
	{   
		$curl = curl_init();
		curl_setopt($curl,CURLOPT_URL,$url);//设置你要抓取的URL
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);//设置CURL参数，要求结果保存到字符串还是输出到屏幕上
		curl_setopt($curl,CURLOPT_POST,1);//设置为POST提交
		curl_setopt($curl,CURLOPT_POSTFIELDS,$urlPost);//提交的参数
		$data=curl_exec($curl);//运行CURL，请求网页
		curl_close($curl);
		if($data)
		{
			return $data;
		}
		return false;			
	}	
	
	/*
	*功能：检查登录的用户是否拥有某个操作权限
	*参数：类名称、方法名称
	*/
	public static function	checkAccess($mod,$act)//string，string
	{	
		if(self::checkLogin())//判断用户是否登录
		{
			$userToken=$_SESSION['userToken'];
			$data=Auth::getAccess($userToken);
			$data=json_decode($data,true); 
			if(isset($data[$mod]))//判断用户传值过来的操作组名(也既是类名)是否存在
			{
				if(in_array($act,$data[$mod]))//判断用户的操作是否存在
				{
					return true;
				} else {
					self::$errCode = "0012";
					self::$errMsg  = "No power to access: ".$mod."->".$act;
				} 
			}else
			{
				self::$errCode = "0013";
				self::$errMsg  = "No ActionGroup ".$mod."->".$act;
			}
		}	
		return false;		
	}	
	
	/*
	*功能：判断用户是否登录，或者登录是否失效
	*/
	public static function checkLogin()
	{	
		if(!isset($_SESSION['userToken']))
		{
			self::$errCode = "0014";
			self::$errMsg  = "Please login first";
			self::showError();
			return false;
		}	
		return true;		
	}
	
	/*
	*功能：输出错误
	*/
	public static function showError(){
		//echo (json_encode(array('errCode'=>self::$errCode,'errMsg'=>self::$errMsg)));
	}
	
	/*
	*用户登出
	*/
	public static function loginOut()
	{
		session_destroy();
	}
}
?>