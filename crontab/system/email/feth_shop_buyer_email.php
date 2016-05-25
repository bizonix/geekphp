<?php
set_time_limit(0);
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('Asia/Shanghai');
include_once str_replace(DIRECTORY_SEPARATOR, '/',substr(__DIR__,0,strpos(__DIR__,"crontab")))."framework.php";
Core::getInstance();

$companyId  = $argv[1];
$shopSelect = $argv[2];
$templateId = $argv[3];
//加入邮件发送队列后台发送
$nowTime = time();
if($shopSelect == "all"){
	//选择了所有店铺
	$where    = array("belong_company" => $companyId);
	$shops    = M("Shops")->getAllData("id",$where);
	$shopStr  = implode(",",$shops);
	$shopSql  = "shop_id IN ({$shopStr})";
}else{
	$shopSql = "shop_id = {$shopSelect}";
}
$orders = M("Order")->getAllData("id,create_time,shop_id","company_id = {$companyId} and source_platform !=4  and {$shopSql}");
foreach($orders as $k=>$v){
	M("OrderDetails")->setTablePrefix('_'.date('Y_m',$v["create_time"]));
	$buyerInfo = M("OrderDetails")->getSingleData("buyerInfo","id=".$v['id']);
	if(empty($buyerInfo['buyerInfo'])){
		continue;
	}
	$buyerInfo = json_decode($buyerInfo['buyerInfo'],true);

	$emailCount = M("EmailQueue")->getDataCount(array("email"=>$buyerInfo['email'],"company_id"=>$companyId));
	if($emailCount > 0){
		$updateData = array(
			"update_time" => $nowTime,
			"status"	  => 1,
		);
		M("EmailQueue")->updateDataWhere($updateData,array("email"=>$buyerInfo['email']));
	}else{
		$insertData = array(
			"template_id"   => $templateId,
			"email"         => $buyerInfo['email'],
			"shop_id"    	=> $v['shop_id'],
			"company_id"    => $companyId,
			"update_time"   => $nowTime,
			"add_time"      => $nowTime,
		);
		M("EmailQueue")->insertData($insertData);
	}
}
exec("php ".WEB_PATH."crontab/system/email/handle_all_email.php  &> /dev/null &");
