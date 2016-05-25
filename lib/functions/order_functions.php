<?php
/*
 * 订单相关通用函数
 * @add by lzx ,date 20140528
 */

/**
 * 根据平台id获取平台名称
 * @param int $id 平台编号
 * @return string
 * @author lzx
 */
function get_platnamebyid($id){
    $data = A('Platform')->act_getPlatformById($id);
	return isset($data['platform']) ? $data['platform'] : '';
}

/**
 * 根据账号id获取账号名称
 * @param int $id 账号id
 * @return string
 * @author lzx
 */
function get_accountnamebyid($id){
	$data   = A('Account')->act_getAccountById($id);
	return isset($data['account']) ? $data['account'] : '';
}

/**
 * 根据账号id获取平台id
 * @param int $id 账号id
 * @return id
 * @author yxd
 * */

function get_platFromidbyaccountid($id){
	$data            = A('Account')->act_getPlatformidByAccountid($id);
	$platformId      = $data[0]['platformId'];
	return $platformId;
}
/**
 * 根据账号id获取平台名称
 * @param int $id 账号id
 * @return string
 * @author yxd
 * */

function get_platnamebyaccountid($id){
	$platformId      = get_platFromidbyaccountid($id);
	$platformName    = get_platnamebyid($platformId);
	return $platformName;
}

/**
 * 根据运输方式id获取运输方式
 * @param int $id 运输方式id
 * @return string
 * @author lzx
 */
function get_carriernamebyid($id){
    $carrierlist = M('InterfaceTran')->key('id')->getCarrierList(2);
	return $carrierlist[$id]['carrierNameCn'];
	
}

/**
 * 根据运输方式获取运输方式id
 * @param string $name 运输方式
 * @return string
 * @author lzx
 */
function get_carrieridbyname($name){
    $carrierlist = M('InterfaceTran')->key('carrierNameCn')->getCarrierList(2);
	return $carrierlist[$name]['id'];
	
}

/**
 * 根据状态type获取状态类型名称
 * @param int $tid 状态码code
 * @return string
 * @author lzx
 */
function get_groupmenunamebyid($cid){
    $groupmenu = A('StatusMenu')->act_getGroupMenuByCode($cid);
	return $groupmenu['groupName'];
}

/**
 * 根据包材id获取名称
 * @param int $id 包材id
 * @return string
 * @author lzx
 */
function get_maternamebyid($id){
    $materlist = M('InterfacePc')->key('id')->getMaterList();
	return isset($materlist[$id]['pmName']) ? $materlist[$id]['pmName'] : '无';
}

/**
 * 根据包材名称获取id
 * @param string $name 包材名称
 * @return string
 * @author lzx
 */
function get_materidbyname($name){
    $materlist = M('InterfacePc')->key('pmName')->getMaterList();
	return isset($materlist[$name]['id']) ? $materlist[$name]['id'] : 0;
}

/**
 * 根据SKU信息获取每日销售情况
 * @param string $sku
 * @return string
 * @author lzx
 */
function get_skudailystatus($sku){
	$dailystatus = MC("SELECT * FROM ".C('DB_PREFIX')."sku_daily_status WHERE sku='{$sku}'", 900);
	return isset($dailystatus[0]) ? $dailystatus[0] : false;
}

function get_useracountpower($uid){
	$accounts = array();
	$competence = A('UserCompetence')->act_getCompetenceByUserId($uid);
	$lists = json_decode($competence['visible_platform_account'], true);
	foreach ($lists AS $platform=>$_account){
		$accounts = array_merge($accounts, $_account);
	}
	return $accounts;
}
function get_userplatacountpower($uid){
	$competence = A('UserCompetence')->act_getCompetenceByUserId($uid);
	return json_decode($competence['visible_platform_account'], true);
}

/**
 * 根据SKU获取对应的已订购数量
 * @param string $sku
 * @return int 已购数量
 * @author lzx
 */
function get_reservecount($sku){
	return M('InterfacePurchase')->getReserveCount($sku);
}

/**
 * 根据SKU|虚拟SKU信息获取相关组合情况
 * @param string $sku
 * @return array
 * @author lzx
 */
function get_orderskulist($sku){
	$orderskus = array();
	$skulists = M('InterfacePc')->getSkuinfo($sku);
	if (empty($skulists)){
		return false;
	}
	$combineskus = array();
	if ($skulists['isCombine']==1){
		if (strpos($sku, '*')!==false){
			list($a, $skupic) = explode('*', $sku);
		}else{
			$skupic = $sku;
		}
		$combineskus['spu']		= $sku;  //为特别设置spu 可以能存在不能找到图片bug
		$combineskus['sku']	   	= $sku;
		$combineskus['skupic'] 	= $skupic;
		$combineskus['amount'] 	= 1;
	}
	foreach ($skulists['skuInfo'] AS $_sku=>$skuinfo){
		$orderskus[$_sku]['spu'] 		 = $skuinfo['skuDetail']['spu'];
		$orderskus[$_sku]['sku'] 		 = $_sku;
		$orderskus[$_sku]['skupic'] 	 = $_sku;
		$orderskus[$_sku]['amount'] 	 = $skuinfo['amount'];
		$orderskus[$_sku]['goodsCost'] 	 = $skuinfo['skuDetail']['goodsCost'];
		$orderskus[$_sku]['purchaseId']  = $skuinfo['skuDetail']['purchaseId'];
		$orderskus[$_sku]['goodsStatus'] = $skuinfo['skuDetail']['goodsStatus'];
	}
	return array('realsku'=>$orderskus, 'combinesku'=>$combineskus, 'isCombine'=>$skulists['isCombine']);
}

/**
 * 跟进itemid获取URL地址
 * @param string $itemid
 * @return string
 * @author lzx
 */
function get_itemurl($itemid){
	switch ($itemid){
		case 1  : $url="http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem&item={$itemid}"; break;
		case 2  : $url="http://www.aliexpress.com/item/New-1mm-Silver-Metallic-Caviar-Beads-Studs-Nail-Art-Glitter-Nail-Decoration-13229/{$itemid}.html"; break;
		case 11 : $url="http://www.amazon.com/gp/product/{$itemid}"; break;
		default : $url="#";
	}
	return $url;
}
?>