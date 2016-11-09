<?php
/*
 */
class CommonAct{

	public $page 			= 0;
	public $perpage  		= 0;

	protected static $errMsg 	= array();
	//构造函数
	public function __construct(){
		if (@$_GET['rc']==='reset') {
			M($this->act_action2Model())->resetCache();
		}
		$this->page 		= 	isset($_GET['page'])&&intval($_GET['page'])>0 ? intval($_GET['page']) : 1;
		/* $this->page 		= 	isset($_REQUEST['page'])&&intval($_REQUEST['page'])>0 ? intval($_REQUEST['page']) : 1; */
		$this->perpage 		= 	isset($_GET['pnum'])&&intval($_GET['pnum'])>0 ? intval($_GET['pnum']) : 10;
		@register_shutdown_function(array($this, '__destruct'));
	}

	/**
	 * 析构函数，赋值模型中执行错误信息
	 */
	/*public function __destruct(){
		$errMsgs = M('common')->getErrorMsg();
		if (!empty($errMsgs)){
			foreach ($errMsgs AS $code=>$errMsg){
				self::$errMsg[$code] = $errMsg;
			}
		}
	}*/

	/**
	 * 获取错误信息
	 * @return array
	 * @author lzx
	 */
	public function act_getErrorMsg(){
		$errMsgs = M('common')->getErrorMsg();
		if (!empty($errMsgs)){
			foreach ($errMsgs AS $code=>$errMsg){
			    if(!isset(self::$errMsg[$code])){
			        self::$errMsg[$code] = $errMsg;
			    }
			}
		}
		return self::$errMsg;
	}
	/**
	 * 获取上一个错误信息
	 * @return multitype:
	 * by wcx
	 */
	public function act_getLastErrorMsg(){
		$errMsgs = M('common')->getErrorMsg();
		if (!empty($errMsgs)){
			foreach ($errMsgs AS $code=>$errMsg){
			    if(!isset(self::$errMsg[$code])){
			        self::$errMsg[$code] = $errMsg;
			    }
			}
		}
		foreach(self::$errMsg as $k=>$v){
			if($k!="200"){
				return array($k,$v);
			}
		}
		return array();
	}
	/**
	 * 获取当前页数
	 * @return int
	 * @author lzx
	 */
	public function act_getPage(){
		return $this->page;
	}

	/**
	 * 获取当前每页数量
	 * @return int
	 * @author lzx
	 */
	public function act_getPerpage(){
		return $this->perpage;
	}

	/**
	 * 根据控制获取对应的模型
	 * @return string
	 * @author lzx
	 */
	private function act_action2Model(){
		$childname = get_class($this);
		return substr($childname, 0, strlen($childname)-3);
	}

	/**
	 * 返回分销商信息
	 * @return string
	 * @author wcx
	 */
	public function act_getDevelopeLoginEmail(){

	    $data  =   json_decode(_authcode($_COOKIE['hcUser']),true);
		return    $data['email'];
	}

	/**
	 * 返回分销商信息
	 * @return string
	 * @author wcx
	 */
	public function act_getUserInfor($flag){
	    $data  =   @json_decode(_authcode($_COOKIE['hcUser']),true);
		return    empty($data[$flag])?false:$data[$flag];
	}

	/**
	 * 返回分销商一部分信息
	 * @return string
	 * @author wcx
	 */
	public function act_getUserSomeInfor($flag){
		return    isset($_SESSION[$flag]) ? $_SESSION[$flag] : 1;
	}

	/**
	 * 设置分销商部分信息
	 * @return string
	 * @author wcx
	 */
	public function act_setUserSomeInfor($flag,$val){
	    $_SESSION[$flag] 	= 	$val;
	    return true;
	}

	/*
	 * 获取action对应的model
	 */
	public function act_getModel(){
	    return str_replace("Act", "", get_class($this));
	}

	public function act_getAdminInfor($flag='userCnName'){
	    $data  =   json_decode(_authcode($_COOKIE['hcAdmin']),true);
	    return    empty($data[$flag])?false:$data[$flag];
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
    public function _getCondition($arr = array(),$flag = true){
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
            if(!$flag||!empty($default)){
                $where[$value][$op] = $this->_param($key,$default);
            }
        }
        return implode(" AND ",array2where($where));
    }
    public function act_updateData($id,$data){
        return M($this->act_action2Model())->updateData($id,$data);
    }
    public function act_addData($data){
        return M($this->act_action2Model())->insertData($data);
    }
    public function act_delData($id=0){
        return M($this->act_action2Model())->deleteData($id);
    }
    public function act_getSingleData($where){
        return $this->act_getList($where);
    }
    public function act_getList($whereArr){
        return M($this->act_action2Model())->getData("*",$whereArr);
    }
    
    /**
     * 简易的对where条件中去除空值的查询条件
     * @param unknown $whereArr
     * @return unknown
     */
    public function _buildWhere($whereArr){
        if(!empty($whereArr)){
            foreach ($whereArr as $key=>$where){
                if(empty($where)){
                    unset($whereArr[$key]);
                }
            }
        }
        return $whereArr;
    }
}