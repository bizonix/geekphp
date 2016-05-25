<?php

//获取变量配置信息
function C($name=null, $value=null) {
    static $_config = array();
    if (empty($name))   return $_config;
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtolower($name);
            if (is_null($value))
                return isset($_config[$name]) ? $_config[$name] : null;
            $_config[$name] = $value;
            return;
        }
        // 二维数组设置和获取支持
        $name = explode('.', $name);
        $name[0]   =  strtolower($name[0]);
        if (is_null($value))
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : null;
        $_config[$name[0]][$name[1]] = $value;
        return;
    }
    // 批量设置
    if (is_array($name)){
        return $_config = array_merge($_config, array_change_key_case($name));
    }
    return null;
}

/**
 * M函数用于实例化一个模型文件的Model
 * @param string $calss Model名称
 * @return object
 * @author lzx
 */
function M($calss='') {

    static $_model  = array();
    //modify wcx
    $model = $calss.'Model';

    if(isset($_model[$model])){
        return $_model[$model];
    }else{
        if (!class_exists($model)){
            return $_model[$model] = new CommonModel($model);
        }else{
            return $_model[$model] = new $model();
        }
    }
    return false;
}

/**
 * A函数用于实例化Action
 * @param string $calss Action资源地址
 * @return object
 * @author lzx
 */
function A($calss='') {

    static $_action = array();

    $action = $calss.'Act';
    if (!class_exists($action)){
    	return false;
    }
    if (!isset($_action[$action])){
    	$_action[$action] = new $action();
    }
    return $_action[$action];
}

/**
 * F自动加载lib/functions/下面的函数库
 * @param string $filename|$classname 文件名称不带php
 * @return file
 * @author lzx
 */
function F($classname, $arg='') {//ebay.package.GetOrders
    static $_importFiles = array();
    $autodir = C('AUTO_DIR');
    if (!is_array($autodir)) {
    	return false;
    }
    $dirstr = '';
    if (strpos($classname, '.')){
    	$dirlist = explode('.', $classname);
    	$classname = array_pop($dirlist);//弹出最后一个变量GetOrders
    	$dirstr = '/'.implode('/', $dirlist);//重组/ebay/package
    }
    $filename = strtolower($classname);//小写getorders
    foreach ($autodir AS $dir=>$type){//array('functions'=>'file', 'class'=>'object', 'api'=>'object'),//文件目录
    	$staticname = "{$filename}_{$dir}";//getorders_functions
	    if (isset($_importFiles[$staticname])){//判断是否实例化过
	    	return $_importFiles[$staticname];//有实例化过则返回
	    }
    	$filepath = WEB_PATH."lib/{$dir}{$dirstr}/{$filename}_{$dir}.php";//找到该类路径
	    if (!isset($_importFiles[$staticname])) {//???重复代码?
	    	if (file_exists($filepath)) {//判断文件存在
	            require $filepath;//加载文件
	            if ($type=='object'){//如果是对象则实例化
	            	$_importFiles[$staticname] =  empty($arg) ? new $classname() : new $classname($arg);//参数只能有一个??
	            }else{
	            	$_importFiles[$staticname] =  true;//不是对象,是file,表示加载过了
	            }
                break;
	        }
	    }
    }
    if (!isset($_importFiles[$staticname])) $_importFiles[$staticname] = false;
    return $_importFiles[$staticname];
}

/**
 * 实例化lib/functions/下面的函数库
 * @param string $filename|$classname 文件名称不带php
 * @return file
 * @author andy
 */
function getInstance($classname, $arg='') {
    static $_importFiles = array();
    $autodir = C('AUTO_DIR');
    if(class_exists($classname)){
    	return new $classname();
    }
    if (!is_array($autodir)) {
    	return false;
    }
    $dirstr = '';
    if (strpos($classname, '.')){
    	$dirlist = explode('.', $classname);
    	$classname = array_pop($dirlist);
    	$dirstr = '/'.implode('/', $dirlist);
    }
    $filename = strtolower($classname);

    $new_obj = new stdClass();

    foreach ($autodir AS $dir=>$type){
    	$staticname = "{$filename}_{$dir}";

    	$filepath = WEB_PATH."lib/{$dir}{$dirstr}/{$filename}_{$dir}.php";

	    	if (file_exists($filepath)) {
	            require $filepath;
	            if ($type=='object'){
	            	$new_obj =  empty($arg) ? new $classname() : new $classname($arg);
	            }

                break;
	        }

    }

    return $new_obj;
}
/**
 * E自动加载lib/extend/下面的插件引入
 * @param string $classname 文件名称不带php
 * @return object
 * @author lzx
 */
function E($classname) {
    static $_extend = array();
    $filename = strtolower($classname);
    $filepath = WEB_PATH."lib/extend/{$filename}/{$filename}_extend.php";
    if (!isset($_extend[$classname])) {
    	if (file_exists($filepath)) {
            require $filepath;
	    	if (!class_exists($classname)){
		    	return false;
		    }
            $_extend[$classname] = new $classname();
        } else {
            $_extend[$classname] = false;
        }
    }
    return $_extend[$classname];
}

/**
 * MC函数里面执行sql和缓存函数
 * @param string $sql 需要执行SQL语句
 * @param int $cachetime 缓存时间，为0是不使用缓存
 * @return array
 * @author lzx
 */
function MC($sql, $cachetime=900){
	global $dbConn,$memc_obj;
    $cachekey = C("DB_PREFIX")."MC_".md5($sql);
//     if ($cachetime>0){
//     	$cachedata = $memc_obj->get($cachekey);
//     	if (!empty($cachedata)) {
//     		return json_decode($cachedata, true);
//     	}
//     }
    $query 		= $dbConn->query($sql);
    $mysqldata  = $dbConn->fetch_array_all($query);
	if ($cachetime>0){
    	$memc_obj->set($cachekey, json_encode($mysqldata), $cachetime);
    }
    return $mysqldata;
}
?>