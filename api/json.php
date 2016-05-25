<?php
error_reporting(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include "../framework.php";
Core::getInstance();
error_reporting(0);
$appKey	=	trim($_REQUEST['app_key']);
if(empty($appKey)){
    echo "empty app_key";
    exit;
}
$controlRet = controlVisitTimes($appKey);
if($controlRet['errCode'] == 408){
    echo "empty mod";
    exit;
}
$mod	=	isset($_REQUEST['mod']) ? $_REQUEST['mod']: "";
$act	=	isset($_REQUEST['act']) ? $_REQUEST['act']: "";
if(empty($mod)){
	echo "empty mod";
	exit;
}

if(empty($act)){
	echo "empty act";
	exit;
}
$modName	=	ucfirst($mod."Act");
$modClass	=	new $modName();

$actName	=	"act_".$act;
if(method_exists($modClass, $actName)){
	$ret	=	$modClass->$actName();
}else{
	echo $actName." no this act!!";
	exit;
}

$callback	=	isset($_REQUEST['callback']) ? $_REQUEST['callback']: "";
$jsonp		=	isset($_REQUEST['jsonp']) ? $_REQUEST['jsonp']: "";

$dat	=	array();
if(empty($ret)){
	$dat	=	array("errCode"=>$modName::$errCode, "errMsg"=>$modName::$errMsg, "data"=>"");
}else{
	$dat	=	array("errCode"=>$modName::$errCode, "errMsg"=>$modName::$errMsg, "data"=>$ret);
}

if(!empty($callback)){
	if(!empty($jsonp)){
		echo "try{ ".$callback."(".json_encode($dat)."); }catch(){alert(e);}";
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