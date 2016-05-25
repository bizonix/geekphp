<?php
	//未付款抓单应许抓取的料号
	$_allow_spide_itemid = array('140964081873','130881450826','130897197539','130764833258','130821799772','130818857110','140964087285','130897195973','130820022776','130818876597','251251828933','350762254788','350626780178','350643165801','251171970582','251243381001','251251856230','350674579598');
	
	$__liquid_items_array = PurchaseAPIModel::getAdjustransportFromPurchase();
	//var_dump($__liquid_items_array);
	//echo time(); echo "<br>";
	$__liquid_items_postbyhkpost = array_filter(explode(",", $__liquid_items_array['液体产品']));//液体产品
	$__liquid_items_postbyfedex = array_filter(explode(",", $__liquid_items_array['贵重产品']));//贵重物品走联邦
	$__liquid_items_cptohkpost = array_filter(explode(",", $__liquid_items_array['指甲油产品']));//指甲油转香港小包
	$__liquid_items_elecsku = array_filter(explode(",", $__liquid_items_array['电子类产品']));//电子类产品走香港小包
	//$__elecsku_countrycn_array = array_filter(explode(",", $__liquid_items_array['']));//电子类产品指定国家
	$__liquid_items_fenmocsku = array_filter(explode(",", $__liquid_items_array['粉末状产品'])); //粉末状SKU
	$__liquid_items_BuiltinBattery  = array_filter(explode(",", $__liquid_items_array['内置电池产品'])); //内置电池类产品
	$__liquid_items_SuperSpecific = array('6471','14995'); //超规格的产品，长度大于60cm, 三边大于 90cm
	$__liquid_items_Paste = array_filter(explode(",", $__liquid_items_array['膏状产品']));//膏状SKU*/
	$__liquid_items_elecWithoutBattery = array_filter(explode(",", $__liquid_items_array['电子类【不带电池】']));//电子类【不带电池】*/
	$__liquid_items_OutWeight = array_filter(explode(",", $__liquid_items_array['超重产品>=1.9kg']));//超重产品>=1.9kg*/
	
	//取统一包装材料重量数据
	//$MaterInfo = CommonModel::getMaterInfo();
	
	//取统一国家中文名对应英文名
	/*$ec = "select * from ebay_countrys where ebay_user='$user' ";
	$result = $dbConn->execute($ec);
	$ebay_country_lists = $dbConn->getResultArray($result);
	$global_countrycn_coutryen = array();
	foreach($ebay_country_lists AS $ebay_country_list){
		$global_countrycn_coutryen[trim($ebay_country_list['countryen'])] = trim($ebay_country_list['countrycn']);
	}*/
	
	//取各个平台的账号名称
	$SYSTEM_ACCOUNTS = OmAvailableModel::getPlatformAccount();
	//echo "<pre>";print_r($SYSTEM_ACCOUNTS);
	$express_delivery = array();
	$express_delivery_value = array();
	$no_express_delivery = array();
	$no_express_delivery_value = array();
	$express_delivery_value = CommonModel::getCarrierListById(1);
	/*foreach($express_delivery_arr as $value){
		$express_delivery_value[$value['id']] = $value['carrierNameCn'];
	}*/
	$express_delivery = array_keys($express_delivery_value);
	//var_dump($express_delivery);
	$no_express_delivery_value = CommonModel::getCarrierListById(0);
	/*foreach($no_express_delivery_arr as $value){
		$no_express_delivery_value[$value['id']] = $value['carrierNameCn'];
	}*/
	$no_express_delivery = array_keys($no_express_delivery_value);
	
?>