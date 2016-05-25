<?php
	//脚本参数检验
	if($argc<2){
		exit("Usage: /usr/bin/php	$argv[0] eBayAccount \n");
	}
	//账号检验
	$__ebayaccount=trim($argv[1]);
	if(!defined('WEB_PATH')){
		define("WEB_PATH","/data/web/order.valsun.cn/");
	}
	require_once WEB_PATH."crontab/scripts.comm.php";
	require_once WEB_PATH_CONF_SCRIPTS."script.ebay.config.php";
	require_once WEB_PATH_LIB_SDK_EBAY."GetCertainOrder.php";
	require_once WEB_PATH_LIB_SCRIPTS_EBAY."ebay_order_cron_func.php";
	
	$rmq_config	=	C("RMQ_CONFIG");
	$rabbitMQClass= new RabbitMQClass($rmq_config['fetchOrder'][1],$rmq_config['fetchOrder'][2],$rmq_config['fetchOrder'][4],$rmq_config['fetchOrder'][0]);//队列对象
	$omAvailableAct = new OmAvailableAct();
	$where = 'WHERE is_delete=0 ';
	$where .= 'AND platformId in(1,5) ';
	$GLOBAL_EBAY_ACCOUNT = $omAvailableAct->act_getTNameList2arrById('om_account', 'id', 'account', $where);
	
	$FLIP_GLOBAL_EBAY_ACCOUNT = array_flip($GLOBAL_EBAY_ACCOUNT);
	
	if(!preg_match('#^[\da-zA-Z]+$#i',$__ebayaccount)){
		exit("Invalid ebay account: $__ebayaccount!\n");
	}
	if(!in_array($__ebayaccount,$GLOBAL_EBAY_ACCOUNT)){
		exit("$__ebayaccount is not support now !\n");
	}
	
	//预先判断ebaytoken文件
	$__token_file = WEB_PATH_CONF_SCRIPTS_KEYS_EBAY.'keys_'.$__ebayaccount.'.php';
	if(!file_exists($__token_file)){
		exit($__token_file." does not exists!!!");
	}
	
	$express_delivery = array();
	$express_delivery_value = array();
	$no_express_delivery = array();
	$no_express_delivery_value = array();
	$express_delivery_arr = CommonModel::getTransCarrierInfo(1);
	foreach($express_delivery_arr['data'] as $value){
		$express_delivery_value[$value['id']] = $value['carrierNameCn'];
	}
	$express_delivery = array_keys($express_delivery_value);
	//var_dump($express_delivery);
	$no_express_delivery_arr = CommonModel::getTransCarrierInfo();
	foreach($no_express_delivery_arr['data'] as $value){
		$no_express_delivery_value[$value['id']] = $value['carrierNameCn'];
	}
	$no_express_delivery = array_keys($no_express_delivery_value);
	//var_dump($no_express_delivery); exit;
	
	#########全局变量设置########	
	date_default_timezone_set('Asia/Chongqing');      
    $detailLevel = 0;
	$Sordersn	= "eBay";
	
	$mctime		= time();      	
	$cc			= $mctime;
	$nowtime	= date("Y-m-d H:i:s",$cc);
	$nowd		= date("Y-m-d",$cc);
	#################以下账号用于测试#############	
	$account= $__ebayaccount;	
	#############类或API 实例化##############
	$api_gco=new GetCertainOrderAPI($__ebayaccount);
	//$oa	=new OrderAction();
	//程序计时器
	$time_start=$cc;
	echo "\n=====[".date('Y-m-d H:i:s',$time_start)."] 系统【开始】抓取账号【 $account 】订单 ====>\n\n";
	
	$api_gco->GetCertainOrder($account);//监听获取队列信息
	exit;
	
	//echo implode("\n", $dbConn->error)."\n\n";
	echo " =====[".date('Y-m-d H:i:s')."]系统【开始】计算【 $account 】\n ";
	//echo " 订单[".implode("\t",$_orderids)."]运费 ====>\n";
	
	echo " 等待计算运输方式的数量".count($orders)."\n";
	//auto_contrast_intercept($orders);//自动缺货拦截全部上线
	foreach($orders as $order){ // 判断 是否在黑名单里面
		$ebay_userid = $order['ebay_userid'];
		$ebay_username = $order['ebay_username'];
		$ebay_usermail = $order['ebay_usermail'];
		$ebay_street = $order['ebay_street'];
		$ebay_phone = $order['ebay_phone'];
		$ebay_account = $order['ebay_account'];
		$ebay_ordersn = $order['ebay_ordersn'];
		
		$sql = "select count(*)  as totalnum from ebay_blacklist ";
		$blackcondition = array();
		if($ebay_userid != ""){
			$blackcondition[] = "ebay_userid='{$ebay_userid}'";
		}
		if($ebay_username != ""){
			$blackcondition[] = "ebay_username='{$ebay_username}'";
		}
		if($ebay_usermail != ""){
			$blackcondition[] = "ebay_usermail='{$ebay_usermail}'";
		}
		if($ebay_street != ""){
			$blackcondition[] = "ebay_street='{$ebay_street}'";
		}
		if($ebay_phone != ""){
			$blackcondition[] = "ebay_phone='{$ebay_phone}'";
		}
		$bconditon = implode(' OR ', $blackcondition);
		$blackwhere = count($blackcondition)	> 0 ? " where ({$bconditon}) and ebay_accounts like '%[{$ebay_account}]%' " : 'where 0';
		$sql = $sql.$blackwhere;
		$log_sql = $sql;

		$sql	= $dbConn->query($sql);
		$black_list	= $dbConn->fetch_first($sql);
		if($black_list['totalnum'] > 0){
			$ss = "update ebay_order set ebay_status=684 where ebay_id={$order['ebay_id']}";

			if($dbConn->query($ss)){
				insert_mark_shipping($order['ebay_id']);
				echo "订单id{$order['ebay_id']}进入黑名单文件夹\n.{$log_sql}\n";
			}else{
				echo "订单id{$order['ebay_id']}移动进黑名单文件夹失败\n";
			}
		}
	}
	
	$time_end=time();
	echo "\n=====[耗时:".ceil(($time_end-$time_start)/60)."分钟]====\n";
	echo "\n<====[".date('Y-m-d H:i:s',$time_end)."]系统【结束】同步账号【 $account 】订单\n";
?>