<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();
$ebaybutt = A('EbayButt');
$ebaybutt->setToken("ddd");
$orderLists = $ebaybutt->spiderOrderLists(array('141496321560-1088114906004'));
	var_dump($spiderlists);exit;

$loop		= true;
$page		= 1;
$nowtime 	= time();

################################## start 这里可以扩展时间分页  ##################################
$ebay_start	= date('Y-m-d\TH:i:s', get_ebay_timestamp($nowtime-(3600)));
$ebay_end	= date('Y-m-d\TH:i:s', get_ebay_timestamp($nowtime));

$ebaybutt = A('EbayButt');
$ebaybutt->setToken("ddd");
$spiderlists = $ebaybutt->spiderOrderId($ebay_start, $ebay_end);
log::writeLog("ebayOrders = ".json_encode($spiderlists),"aaaa","aaaa","d");
var_dump($spiderlists);
//实例化队列
foreach ($spiderlists AS $spiderlist){
	$orderLists = $ebaybutt->spiderOrderLists(array('141496321560-1088114906004'));
	var_dump($spiderlists);exit;
}
echo "\n<====[".date('Y-m-d H:i:s')."]系统【结束】同步账号【 $account 】订单, 本次共处理".count($spiderlists)."条数据\n";
################################## end 这里可以扩展时间分页  ##################################
?>