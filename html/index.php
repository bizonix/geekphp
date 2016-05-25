<?php
use PhpAmqpLib\Channel\AbstractChannel;
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once dirname(__DIR__)."/conf/define.php";
include_once WEB_PATH."framework.php";
Core::getInstance();
$requestUri		= urldecode($_SERVER['REQUEST_URI']);
$requestUriArr 	= explode("/", $requestUri);
if(stripos($requestUri,"index.php") === false && !empty($requestUriArr[1]) && $requestUri){
	$_GET = $_REQUEST = null;
    for ($i=1;$i<count($requestUriArr);$i++) {
        if($i == 1){
            $_GET['mod'] = $_REQUEST['mod'] = $requestUriArr[$i];
        }elseif($i == 2){
            $_GET['act'] = $_REQUEST['act'] = $requestUriArr[$i];
        }elseif($i > 2){
             $_GET[$requestUriArr[$i]] = $_REQUEST[$requestUriArr[$i]] = @$requestUriArr[$i+1];
            $i++;
        }
    }
    //将post数据加入$_REQUEST
    if(!empty($_POST)){
        $_REQUEST = array_merge($_POST,$_REQUEST);
    }
}
$mod	=	isset($_REQUEST['mod']) ? $_REQUEST['mod']: "index";
$act	=	isset($_REQUEST['act']) ? $_REQUEST['act']: "index";
error_reporting(-1);

/*if(!file_exists(WEB_PATH.'view/'.$mod.'.view.php')){
    redirect_to(WEB_URL."public/showErr");
}*/
$modName	= ucfirst($mod."View");
$modClass	= new $modName();
$actName	= "view_".$act;
if(method_exists($modClass, $actName)){
	$ret	=	$modClass->$actName();
}else{
	/*echo "no this act!!";*/
	redirect_to(WEB_URL."public/showErr");	//**add by yyn**//
}
?>