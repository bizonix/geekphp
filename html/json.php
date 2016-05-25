<?php
error_reporting(E_ALL);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include "../framework.php";
Core::getInstance();
session_start();
$mod	=	isset($_REQUEST['mod']) ? $_REQUEST['mod']: "";
$act	=	isset($_REQUEST['act']) ? $_REQUEST['act']: "";
//$token	=	trim($_REQUEST['token']);

if(empty($mod)){
	echo "empty mod";
	exit;
}

if(empty($act)){
	echo "empty act";
	exit;
}

//初始化memcache类
$memc_obj = new Cache(C('CACHEGROUP'));

$callback	=	isset($_REQUEST['callback']) ? $_REQUEST['callback']: "";
$jsonp		=	isset($_REQUEST['jsonp']) ? $_REQUEST['jsonp']: "";

//未登陆拦截
if(empty($_SESSION['userId'])&&$mod!=='login'){ //没登陆则提示
    $dat	=	array("errCode"=>6001, "errMsg"=>'登陆超时', "data"=>"");
    goto loginchck;
}

$modName	=	ucfirst($mod."Act");
$modClass	=	new $modName();

$actName	=	"act_".$act;
if(method_exists($modClass, $actName)){
	$ret	=	$modClass->$actName();
}else{
	echo "no this act!!";
	exit;
}
$dat	=	array();


if(empty($ret)){
	$dat	=	array("errCode"=>$modName::$errCode, "errMsg"=>$modName::$errMsg, "data"=>"");
}else{
	$dat	=	array("errCode"=>$modName::$errCode, "errMsg"=>$modName::$errMsg, "data"=>$ret);
}

loginchck:

if(!empty($callback)){
	if(!empty($jsonp)){
		echo "try{ ".$callback."(".json_encode($dat)."); }catch(e){alert(e);}";
	}else{
		echo $callback."(".json_encode($dat).");";
	}
	
}else{
	if(!empty($jsonp)){
		echo json_encode($dat);
	}else{
		echo $dat;
	}
}
?>