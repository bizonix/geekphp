<?php
/*
*模板通用操作类
*@add by : linzhengxiang ,date : 20140525
*/

class CommonModel extends ValidateModel{
	public function __construct($model=''){
		parent::__construct();
		if(!empty($model)){
			$this->model = $model;
		}
		$this->initDbPrefix();

	}
	/**
	 * 组织插入信息
	 * @param array $data
	 * @author zjr
	 */
	public function bulitDatas($data){
		$retData = array();
		$table = $this->getTableName();
		$columnlists = $this->sql("SHOW COLUMNS FROM ".self::$dbPrefix."`{$table}`")->select(array('mysql'), 3600);
    	foreach ($columnlists AS $columnlist){
    		if(!empty($data[$columnlist['Field']])){
    			$retData[$columnlist['Field']] = $data[$columnlist['Field']];
    		}
    	}
    	return $retData;
	}
	/**
	 * 插入信息
	 * @param array $data
	 * @author lzx
	 */
	public function insertData($data){
		$this->initDbPrefix();
	   
	    
	    $fdata	=	$data;
		$fdata = $this->formatInsertField($this->getTableName(), $data);
		if ($fdata===false){
			self::$errMsg = $this->validatemsg;
			return false;
		}
		if ($this->checkIsExists($fdata)){
			return false;
		}
		return $this->sql("INSERT INTO ".$this::$dbPrefix.$this->getTableName()." SET ".array2sql($fdata))->insert();
	}
	/*
	 * 判断是都是独立分销商并且已经有数据库
	 */
    public function existDb($dpId){
    	return false;
        $ret =   M("user")->getDeveloper('independent_db', "id='".$dpId."'");
        if (!empty($ret)) {
            $independentDb   =   $ret[0]['independent_db'];
            if(in_array($independentDb, array("1","2"))){
            	return $independentDb=='2';
            }
        }
        
        return false;
    }
	/**
	 * 根据id更新信息
	 * @param array $data
	 * @author lzx
	 */
	public function updateData($id, $data){
		$this->initDbPrefix();
		$id = intval($id);
		if ($id==0){
		        self::$errMsg[10110] = get_promptmsg(10110,'更新');
			return false;
		}
	    $fdata	=	$data;
		
		$fdata = $this->formatUpdateField($this->getTableName(), $data);
		if ($fdata===false){
			self::$errMsg = $this->validatemsg;
			return false;
		}
		return $this->sql("UPDATE ".$this::$dbPrefix.$this->getTableName()." SET ".array2sql($fdata)." WHERE id={$id}")->update();
	}
	
	/**
	 * 获取所有信息
	 * @author wcx
	 */
	public function getAllData($fieldArr='*', $whereArr='1', $key='',$sort=' order by id desc '){

		$this->initDbPrefix();
		if(empty($fieldArr)){
			$field  =   "*";
		}else{
			if(is_array($fieldArr)){
				$field =   '`'.implode('`,`', $fieldArr).'`';
			}else{
				$field =   $fieldArr;
			}
		}
		if(empty($whereArr)){
			$where  =   "is_delete=0";
		}else{
			if(is_array($whereArr)){
				$whereArr['is_delete'] = 0;
				$whereArr  =   $this->formatWhereField($this->getTableName(), $whereArr);
				if(empty($whereArr)){
					$this::$errMsg =   $this->validatemsg;
					return false;
				}
				$where =   "is_delete=0";
				foreach($whereArr as $k=>$v){
					$where .=  " AND `$k`='$v'";
				}
			}else{
				$where =   $whereArr." and is_delete=0";
			}
		}
		 
		$sql = 'SELECT '.$field.' FROM '.$this::$dbPrefix.'`'.$this->getTableName().'` WHERE '.$where;
		//echo $sql."\r\n";exit;
		return $this->sql($sql)->sort($sort)->limit("*")->key($key)->select(array('cache','mysql'));
	}
	
	/**
	 * 获取信息
	 * @author wcx
	 */
	public function getData($fieldArr='*', $whereArr='1', $sort=' order by id desc ',$page=1, $perpage=20){
		$this->initDbPrefix();
	    if(empty($fieldArr)){
	        $field  =   "*";
	    }else{
	        if(is_array($fieldArr)){
    	        $field =   '`'.implode('`,`', $fieldArr).'`';
	        }else{
	            $field =   $fieldArr;
	        }
	    }
	    if(empty($whereArr)){
	        $where  =   "is_delete=0";
	    }else{
	        if(is_array($whereArr)){
	            try {
	                $whereArr  = $this->formatWhereField($this->getTableName(), $whereArr);
	            } catch (Exception $e) {
	                $whereArr  = array();
	            }
	            if(empty($whereArr)){
	                $this::$errMsg =   $this->validatemsg;
	            	return false;
	            }
	            $where = "is_delete=0";
	            foreach($whereArr as $k=>$v){
	                $where .= " AND `$k`='$v'";
	            }
	        }else{
	            $where =   $whereArr." and is_delete=0";
	        }
	    }
	    
	    $sql = 'SELECT '.$field.' FROM '.$this::$dbPrefix.'`'.$this->getTableName().'` WHERE '.$where;
// 	    echo $sql."\r\n";
	    
	    return $this->sql($sql)->sort($sort)->page($page)->perpage($perpage)->select($this->getCacheOrMysql());
	}
	/*
	 * 获取查询条件中某个字段的值
	 */
	public function getWhereDataOneFieldValue($field,$whereData){
        if(is_array($whereData)){
            return isset($whereData[$field])?$whereData[$field]:false;
        }else{
            if(strpos('_'.$whereData, $field)===false){
            	return false;
            }else{
                return getWhereStrOneFieldValue($whereData);
            }
        }
	}
	/**
	 *  获取单个信息
	 *  @author wcx
	*/
	public function getSingleData($fieldArr='*',$whereArr='1'){
	    $ret   =   $this->getData($fieldArr,$whereArr);
	    if(empty($ret)){
	    	return $ret;
	    }else{
	    	return $ret[0];
	    }
	}
	/**
	 * 更新多条信息
	 * @author wcx
	 */
	public function updateDataWhere($data,$whereArr='0'){
		$this->initDbPrefix();
	    if(!is_array($whereArr)){
	        $where =   '';
	        $where .=  $whereArr;
	    }else{
	        $where =   '1';
    	    foreach($whereArr as $k=>$v){
    	        $where .=  " AND $k='".mysql_real_escape_string($v)."'";
    	    }
	    }
	    $fdata = $this->formatUpdateField($this->getTableName(), $data);

	    if ($fdata===false){
	        self::$errMsg = $this->validatemsg;
	        return false;
	    }
	    //echo "UPDATE ".$this::$dbPrefix.$this->getTableName()." SET ".array2sql($fdata)." WHERE $where";exit;
	    return $this->sql("UPDATE ".$this::$dbPrefix.$this->getTableName()." SET ".array2sql($fdata)." WHERE $where")->update();
	}
	
	public function replaceData($id, $data, $column='id'){
		$this->initDbPrefix();
		$id = intval($id);
		$column = addslashes($column);
		if ($id==0){
		    self::$errMsg[10110] = get_promptmsg(10110,'更新或插入');
			return false;
		}
		$fdata = $this->formatUpdateField($this->getTableName(), $data);
		if ($fdata===false){
			self::$errMsg = $this->validatemsg;
			return false;
		}
		if (!$this->checkIsExists($fdata)){
			return false;
		}
		$check = $this->sql("SELECT COUNT(*) AS count FROM {$this->getTableName()} WHERE {$column}={$id}")->count();
		if ($check==0) {
			$fdata[$column] = $id;
			return $this->insertData($fdata);
		}else{
			return $this->updateDataWhere($data,"id='$id'");
			//return $this->sql("UPDATE ".$this->getTableName()." SET ".array2sql($fdata)." WHERE {$column}={$id}")->update();
		}
	}

	public function replaceDataWhere($data, $whereArr){
		$this->initDbPrefix();

		$fdata	=	$data;
		$fdata = $this->formatInsertField($this->getTableName(), $data);
		if ($fdata===false){
			self::$errMsg = $this->validatemsg;
			return false;
		}
		if ($this->checkIsExists($fdata)){
			return false;
		}

		if(empty($whereArr)){
	        $where  =   "is_delete=0";
	    }else{
	        if(is_array($whereArr)){
	        	$whereArr['is_delete'] = 0;
	            $whereArr  =   $this->formatWhereField($this->getTableName(), $whereArr);
	            if(empty($whereArr)){
	                $this::$errMsg =   $this->validatemsg;
	            	return false;
	            }
	            $where =   "is_delete=0";
	            foreach($whereArr as $k=>$v){
	                $where .=  " AND `$k`='$v'";
	            }
	        }else{
	            $where =   $whereArr." and is_delete=0";
	        }
	    }
		$check = $this->sql("SELECT COUNT(*) AS count FROM {$this->getTableName()} WHERE ".$where)->count();
		if ($check==0) {
			return $this->insertData($fdata);
		}else{
			return $this->updateDataWhere($data,$whereArr);
		}
	}

	/**
	 * 删除信息
	 * @param array $data
	 * @author lzx
	 */
	public function deleteData($id){
		$id = intval($id);
		if ($id==0){
		    self::$errMsg[10110] = get_promptmsg(10110,'删除');
			return false;
		}
		return $this->sql("UPDATE ".$this::$dbPrefix.$this->getTableName()." SET is_delete=1 WHERE id={$id}")->delete();
	}
	
	/**
	 * 删除信息
	 * @param array $data
	 * @author zjr
	 */
	public function deleteDataByCondition($whereData){
		$this->initDbPrefix();
	    if (empty($whereData)){
		    self::$errMsg[10110] = get_promptmsg(10110,'删除');
			return false;
		}
		return $this->sql("UPDATE ".$this::$dbPrefix.$this->getTableName()." SET is_delete=1 WHERE ".array3where($whereData))->delete();
	}

	/*
	 * 获取记录条数
	 */
	public function getDataCount($whereArr='1'){
	    $num   =   $this->getSingleData("COUNT(*) as count",$whereArr);
	    if(empty($num)){
	    	return 0;
	    }else{
	    	return $num['count'];
	    }
	}
	/**
	 * sql记录条数统计
	 * @param array $data
	 * @author lzx
	 */
	public function replaceSql2Count($sql){
		if (preg_match("/(`[a-z]*`)\.\*/", $sql)>0){
			return preg_replace("/(`[a-z]*`)\.\*/", "COUNT(\$1.id) AS count", $sql);
		}else if(preg_match("/^SELECT\s*\*/i", $sql)>0){
			return preg_replace("/^SELECT\s*\*/i", "SELECT COUNT(*) AS count", $sql);
		}else{
		    self::$errMsg[10111] = get_promptmsg(10111);
			return false;
		}
	}

	public function checkIsExists($data){
	    //self::$errMsg[10109] = get_promptmsg(10109);
		return false;
	}

	public function resetCache(){
		$this->recache = true;
	}

	public function getErrorMsg(){
		return self::$errMsg;
	}
	public function setTablePrefix($tablePrefix=''){
		self::$tablePrefix    =   $tablePrefix;
		if(!empty($this::$tablePrefix)&&in_array($this->getTableName(false),C("NEEDCOPYTABLES"))&&($this->getTableName()!=$this->getTableName(false))){
			$ret   =   $this->sql("CREATE TABLE IF NOT EXISTS ".$this::$dbPrefix."`".$this->getTableName()."` like ".$this::$dbPrefix."`".$this->getTableName(false)."`")->create();
			if(empty($ret)){
				self::$errMsg[123] = "自动创建表失败";
				return false;
			}
		}
	}
	public function setDbPrefix($databaseIndex=''){
		if(!empty($databaseIndex)&&self::existDb($databaseIndex)&&in_array($this->getTableName(false), C("NEEDCOPYTABLES"))){
            self::$dbPrefix   =   C("SOURCEDATABASE").'_'.$databaseIndex.'.';
        }else{
            self::$dbPrefix   =   C("SOURCEDATABASE").'.';
        }
	}
	public function initDbPrefix(){
		
		if(!in_array($this->getTableName(false), C("NEEDCOPYTABLES"))) {
			self::$dbPrefix   =   C("SOURCEDATABASE").'.';
		}
		//echo $this->getTableName(false).'  '.self::$dbPrefix."\r\n";

	}
	public function getDbPrefix(){
		return self::$dbPrefix;
	}
	public function getTablePrefix(){
		return self::$tablePrefix;
	}
	/**
	 * 获取表名
	 * @param unknown $tablePrefix
	 * @param unknown $databaseIndex
	 * @return boolean
	 * by wcx
	 */
	public function getTablesName($tablePrefix,$databaseIndex){
		if (empty($tablePrefix)){
		    self::$errMsg[10127] = get_promptmsg(10127,'表名字');
			return false;
		}
		$this->setDbPrefix($databaseIndex);
		return $this->sql("SHOW  TABLES FROM ".trim($this::$dbPrefix,'.')." LIKE '".$tablePrefix."_%'")->showTabel();
	}
	/**
	 * 根据配置获取数据来源类型
	 * @return multitype:string
	 * by wcx
	 */
	public function getCacheOrMysql(){
		$cacheOrMysql	=	array('mysql');
		$table	=	hump2underline(lcfirst(str_replace('Model', '', get_class($this))));
		if(in_array($table,C("CACHE_TABLE"))){
			$cacheOrMysql	=	array('cache','mysql');
		}
		return $cacheOrMysql;
	}
	/**
	 * 根据sql获取信息
	 * @author wcx
	 */
	public function getDataBySql($sql = '', $whereArr='1', $sort=' order by id desc ',$page=1, $perpage=20){
		if(empty($sql)) return $this->getData("*",$whereArr,$sort,$page,$perpage);
		$this->initDbPrefix();
	    if(empty($whereArr)){
	        $where  =   "is_delete=0";
	    }else{
	        if(is_array($whereArr)){
	        	$whereArr['is_delete'] = 0;
	            $whereArr  =   $this->formatWhereField($this->getTableName(), $whereArr);
	            if(empty($whereArr)){
	                $this::$errMsg =   $this->validatemsg;
	            	return false;
	            }
	            $where =   "is_delete=0";
	            foreach($whereArr as $k=>$v){
	                $where .=  " AND `$k`='$v'";
	            }
	        }else{
	            $where =   $whereArr;
	        }
	    }
	    $sql = $sql." where ".$where;
	    return $this->sql($sql)->sort($sort)->page($page)->perpage($perpage)->select($this->getCacheOrMysql());
	}
}
?>