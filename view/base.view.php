<?php
//view 层基类
class BaseView{

	protected $page 		= 1;
	protected $smarty		= '';
	protected $_username	= '';
	protected $_userid		= 0;
	protected $_companyid	= 0;
	protected $_systemid	= 0;
	protected $waitSecond	= 2;
	public function __construct(){
		@session_start();
		$mod	= @trim($_REQUEST['mod']);
		$act	= @trim($_REQUEST['act']);
		####################  smarty初始化 start ####################
		require(WEB_PATH.'lib/template/smarty/Smarty.class.php');
		$this->smarty = new Smarty;
		$this->smarty->template_dir = TPL_PATH.DIRECTORY_SEPARATOR;//模板文件目录
		$this->smarty->compile_dir 	= WEB_PATH.'smarty/templates_c'.DIRECTORY_SEPARATOR;//编译后文件目录
		$this->smarty->config_dir 	= WEB_PATH.'smarty/configs'.DIRECTORY_SEPARATOR;//配置文件目录
		$this->smarty->cache_dir 	= WEB_PATH.'smarty/cache'.DIRECTORY_SEPARATOR;//缓存文件目录
		$this->smarty->debugging 	= false;
		$this->smarty->caching 		= false;
		$this->smarty->cache_lifetime = 120;
		####################  smarty初始化  end ####################
		$user   =   @json_decode(_authcode($_COOKIE['user']),true);
		$loginName = '';
		if (isset($_REQUEST["PHPSESSID"])) {
		    session_id($_REQUEST["PHPSESSID"]);
		}else{
            //重新登录时,页面跳转到之前的页面
            if(!in_array($mod, array('login', 'logout', 'userLogin','index','register'))){
                if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) != "xmlhttprequest" && !in_array($_SERVER['REQUEST_URI'],array("","/"))){
                    $now_url= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; //记录当前页面url
                    setcookie('now_url', $now_url, time()+86400);
                }
            }
    		if (C('IS_AUTH_ON')===true){//权限控制
    		    if(empty($user)){
    		    	include_once WEB_PATH.'lib/class/authuser.class.php';
    		    	$_SESSION['loginStatus'] = "out";  //修改退出登录标志
    		    	//****判断登录
     		    	if (!AuthUser::checkLogin($mod, $act)){
    		    		redirect_to(WEB_URL."index/index");
     		    	}
    		    }
    		    if(!empty($user)){
    		        //前台登陆信息
    		        $loginName    =   $user['user_name'];
    		    }
            }else{
                $loginName    =   $user['user_name'];
            }
	        $this->smarty->assign(array(
	                "loginName"   =>  $loginName,
	        ));
	        //以下三个变量在登录成功的时候写入SESSION
			$this->_username	= isset($user['email']) ? $user['email'] :  "XX" ;//登录的中文名字
			$this->_userid		= isset($user['id']) ? $user['id'] : 0;
			//$this->_companyid	= isset($_SESSION['companyId']) ? $_SESSION['companyId'] : 0;
            $this->_companyid   = isset($user['company_id']) ? $user['company_id'] : 0;
			$this->_systemid	= '12';


			//初始化提交过来的变量（post and get） 用与搜索后条件不消失,或者表单信息不消失
			if (isset($_GET)){
				foreach ($_GET AS $gk=>$gv){
					$this->smarty->assign('g_'.$gk, $gv);
				}
			}
			if (isset($_POST)){
				foreach ($_POST AS $pk=>$pv){
					$this->smarty->assign('p_'.$pk, $pv);
				}
			}
			$this->smarty->assign('curusername',@$_SESSION['userName']); //设置当前用户名
	        $this->smarty->assign('mod', $mod);//模块权限
	        $this->smarty->assign('act', $act);//操作权限
            //$this->smarty->assign('TPL', $TPL);//操作权限
	        $this->smarty->assign('_username', $this->_username);//中文名字
			$this->smarty->assign('_userid', $this->_userid);//用户id
			$this->smarty->assign('loginStatus', @$_SESSION['loginStatus']);//用户登录状态

			//初始化当前页码
			$this->page = isset($_GET['page'])&&intval($_GET['page'])>0 ? intval($_GET['page']) : 1;
			$this->smarty->assign("page", $this->page);
		}
	}
    //收集action中的操作信息
    protected function collectMsg($prompt='auto'){
        $result = array();
        //设置成{"errCode":"","errMsg":"","data":""}这种格式
        if(isset($_REQUEST['errCode'])){
            $result['errCode'] = $_REQUEST['errCode'];
            $result['errMsg']  = get_promptmsg($_REQUEST['errCode']);
            return $result;
        }
        if($prompt=='auto'){
            $prompt    =   A('Common')->act_getErrorMsg();
            if(empty($prompt)){
                $result['errCode'] = "200";
                $result['errMsg']  = get_promptmsg(200);
            }
        }
        foreach ($prompt AS $key=>$msg){
         //只要其中有出现非200的就拦截
            if($key!='200'){
                $result['errCode'] = $key;
                $result['errMsg']  = $msg;
                break;
            }
            //只判断最后一个错误码

            $result['errCode'] = $key;
            $result['errMsg']  = $msg;
        }
        return $result;
    }
	//数据和错误信息返回
	protected function ajaxReturn($data='', $prompt='auto'){
		header('Content-Type:application/json; charset=utf-8');//设置格式
		$result = $this->collectMsg($prompt);
		$result['data'] = $data;
		exit(json_encode($result));
	}

	/**
     * 操作错误跳转的快捷方法
     * @param string $message 错误信息
     * @param string $jumpUrl 页面跳转地址
     * @param int $waitSecond 等待时间
	 * @param string $prefix 模版页前缀，用来区分前台后或其他专题页的不同样式
     * @return void
     * @author lzx
     */
	protected function error($message, $jumpUrl='', $waitSecond=3, $prefix = '') {
        $this->dispatchJump($message, 0, $jumpUrl, $waitSecond, $prefix);
    }

    /**
     * 操作成功跳转的快捷方法
     * @param string $message 提示信息
     * @param string $jumpUrl 页面跳转地址
     * @param int $waitSecond 等待时间
     * @return void
     * @author lzx
     */
    protected function success($message, $jumpUrl='', $waitSecond=3, $prefix = '') {
        $this->dispatchJump($message, 1, $jumpUrl, $waitSecond, $prefix);
    }

	/**
     * 无论操作是否成功都不跳转
     * @param string $message 提示信息
     * @param string $jumpUrl 页面跳转地址
     * @return void
     * @author lzx
     */
    protected function notJump($message,$jumpUrl='') {
        $this->dispatchJump($message,2,$jumpUrl);
    }

	/**
     * 默认跳转操作 支持错误导向和正确跳转
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     * @param string $message 提示信息
     * @param Boolean $status 状态
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @access private
     * @return void
     */
    private function dispatchJump($message,$status=1,$jumpUrl='', $waitSecond=3, $prefix) {
        if(!empty($jumpUrl)) $this->smarty->assign('jumpUrl',$jumpUrl);
        //如果设置了关闭窗口，则提示完毕后自动关闭窗口
        $this->smarty->assign('status',$status);   // 状态
        if($status==1) {
            $this->smarty->assign('message',$message);// 提示信息
            // 成功操作后默认停留1秒
            if(!isset($this->smarty->waitSecond))   $this->smarty->assign('waitSecond','3');
            // 默认操作成功自动返回操作前页面
            if(!isset($jumpUrl)) $this->smarty->assign("jumpUrl",$_SERVER["HTTP_REFERER"]);
            $this->smarty->display('success_jump.html');
        }else if($status==1){
        	$this->smarty->assign('message',$message);													// 提示信息
            if(!isset($jumpUrl)) $this->smarty->assign("jumpUrl",$_SERVER["HTTP_REFERER"]);				// 默认操作成功自动返回操作前页面
            $this->smarty->display($prefix.'success_jump.htm');
        }else{
			/* $this->smarty->assign('jumpUrl_index',$jumpUrl);	  add by yyn */
            $this->smarty->assign('error',$message);// 提示信息
            //发生错误时候默认停留3秒
            $this->smarty->assign('waitSecond', $waitSecond);
            // 默认发生错误的话自动返回上页
            if(!isset($jumpUrl)) $this->smarty->assign('jumpUrl',"javascript:history.back(-1);");
			 $this->smarty->display($prefix.'error_jump.html');
        }
        exit;			// 中止执行  避免出错后继续执行
    }
    /**
     * 函数说明：404页面跳转
     * @param string $message
     * @param string $jumpUrl
     * @param number $waitSecond
     */
    protected function error_404Jump($message,$jumpUrl='', $waitSecond=3){
    	@header("http/1.1 404 not found");
    	@header("status: 404 not found");
    	$this->smarty->assign('jumpUrl_index',$jumpUrl);	//**add by yyn**//
    	$this->smarty->assign('error',$message);// 提示信息
    	//发生错误时候默认停留3秒
    	$this->smarty->assign('waitSecond', $waitSecond);
    	// 默认发生错误的话自动返回上页
    	$this->smarty->assign('jumpUrl_back',"javascript:history.back(-1);"); //**add by yyn**//
    	$this->smarty->display('404.html');
    	exit;
    }
     /*
     * 根据当前view获取action
    */
    protected function getAction(){
    	return empty($this->defaultAction)?str_replace("View", "", get_class($this)):$this->defaultAction;
    }
    /*
     * 获取分页样式
    */
    protected function getPageformat($count=0,$perpage=0){
    	$pageclass =   new Page($count, $perpage, '', 'CN');
    	return $pageclass->fpage($count>$perpage ? array(0,4,5,6,7,8,9) : array(0,4));
    }
    /*
     *  列表显示和分页
    */
    public function view_listShow(){
    	$api       =   A($this->getAction());
    	$list      =   $api->act_getDataList();
    	$this->smarty->assign(array(
    			"list"         =>  $list,
    			"show_page"    =>  $this->getPageformat($api->act_getDataCount(),$api->act_getPerpage()),
    	));
    }
    /*
     * 更新数据
    */
    public function view_updateData(){
    	echo $this->ajaxReturn(A($this->getAction())->act_updateData($this->_param("id",0),$this->_allParam()),A($this->getAction())->act_getErrorMsg());
    }
    /*
     * 添加数据
    */
    public function view_addData(){
    
    	echo $this->ajaxReturn(A($this->getAction())->act_addData($this->_allParam()),A($this->getAction())->act_getErrorMsg());
    
    }
    /*
     * 删除数据
    */
    public function view_delData(){
    	echo $this->ajaxReturn(A($this->getAction())->act_delData($this->_param("id",0)),A($this->getAction())->act_getErrorMsg());
    }
    /*
     * 获取单个数据
    */
    public function view_getSingleData(){
        echo $this->ajaxReturn($this->_getList()[0],A($this->getAction())->act_getErrorMsg());
    }
    public function _getList(){
        return A($this->getAction())->act_getSingleData($this->_param("id",0));
    }
    /*
     * 设置默认调用action
    */
    public function view_setAction($action){
    	$this->defaultAction	=	$action;
    }
	/**
	 * @description 获取请求方法
	 * @param string $name 传入的POST或GET
	 * @param any $defaultValue 当请求为空时可选默认值 
	 * @author lzj
	 * @example Http::getParam('name') 处理重复判断请求是否为空的
	 */
	public function getParam($name, $defaultValue = null) {
		return isset($_GET[$name]) ? $_GET[$name] : (isset($_POST[$name]) ? $_POST[$name] : $defaultValue);
	}
	/**
	 * @description 判断是否为AJAX
	 * @return bool 返回真或假
	 * @author lzj
	 */
	public function isAjax() {
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
	}

    /**
     * @description 检测必填参数是否填写
     * @param [unkowm] [$value] [要检测的值]
     * @param [string] [$msg] [为空时返回错误信息值]
     * @author zjr
     */
    public function must($value,$msg,$type='string') {
        if(!empty($value)){
            return $value;
        }else{
            $result['errCode']  = '10007';
            $result['errMsg']   = get_promptmsg('10007',$msg);
            $result['data']     = '';
            echo json_encode($result);exit;
        }
    }

    /**
     * 
     */
    public function showOperateRes(){
        $result = $this->collectMsg();
        if($result['errCode'] == '200'){
            $this->smarty->assign("o_success",$result['errMsg']);
        }else{
            $this->smarty->assign("o_error",$result['errMsg']);
        }
    }

    
    private function _filtParam($info){
        if(is_array($info)){
            array_walk_recursive($info,function(&$item,$key){
                $item = htmlspecialchars($item);
            });
        }else{
            $info = htmlspecialchars($info);
        }
        return $info;
    }
    /**
     * @desc Returns the named GET or POST parameter value. <br/>
     * If the GET or POST parameter does not exist, <br/>
     * the second parameter to this method will be returned. <br/>
     * If both GET and POST contains such a named parameter, the GET parameter takes precedence.
     * @param unknown $name
     * @param string $defaultValue
     * @return mixed
     */
    protected function _param($name,$defaultValue=null){
        $info = isset($_GET[$name]) ? $_GET[$name] : (isset($_POST[$name]) ? $_POST[$name] : $defaultValue);
        return $this->_filtParam($info);
    }
    protected function _allParam($type="post"){
        if($type=='post') {
            $info = $_POST;
        }else{
            $info = $_GET;
        } 
        if(isset($info['id'])){
            unset($info['id']);
        }
        foreach($info as $k=>$v){
            $info[$k] = $this->_filtParam($v);
        }
        return $info;
    }
    /**
     * @desc post方式获取数据并过虑
     * @param string $name
     * @param string $defaultValue
     * @return mixed
     */
    protected function _post($name, $defaultValue=NULL){
        $info = isset($_POST[$name]) ? $_POST[$name] : $defaultValue;
        return $this->_filtParam($info);
    }
    /**
     * @desc get方式获取数据并过虑
     * @param $name
     * @param null $defaultValue
     * @return array|string
     */
    protected function _get($name, $defaultValue=NULL){
        $info = isset($_GET[$name]) ? $_GET[$name] : $defaultValue;
        return $this->_filtParam($info);
    }

    /**
     * @desc 获取查询条件
     * @param array(key,key)or array(array(key,default,$value,$opration),array(key,default,$value,$opration))
     * @return string
     */
    protected function _getCondition($arr = array(),$flag = true){
        if(empty($arr)) return array();
        $where = array();
        foreach($arr as $k=>$v){
            if(!is_array($v)){
                $key = $v;
                $value = $v;
                $default = '';
                $op = '$e';
            }else{
                $key = $v[0];
                $default = $v[1];
                $value = isset($v[2])?$v[2]:$v[0];
                $op = isset($v[3])?$v[3]:'$e';
            }
            
            if(!$flag||$default!=''){
                $where[$value][$op] = $this->_param($key,$default);
            }else{
                if($this->_param($key)!=''){
                    $where[$value][$op] = $this->_param($key);
                }
            }
        }
        return implode(" AND ",array2where($where));
    }
}
?>