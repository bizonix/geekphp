<?php
error_reporting(-1);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
//加载框架核心库
include_once dirname(__DIR__)."/conf/define.php";
include_once WEB_PATH."framework.php";
Core::getInstance();
$memc_obj 	= new Cache(C('CACHEGROUP'));
//加载定时脚本通用文件
require_once WEB_PATH."conf/scripts/script.config.php";
?>