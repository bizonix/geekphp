<?php
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include "../../framework.php";
Core::getInstance();

$appKey	=	empty($_REQUEST['app_key']) ? empty($_REQUEST['username']) ? '' : $_REQUEST['username'] : $_REQUEST['app_key'];
if(empty($appKey)){
    echo "empty app_key";
    exit;
}
//********接口频率限制***************
$controlRet = controlVisitTimes($appKey);
if($controlRet['errCode'] == 408){
    echo $controlRet['errMsg'];
    exit;
}
//**********************************
$act = isset($_GET['action']) ? $_GET['action']: "";
$v 	 = isset($_GET['v']) ? $_GET['v']: "1.0";

if(empty($act)){
	json_return(10170);
}

if (preg_match("/^[a-z0-9_]*$/i", $act)==0){
	json_return(10171, '', $act);
}

if (preg_match("/^[\.0-9_]*$/i", $v)==0){
	json_return(10175, '', $v);
}

$data = MC("SELECT * FROM ".C('DB_PREFIX')."interface_version WHERE requestname='{$act}' AND version='{$v}' AND is_delete=0", 0);
if (!isset($data[0]['is_disable'])){
	json_return(10173, '', $act, $v);
}
if ($data[0]['is_disable']==1){
	json_return(10174, '', $act, $v);
}
//对接口请求内容进行验证或转换
$transform = !empty($data[0]['extend_transform']) ? $data[0]['extend_transform'] : 'Transform:commonTransform';
list($vclass, $vfun) = explode(':', $transform);
$vmethod = ucfirst($vclass."Act");
$vfun   = 'act_'.$vfun;
if (!class_exists($vmethod)){
	json_return(10176);
}
if (!method_exists($vmethod, $vfun)){
	json_return(10176);
}
//验证数据
if (!A($vclass)->$vfun()){
	json_return(A($vclass)->act_getErrorMsg());
}
//加载实际执行函数
list($class, $fun) = explode(':', $data[0]['rule']);
$method = ucfirst($class."Act");
$fun   = 'act_'.$fun;
if (!class_exists($method)){
	json_return(10176);
}
if (!method_exists($method, $fun)){
	json_return(10176);
}
$ret = A($class)->$fun();
if (isset($_GET['debug']) && $_GET['debug']==1){
	echo "<!-- \n\t\t".implode("\n\t\t", M($class)->getAllRunSql())."\n\t -->\n";
}
if (empty($ret)){
	$errmsg = A($class)->act_getErrorMsg();
	if (!empty($errmsg)){
		json_return($errmsg);
	}
}
//对返回数据进行封装
$package = !empty($data[0]['extend_package']) ? $data[0]['extend_package'] : 'Package:commonPackage';
list($pclass, $pfun) = explode(':', $package);
$pmethod = ucfirst($pclass."Act");
$pfun   = 'act_'.$pfun;
if (!class_exists($pmethod)){
	json_return(10176);
}
if (!method_exists($pmethod, $pfun)){
	json_return(10176);
}
$ret = A($pclass)->$pfun($ret);

$callback	=	isset($_GET['callback']) ? $_GET['callback'] : "";
$jsonp		=	isset($_GET['jsonp']) ? $_GET['jsonp']: "";

$data = array("errCode"=>200, "errMsg"=>get_promptmsg(10172), "status"=>true, "data"=>$ret);
if(!empty($callback)){
	if(!empty($jsonp)){
		echo "try{ ".$callback."(".json_encode($data)."); }catch(){alert(e);}";
	}else{
		echo $callback."(".json_encode($data).");";
	}
}else{
	echo json_encode($data);
}
exit;

/**
 * 功能： 控制接口访问频率
 * @param string $appKey    访问者身份
 * @param number $contorlTime   控制访问频率区间
 * @param number $visitNums   控制区间内访问次数
 * @by zjr
 */

function controlVisitTimes($appKey,$contorlTime = 60 ,$visitNums = 50){
    global $memc_obj;
    $memkey		=	md5($appKey."_add_limit");	//新增调用控制
    $limit_info	=	$memc_obj->get($memkey);
    //缓存最长时间， 一天， 凌晨准时全部更新
    $time_cache	=	strtotime(date("Y-m-d")." 23:59:59")	-	time();	//一天剩余的缓存时间
    if(!empty($limit_info)){
        //半分钟重置一下统计
        $last_time	=	time()	-	$limit_info['time'];
        $count		=	$limit_info['count']+1;
        if($last_time > $contorlTime){
            $ar	=	array(
                "count"		=>	1,
                "time"		=>	time(),
            );
            $op	=	$memc_obj->replace($memkey, $ar, false, $time_cache);

        }else{
            $ar	=	array(
                "count"		=>	$count,
                "time"		=>	$limit_info['time'],
            );
            $op	=	$memc_obj->replace($memkey, $ar, false, $time_cache);

            if($count > $visitNums){
                $rtnArr["errcode"]	=	408;
                $rtnArr["msg"]		=	"接口请求过快, 请稍后再试!";
                return $rtnArr;
            }
        }

    }else{
        $ar	=	array(
            "count"		=>	1,
            "time"		=>	time(),
        );
        $op	=	$memc_obj->add($memkey, $ar, false, $time_cache);
    }
    
    $rtnArr["errCode"]	= 200;
    $rtnArr["errMsg"]	= "";
    return $rtnArr;
}

?>