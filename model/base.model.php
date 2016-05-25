<?php
/*
*基础操作类(model)
*@add by Herman.Xi ,date20130901
*@modify by : linzhengxiang ,date : 20140523
*/
defined('WEB_PATH') ? '' : exit;
class BaseModel{

	public $model ='';
	protected $dbConn  = '';
	protected $cache   = '';
	protected $recache = false;
	protected $options = array();
	private   static $_sql    = array();
	protected static $errMsg  = array();
	public static $dbPrefix   = '';//数据库
	public static $tablePrefix= '';//表
	public static $lastInsertId='';
	private static $transaction	=	0;
	//构造函数自动加载DB对象
	public function __construct(){
		if (!is_object($this->dbConn)){
			$this->_initDB();
		}
		if (!is_object($this->cache)){
			$this->_initCache();
		}
	}

	//魔法函数，连贯操作的实现
	public function __call($method,$args){

		$allowfun = array('sql', 'key', 'sort', 'perpage', 'page', 'limit');
		$fun = strtolower($method);
        if(in_array($fun, $allowfun, true)) {
            $this->options[$fun] = $args[0];
            return $this;
        }else if (preg_match("/^get([a-z]+)ById$/i", $method, $match)>0){
        	$source = isset($args[1]) ? $args[1] : array('mysql');
        	$cachetime = isset($args[2]) ? $args[2] : 600;
        	$data = $this->sql("SELECT * FROM ".C('DB_PREFIX').hump2underline(lcfirst($match[1]))." WHERE id=".intval($args[0]))->limit(1)->select($source, $cachetime);
        	return isset($data[0]) ? $data[0] : array();
        }else{
            echo  get_promptmsg(10107, $fun);
            exit;
//         	debug_print_backtrace();
//         	exit("BaseModel __call {$method} not exist! ");
        }
    }

    //初始化mysqlDB
	private function _initDB(){
		global $dbConn;
		$this->dbConn = $dbConn;
		mysql_query('SET NAMES UTF8');
	}

	//初始化缓存
	private function _initCache(){
		global $memc_obj;
		$this->cache = $memc_obj;
	}

	protected function resetConnect() {
		//待开发
	}

	private function buildConnect(){
		//待开发
	}

	/**
	 * 重新封装sql语句查询函数，新增缓存
	 * @param array $source   支持array('cache', 'mysql')
	 * @param int $cachetime  支持缓存才生效，设置缓存时间
	 * @return array 查询结果
	 * @author lzx
	 */
	protected function select($source=array('mysql'), $cachetime=900){
		$sql 	 = isset($this->options['sql']) ? trim($this->options['sql']) : '';
		$sort 	 = isset($this->options['sort']) ? trim($this->options['sort']) : '';
		$limit	 = isset($this->options['limit']) ? $this->options['limit'] : 0;
    	$page 	 = isset($this->options['page']) ? intval($this->options['page']) : 1;
    	$perpage = isset($this->options['perpage']) ? intval($this->options['perpage']) : 10;
		if (empty($sql)){
			self::$errMsg[10020] = get_promptmsg(10020, 'select');
			return array();
		}
		if (preg_match("/^\s*select/i", $sql)>0&&$limit!=='*'){
			$limit = intval($limit);
			$limit = "LIMIT ".($limit>0 ? $limit : (($page-1)*$perpage).", {$perpage}");
		}else{
			$limit = '';
		}
    	$sql = "{$sql} {$sort} {$limit}";
    	self::$_sql[] = $sql;
    	$cachekey = C("DB_PREFIX").'sql_select_'.md5($sql);
		if (in_array('cache', $source)&&!$this->recache){
			$cachedata = $this->cache->get($cachekey);
			if (!empty($cachedata)){
			    self::$errMsg[200] = get_promptmsg(200);
				return $this->changeArrayKey(json_decode($cachedata, true));
			}
		}
		if (in_array('mysql', $source)){
			$query = $this->dbConn->query($sql);
			$mysqldatas = $this->dbConn->fetch_array_all($query);
			if (in_array('cache', $source)) {
			    $this->cache->set($cachekey, json_encode($mysqldatas), $cachetime);
			}
			self::$errMsg[200] = get_promptmsg(200);
			return $this->changeArrayKey($mysqldatas);
		}
		return	array();
	}

	/**
	 * 重新封装sql语句统计函数，新增缓存
	 * @param array $source   支持array('cache', 'mysql')
	 * @param int $cachetime  支持缓存才生效，设置缓存时间
	 * @return int 查询结果
	 * @author lzx
	 */
	protected function count($source=array('mysql'), $cachetime=900){
		$sql = isset($this->options['sql']) ? trim($this->options['sql']) : '';
		$this->options = array();
		$this->_sql[] = $sql;
		if (empty($sql)){
			self::$errMsg[10020] = get_promptmsg(10020, 'count');
			return 0;
		}
		$cachekey = C("DB_PREFIX").'sql_count_'.md5($sql);
		if (in_array('cache', $source)&&!$this->recache){
			$cachenum = $this->cache->get($cachekey);
			if (!empty($cachenum)){
			    self::$errMsg[200] = get_promptmsg(200);
				return $cachenum;
			}
		}
		if (in_array('mysql', $source)){
			$query = $this->dbConn->query($sql);
			$mysqldata = $this->dbConn->fetch_array($query);
			if (in_array('cache', $source)) {
			    $this->cache->set($cachekey, $mysqldata['count'], $cachetime);
			}
			self::$errMsg[200] = get_promptmsg(200);
			return $mysqldata['count'];
		}
	}

	/**
	 * 重新封装sql语句更新函数
	 * @return bool 执行结果
	 * @author lzx
	 */
	protected function update(){
		$sql = isset($this->options['sql']) ? trim($this->options['sql']) : '';
		$this->options = array();
		self::$_sql[] = $sql;
		if (empty($sql)){
			self::$errMsg[10020] = get_promptmsg(10020, 'update');
			return false;
		}
		if (preg_match("/^\s*update/i", $sql)==0){
			self::$errMsg[10019] = get_promptmsg(10019, 'update', $sql);
			return false;
		}
		return $this->dbConn->query($sql);
	}

	/**
	 * 重新封装sql语句插入函数
	 * @return bool 执行结果
	 * @author lzx
	 */
	protected function insert(){
		$sql = isset($this->options['sql']) ? trim($this->options['sql']) : '';
		$this->options = array();
		self::$_sql[] = $sql;
		if (empty($sql)){
			self::$errMsg[10020] = get_promptmsg(10020, 'insert');
			return false;
		}
		if (preg_match("/^\s*insert/i", $sql)==0){
			self::$errMsg[10019] = get_promptmsg(10019, 'insert', $sql);
			return false;
		}
		$ret  =   $this->dbConn->query($sql);
		if(empty($ret)){
			echo $sql;exit;
		}
		if($ret){
		    self::$lastInsertId   =   mysql_insert_id();
		    self::$errMsg[200] = get_promptmsg(200);
			return true;
		}else{
		    self::$errMsg[10021] = get_promptmsg(10021,'insert');
			return false;
		}
	}

	/**
	 * 重新封装sql语句创建函数
	 * @return bool 执行结果
	 * @author lzx
	 */
	protected function create(){
		$sql = isset($this->options['sql']) ? trim($this->options['sql']) : '';
		$this->options = array();
		self::$_sql[] = $sql;
		if (empty($sql)){
			self::$errMsg[10020] = get_promptmsg(10020, 'create');
			return false;
		}
		if (preg_match("/^\s*create/i", $sql)==0){
			self::$errMsg[10019] = get_promptmsg(10019, 'create', $sql);
			return false;
		}
		$ret  =   $this->dbConn->query($sql);
		if($ret){
		    self::$errMsg[200] = get_promptmsg(200);
		    return true;
		}else{
		    self::$errMsg[10021] = get_promptmsg(10021,'create');
		    return false;
		}
	}

	/**
	 * 重新封装sql语句删除函数，逻辑删除
	 * @return bool 执行结果
	 * @author lzx
	 */
	protected function delete(){
		$sql = isset($this->options['sql']) ? trim($this->options['sql']) : '';
		$this->options = array();
		self::$_sql[] = $sql;
		if (empty($sql)){
			self::$errMsg[10020] = get_promptmsg(10020, 'delete');
			return false;
		}
		if (preg_match("/^\s*update.*is_delete\s*=\s*1/i", $sql)==0){
			self::$errMsg[10019] = get_promptmsg(10019, 'delete', $sql);
			return false;
		}
		$ret  =   $this->dbConn->query($sql);
		if($ret){
		    self::$errMsg[200] = get_promptmsg(200);
		    return true;
		}else{
		    self::$errMsg[10021] = get_promptmsg(10021,'delete');
		    return false;
		}
	}
	/**
	 * 重新封装sql语句遍历表函数
	 * @return array 执行结果
	 * @author wcx
	 */
	protected function showTabel($source=array('mysql'), $cachetime=900){
		$sql =	isset($this->options['sql']) ? trim($this->options['sql']) : '';
		//show  tables FROM opensystem like  "dp_order_details_%" ;
		if (empty($sql)){
			self::$errMsg[10020] = get_promptmsg(10020, 'show');
			return array();
		}
		if (preg_match("/^\s*show/i", $sql)==0){
			self::$errMsg[10019] = get_promptmsg(10019, 'show', $sql);
			return false;
		}
    	self::$_sql[] = $sql;
    	$cachekey = C("DB_PREFIX").'sql_show_'.md5($sql);
		if (in_array('cache', $source)&&!$this->recache){
			$cachedata = $this->cache->get($cachekey);
			if (!empty($cachedata)){
			    self::$errMsg[200] = get_promptmsg(200);
				return $cachedata;
			}
		}
		if (in_array('mysql', $source)){
			$query = $this->dbConn->query($sql);
			$mysqldatas = $this->dbConn->fetch_array_all($query);
			$ret	=	array();
			foreach($mysqldatas as $k=>$v){
				$ret[]	=	array_pop($v);
			}
			if (in_array('cache', $source)) {
			    $this->cache->set($cachekey, json_encode($ret), $cachetime);
			}
			self::$errMsg[200] = get_promptmsg(200);
			return $ret;
		}
	}

	public function begin() {
		if(self::$transaction==0){
			self::$_sql[] = "BEGIN";
			$this->dbConn->begin();
		}
		self::$transaction	++;
	}

	public function commit() {
		self::$transaction	--;
		if(self::$transaction==0){
			self::$_sql[] = "COMMIT";
			$this->dbConn->commit();
		}
	}

	public function rollback() {
		self::$transaction	--;
		if(self::$transaction==0){
			self::$_sql[] = "ROLLBACK";
			$this->dbConn->rollback();
		}
	}

	public function autoCommit() {
		mysql_query('SET autocommit=1');
	}

	/**
	 * 获取刚刚新增的数据行id
	 * @return int 自增主键
	 * @author lzx
	 */
	public function getLastInsertId(){
		return self::$lastInsertId;
	}
	/**
	 * 获取刚刚新增的数据行id
	 * @return string 刚刚执行的sql语句
	 * @author lzx
	 */
	public function getLastRunSql(){
		return array_pop(self::$_sql);
	}

	/**
	 * 获取刚刚新增的数据行id
	 * @return array 所有被执行的sql语句
	 * @author lzx
	 */
	public function getAllRunSql(){
		return self::$_sql;
	}

	/**
	 * 跟进类名转化为表名
	 * @return string 数据表名
	 * @author lzx
	 */
	protected function getTableName($flag=true){
		if(!empty($this->model)){
			$model = $this->model;
		}else{
			$model =  get_class($this);
		}
	    $table =   C('DB_PREFIX').hump2underline(lcfirst(str_replace('Model', '', $model)));
	    if($flag){
    	    if(in_array($table, C("NEEDSUBMETER"))&&!empty(self::$tablePrefix)){
    	        return $table.self::$tablePrefix;
    	    }else{
    	        return $table;
    	    }
	    }else{
	    	return $table;
	    }
	}

	/**
	 * 切换返回数组的KEY值
	 * @param array $data
	 * @return array
	 * @author lzx
	 */
	private function changeArrayKey($data){
		$key = isset($this->options['key']) ? trim($this->options['key']) : '';
		$this->options = array();
		if (empty($key)||empty($data)||!isset($data[0][$key])){
			return $data;
		}
		$reulst = array();
		foreach ($data AS $k=>$list){
			$reulst[$list[$key]] = $list;
		}
		unset($data);
		return $reulst;
	}
	
	/**
	 * 保存数据方法（自动判断更新还是新增）
	 * @param unknown $data
	 * @param string $table
	 * @return boolean|Ambigous <number, string>
	 * @author jbf
	 */
	public function save($data, $table = null, $database = null, $idNull = false) {
	    if (empty($data)) return false;
	    if (empty($table)) $table = $this -> getTableName();
	    
	    if (empty($data['id']) || $idNull) {
	        $sql = self::buildInsertSQL($data, $table, $database);
	        if ($this -> sql($sql) -> insert()) {
	            $this->commit();
	            return $this -> getLastInsertId();
	        } else {
	            return false;
	        }
	    } else {
	        $id = intval($data['id']);
	        unset($data['id']);
	        $sql = self::buildUpdateSQL($data, $table, $database);
	        if (empty($sql)) {
	            self::$errMsg[10030] = '生成数据错误，请重试！';
	            return false;
	        } else {
	            $sql .= " WHERE `id` = '" .$id. "'";
	            $res = $this -> sql($sql) -> update();
	            $this -> dbConn -> commit();
	            return $res;
	        }
	    }
	    
	}
	
	/**
	 * 生成插入数据SQL
	 * @param unknown $data
	 * @param unknown $table
	 * @return boolean|string
	 * @author jbf
	 */
	private function buildInsertSQL($data, $table, $database = null) {
	    if (empty($data) || empty($table)) return false;
	    
	    $splitData = self::splitDataForInsertSQL($data);
	    return "INSERT INTO `".(empty($database) ? '' : $database.'`.`').$table."`(".$splitData['fields'].") VALUES(".$splitData['values'].")";
	}
	
	/**
	 * 生成更新数据SQL
	 * @param unknown $data
	 * @param unknown $table
	 * @return boolean|string
	 * @author jbf
	 */
	private function buildUpdateSQL($data, $table, $database = null) {
	    if (empty($data) || empty($table) || !empty($data['id'])) return false;
	     
	    $splitData = self::splitDataForUpdateSQL($data);
	    return "UPDATE `".(empty($database) ? '' : $database.'`.`').$table."` SET ".$splitData;
	}
	
	/**
	 * 分割数据用于更新SQL
	 * @param unknown $data
	 * @return boolean|string
	 * @author jbf
	 */
	private function splitDataForUpdateSQL($data) {
	    if (empty($data) || !empty($data['id']) || !is_array($data)) return false;
	    
	    $setData = array();
	    foreach ($data AS $key => $value) {
	        $setData[] = " `".$key."` = '".$value."'";
	    }
	    
	    if (!empty($setData)) return implode(',', $setData);
	    else return false;
	}
	
	/**
	 * 分割数据用于插入SQL
	 * @param unknown $data
	 * @return boolean|multitype:string
	 * @author jbf
	 */
	private function splitDataForInsertSQL($data) {
	    if (empty($data)) return false;
	
	    $fields = '';
	    $values = '';
	    foreach ($data AS $key => $value) {
	        if (!empty($value) && $value != 'null') {
	            $fields .= "`".$key."`,";
	            $values .= "'".$value."',";
	        }
	    }
	    
	    $values = substr($values, 0, -1);
	    $fields = substr($fields, 0, -1);
	    
	    if (!empty($fields) && !empty($values)) {
	        return array('fields' => $fields, 'values' => $values);
	    } else return false;
	}
	
	/**
	 * 生成用于IN语句的列表
	 * @param array $list
	 * @return boolean|string
	 * @author jbf
	 */
	protected function buildINString($list) {
	    if (empty($list)) return false;
	    
	    $result = "";
	    
	    foreach ($list AS $a) {
	        $result .= "'".$a."',";
	    }
	    
	    return substr($result, 0, -1);
	}
}
