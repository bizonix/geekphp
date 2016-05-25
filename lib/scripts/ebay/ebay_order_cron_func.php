<?php
	//传入参数为中国重庆时区时间戳
	function get_ebay_timestamp($time){
		//默认八小时时差
		return	$time-(3600*8);
	}
	//传入参数为UTC时区时间串
	function get_china_timestamp($timestr){
		return strtotime($timestr)+3600*8;
	}
	/*function str_rep($str){
		$str  = str_replace("'","&acute;",$str);
		$str  = str_replace("\"","&quot;",$str);
		return $str;
	}*/
	function addlogs($log_name,$log_operatetime,$log_orderid,$log_notes,$tname,$log_ebayaccount,$start,$end,$type){
		global $dbConn;
		$nowtime=date("Y-m-d H:i:s");
		$ss		= "insert into	system_log(log_name,log_operationtime,log_orderid,log_notes,ebay_user,
										   currentime,log_ebay_account,starttime,endtime,type) 
				   values('$log_name','$log_operatetime','$log_orderid','$log_notes','$tname',
						  '$nowtime','$log_ebayaccount','$start','$end','$type')";
		$dbConn->query($ss);
	}
	function calcshippingfee($totalweight,$ebay_countryname,$ebayid,$ebay_account,$ebay_total){
		global $dbConn,$user,$__liquid_items_postbyhkpost,$__liquid_items_postbyfedex,$__liquid_items_cptohkpost, $__liquid_items_elecsku, $global_countrycn_coutryen,$__elecsku_countrycn_array,$GLOBAL_EBAY_ACCOUNT,$__liquid_items_fenmocsku,$__liquid_items_BuiltinBattery,$__liquid_items_SuperSpecific,$__liquid_items_Paste,$SYSTEM_ACCOUNTS;

		$g_account = str_replace(',', '', $ebay_account);
		$ss		= "delete from ebay_lishicalcfee where orderid ='$ebayid' ";
		$dbConn->query($ss);
		
		$shippment_hkpost_directly	=	false;
		$shippment_fedex_directly	=	false;
		$shippment_cptohkpost	=	false;
		//$shippment_elec_directly = false;
		############single line item order中如果有液体的产品直接设为香港小包###########1/2
		####added by john 2012-05-16
		$ss     = "select ebay_ordersn,ebay_orderid,ebay_couny,ebay_currency from ebay_order where ebay_id =$ebayid ";
		$ss		= $dbConn->query($ss);
		$ss		= $dbConn->fetch_array_all($ss);
		
		$ebay_ordersn = $ss[0]['ebay_ordersn'];
		$ebay_orderid = $ss[0]['ebay_orderid'];
		$ebay_couny = $ss[0]['ebay_couny'];
		$ebay_currency = $ss[0]['ebay_currency'];
		
		$ss		= "select sku,ebay_itemprice from ebay_orderdetail where ebay_ordersn ='$ebay_ordersn'";
		$ss		= $dbConn->query($ss);
		$ss		= $dbConn->fetch_array_all($ss);
		$sku_arr = array();
		$eub_to_py = false;//包含单价小于等于五的料号
		foreach ($ss AS $_ss){
			if(function_exists("get_realskuinfo")){
				$skus = get_realskuinfo($_ss['sku']);
				foreach($skus as $k => $n){//支持组合料号
					$sku_arr[] = trim($k);
				}
			}else{
				$sku_arr[] = trim($_ss['sku']);
			}
			/* add by Herman.Xi @2013-07-16 */
			if($_ss['ebay_itemprice'] <= 5){
				$eub_to_py = true;
			}
		}
		$array_intersect_elec = array_intersect($sku_arr, $__liquid_items_elecsku);
		$array_intersect_gaoji = array_intersect($sku_arr, $__liquid_items_postbyfedex);
		$array_intersect_zhijiayou = array_intersect($sku_arr, $__liquid_items_cptohkpost);
		$array_intersect_yieti = array_intersect($sku_arr, $__liquid_items_postbyhkpost);
		
		$array_intersect_fenmocsku = array_intersect($sku_arr, $__liquid_items_fenmocsku);
		$array_intersect_BuiltinBattery = array_intersect($sku_arr, $__liquid_items_BuiltinBattery);
		$array_intersect_SuperSpecific = array_intersect($sku_arr, $__liquid_items_SuperSpecific);
		$array_intersect_Paste = array_intersect($sku_arr, $__liquid_items_Paste);
		
		/*if(count($array_intersect_elec) > 0 && in_array($global_countrycn_coutryen[$ebay_countryname],$__elecsku_countrycn_array)){
			$shippment_elec_directly	=	true;
			echo "料号[ ".join(', ', $array_intersect_elec)." ]为电子类产品,运到[ ".$global_countrycn_coutryen[$ebay_countryname]." ]需要直接走香港小包\n";
		}else */if(count($array_intersect_gaoji) > 0){
			$shippment_fedex_directly	=	true;
			echo "料号[ ".join(', ', $array_intersect_gaoji)." ]为高级产品,需要直接走FedEx\n";
		}else if(count($array_intersect_zhijiayou) > 0){
			$shippment_cptohkpost	=	true;
			echo "料号[ ".join(', ', $array_intersect_zhijiayou)." ]为指甲油产品,需要直接走香港小包\n";	
		}else if(count($array_intersect_yieti) > 0){
			$shippment_hkpost_directly	=	true;
			echo "料号[ ".join(', ', $array_intersect_yieti)." ]为液体产品,需要直接走中国邮政\n";
		}
		############single line item order中如果有液体的产品直接设为香港小包###########1/2
		
		############ebay设置特定国家走挂号，包含特殊料号走平邮，超过70走挂号，币种为美元和英镑###########START
		//ADD BY Herman.Xi @ 2013-07-02
		$ecsql = "select countrys from ebay_cpghcalcfee where ebay_user='$user' and name in ('第六组','第七组','第八组','第九组','第十组') ";
		$ecresult = $dbConn->query($ecsql);
		$ecarr = $dbConn->fetch_array_all($ecresult);
		$spec_countries = array('Turkey','Korea','North','Russian Federation','Spain','Armenia','Bosnia and Herzegovina','Vietnam','Palestine');
		$ec_countries = array();
		foreach($ecarr as $ecline){
			$strarr = array_filter(explode(',', $ecline['countrys']));
			foreach($strarr as $line){
				if(trim($line) != 'Puerto Rico'){ //波多黎各不挂号，add by herman.Xi @ 20130801
					$ec_countries[] = trim($line);
				}
			}
		}
		$union_countries = array_merge($spec_countries, $ec_countries);
		############ebay设置特定国家走挂号，包含特殊料号走平邮，超过70走挂号，币种为美元和英镑###########END
		
		$ss		= "select * from ebay_carrier where ebay_user ='$user' and country not like '%$ebay_countryname%'";
		$ss		= $dbConn->query($ss);
		$ss		= $dbConn->fetch_array_all($ss);
		
		$data	= array();
		for($i=0;$i<count($ss);$i++){
			
			$shipfee	= 0;
			
			$name		= $ss[$i]['name'];
			$kg			= $ss[$i]['kg'];
			$handlefee	= $ss[$i]['handlefee'];
			$id			= $ss[$i]['id'];
			$rate		= $ss[$i]['rate'];
			$min		= $ss[$i]['min']; // 是否满足挂号条件
			
			
			if($name  == '香港小包挂号' ){
				$shipfee= calchkghpost($totalweight,$ebay_countryname);
				/*if(($ebay_total >= $min) || (in_array($ebay_couny, array('AR','BR','PE','CL','PY','BO','EC','GF','CO','GY','SR','UY','VE','RU')) && ($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_BuiltinBattery) > 0 || count($array_intersect_Paste) > 0))){
					$gg		= "insert into ebay_lishicalcfee(name,value,shippingid,orderid,totalweight)
							  values('$name','$shipfee','$id','$ebayid','$totalweight')";
					echo "$name:$shipfee\n";
					$dbConn->query($gg);
				}*/
				if(($ebay_total >= $min)){
					$gg		= "insert into ebay_lishicalcfee(name,value,shippingid,orderid,totalweight)
							  values('$name','$shipfee','$id','$ebayid','$totalweight')";
					echo "$name:$shipfee\n";
					$dbConn->query($gg);
				}
			}
			/****************************************************/
			if($name  == '香港小包平邮'){			
				$shipfee	= calchkpost($totalweight,$ebay_countryname);
				echo "$name:$shipfee\n";
				$gg			= "insert into ebay_lishicalcfee(name,value,shippingid,orderid,totalweight) 
								values('$name','$shipfee','$id','$ebayid','$totalweight')";
				$dbConn->query($gg);
			}
			
			if($name  == 'EUB' && ($ebay_countryname == 'United States' || $ebay_countryname == 'US')){
			
				$discount	= $ss[$i]['discount']?$ss[$i]['discount']:1;
				if($totalweight <= 0.06){
					$shipfee	= 80*0.06+7;
				}else{
					$shipfee	= 80*$totalweight+7;
				}
				$shipfee	= $shipfee * $discount;
				$gg			= "insert into ebay_lishicalcfee(name,value,shippingid,orderid,totalweight) 
								values('$name','$shipfee','$id','$ebayid','$totalweight')";			
				$dbConn->query($gg);
				echo "$name:$shipfee\n";
			}
			
			if($name  == '中国邮政平邮'){
				$shipfee = calcchinapostpy($totalweight, $ebay_countryname);
				if($shipfee !== false){
					$gg		= "insert into ebay_lishicalcfee(name,value,shippingid,orderid,totalweight) 
								values('$name','$shipfee','$id','$ebayid','$totalweight')";
					if($dbConn->query($gg)){
						
					}else{
						echo "Fail : $gg\n";
					}
					echo "$name : $shipfee 满足重量区间: $totalweight 如果有重量区间,则以后面重量计算\n";
				}else{
					echo "{$ebay_countryname} 未开通中国邮政平邮\n";				
				}
			}
			
			if($name  == '中国邮政挂号'){
				$shipfee = calcchinapostgh($totalweight, $ebay_countryname);
				if($shipfee !== false){
					$gg		= "insert into ebay_lishicalcfee(name,value,shippingid,orderid,totalweight) 
								values('$name','$shipfee','$id','$ebayid','$totalweight')";
					if($dbConn->query($gg)){
						
					}else{
						echo "Fail : $gg\n";
					}
					echo "$name : $shipfee 满足重量区间: $totalweight 如果有重量区间,则以后面重量计算\n";
				}else{
					echo "{$ebay_countryname} 未开通中国邮政挂号\n";
				}
			}
			
			if($name  == 'EMS'){			
				$dd		= "SELECT * FROM  `ebay_emscalcfee` where countrys like '%$ebay_countryname%' ";
				$dd		= $dbConn->query($dd);
				$dd		= $dbConn->fetch_array_all($dd);
				$firstweight	= $dd[0]['firstweight'];
				$nextweight		= $dd[0]['nextweight'];
				$discount		= $dd[0]['discount'];
				$firstweight0	= $dd[0]['firstweight0'];
				$files			= $dd[0]['files'];
				$declared_value = $dd[0]['declared_value'];
				
				if($files == '1' && $totalweight <= 0.5){
					$firstweight	= $firstweight0;
				}
				
				if($totalweight <= 0.5){
					$shipfee	= $firstweight;
				}else{
					$shipfee	= ceil((($totalweight*1000-500)/500))*$nextweight + $firstweight;
				}
			
				$shipfee	= $shipfee *$discount+$declared_value;
				
				if($totalweight > 0){
				
					$gg		= "insert into ebay_lishicalcfee(name,value,shippingid,orderid,totalweight) 
								values('$name','$shipfee','$id','$ebayid','$totalweight')";
					$dbConn->query($gg);
				}
				echo "$name : $shipfee 满足重量区间:$totalweight 如果有重量区间,则以后面重量计算\n";
			
			}
			
			if ($name  == 'FedEx'){
				if($shippment_cptohkpost === true || count($array_intersect_fenmocsku) > 0 || count($array_intersect_BuiltinBattery) > 0 || count($array_intersect_Paste) > 0 ){
					echo "包含特殊料号不走联邦!\n"; //add by Herman.Xi
				}else{
					$shipfee	= calcfedex($totalweight, $ebay_countryname, $ebayid);
					$gg		= "insert into ebay_lishicalcfee(name,value,shippingid,orderid,totalweight) 
										values('$name','$shipfee','$id','$ebayid','$totalweight')";
					$dbConn->query($gg);
					echo "$name : $shipfee 满足重量区间:$totalweight 如果有重量区间,则以后面重量计算\n";
				}
			}
			
			if ($name  == 'Global Mail'&&in_array($g_account, $SYSTEM_ACCOUNTS['海外销售平台'])){
				$shipfee	= calcglobalmail($totalweight, $ebay_countryname);
				$gg		= "insert into ebay_lishicalcfee(name,value,shippingid,orderid,totalweight) 
									values('$name','$shipfee','$id','$ebayid','$totalweight')";
				$dbConn->query($gg);
				echo "$name : $shipfee 满足重量区间:$totalweight 如果有重量区间,则以后面重量计算\n";
			}
			
			if ($name  == 'DHL'){
				$shipfee	= calcdhlshippingfee($totalweight, $ebay_countryname);
				$gg		= "insert into ebay_lishicalcfee(name,value,shippingid,orderid,totalweight) 
									values('$name','$shipfee','$id','$ebayid','$totalweight')";
				$dbConn->query($gg);
				echo "$name : $shipfee 满足重量区间:$totalweight 如果有重量区间,则以后面重量计算\n";
			}
			
		}
		//sleep(10);//主从同步延时
		$ss		= "select * from ebay_carrier where ebay_account like '%$ebay_account%'";
		$ss		= $dbConn->query($ss);
		$ss		= $dbConn->fetch_array_all($ss);
		
		$ff	= 0;
		if(count($ss) > 0){
			$ff	= 1;
		}
		
		$ss = "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name !='EUB' order by value asc ";
		$ss		= $dbConn->query($ss);
		$ss		= $dbConn->fetch_array_all($ss);
		
		/*##############中国邮政挂号(总价大于40走挂号)#############START
		if ($ebay_total > 40){
			$ss = "select * from ebay_lishicalcfee where name = '中国邮政挂号' and orderid ='$ebayid' ";
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}
		##############Global Mail(海外销售专走)#############START
		if (in_array($g_account, $SYSTEM_ACCOUNTS['海外销售平台'])){
			$ss = "select * from ebay_lishicalcfee where name = 'Global Mail' and orderid ='$ebayid' ";
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}else
		##############(sunwebzone,enjoy24hours,charmday88,betterdeals255,360beauty这5个账号除了以上的设置，还需要额外对US站点进行以下修改：针对第6点，ERP在同步订单的时候，订单金额(不包含运费)小于等于5.00(不限币种)，发货国家为美国或者波多黎各时，自动选择一个最便宜的运输方式，不受EUB影响#############START
		if (in_array($g_account, array('sunwebzone','enjoy24hours','charmday88','betterdeals255','360beauty')) && ($ebay_countryname == 'United States' ||  $ebay_countryname == 'US' || $ebay_countryname == 'Puerto Rico') && $eub_to_py){
			if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_BuiltinBattery) > 0 || count($array_intersect_Paste) > 0){
				if($ebay_total >= 70 ){
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='香港小包挂号' order by value asc ";
				}else{
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name = '香港小包平邮' order by value asc ";			
				}	
			}else{
				if($ebay_total > 40 ){
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政挂号' order by value asc ";
				}else{
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政平邮' order by value asc ";	
				}
			}
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}else
		##############EUB(EBAY US 站点)#############START
		if($ff == 1 && ($ebay_countryname == 'United States' ||  $ebay_countryname == 'US' || $ebay_countryname == 'Puerto Rico')){
			$ss = "select * from ebay_lishicalcfee where name = 'EUB' and orderid ='$ebayid' ";
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}else
		##############FedEx(包含单个超重料号或者贵重SKU)#############START
		if ($shippment_fedex_directly===true){
			$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name = 'FedEx' order by value asc ";			
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}else
		//modified by Herman.Xi @ 2013-07-08
		if(in_array($g_account,array('betterdeals255','dealinthebox','easytrade2099','bestinthebox','fiveseason88','befdi','enicer','mysoulfor','newcandy789','estore456','eseasky68','swzeagoo','happyzone80','infourseas','emallzone','unicecho','vobeau','blessedness365'))){
			if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_BuiltinBattery) > 0 || count($array_intersect_Paste) > 0){
				if($ebay_total >= 70 ){
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='香港小包挂号' order by value asc ";
				}else{
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name = '香港小包平邮' order by value asc ";			
				}
			}else{
				if($ebay_total > 40 ){
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政挂号' order by value asc ";
				}else{
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政平邮' order by value asc ";	
				}
			}
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}
		else
		//以下这些国家在同步订单的时候，金额大于10才挂号寄出，不管什么币种都行
		//Japan,Korea, South,Malaysia,Singapore,Portugal,Czech Republic,Italy,Israel,Ireland 
		if(in_array($ebay_countryname, array('Japan','Korea, South','Malaysia','Singapore','Portugal','Czech Republic','Italy','Israel','Ireland')) && $ebay_total > 10){
			if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_BuiltinBattery) > 0 || count($array_intersect_Paste) > 0){
				if($ebay_total >= 70 ){
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='香港小包挂号' order by value asc ";
				}else{
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name = '香港小包平邮' order by value asc ";			
				}
			}else{
				$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政挂号' order by value asc ";	
			}
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}else
		//南美洲国家开放走挂号，特殊料号走香港小包挂号
		//modified by Herman.Xi @ 2013.05.28
		//if(in_array($ebay_couny, array('AR','BR','PE','CL','PY','BO','EC','GF','CO','GY','SR','UY','VE','RU'))){
		if(in_array($ebay_countryname, $union_countries) && in_array($ebay_currency, array('GBP','USD'))){//指定的这些国家，并且订单币种为美元和英镑 Modified by Herman.Xi @ 2013-07-02
			if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_BuiltinBattery) > 0 || count($array_intersect_Paste) > 0){
				if($ebay_total >= 70 ){
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='香港小包挂号' order by value asc ";
				}else{
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name = '香港小包平邮' order by value asc ";			
				}
			}else{
				$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政挂号' order by value asc ";	
			}
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}else
		##############液体(不含指甲油)SKU(中国邮政转香港小包)#############START
		##############指甲油SKU(中国邮政转香港小包)#############START
		##############电子类产品SKU(指定国家的订单 走香港小包)#############START
		##############内置电池SKU(中国邮政转香港小包)#############START
		##############膏状SKU(中国邮政转香港小包)#############START
		//add by Herman.Xi 2013-03-14
		if($shippment_cptohkpost === true || $shippment_hkpost_directly ===true || count($array_intersect_BuiltinBattery) > 0 || count($array_intersect_Paste) > 0){
			if($ebay_total >= 70 ){
				$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='香港小包挂号' order by value asc ";
			}else{
				$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name = '香港小包平邮' order by value asc ";			
			}
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}*/
		//implode("\n", $dbConn->error)."\n\n";
		//modified by Herman.Xi @ 2013-07-18 23:45 所有订单按照原始逻辑判断最优运输方式计算
		##############Global Mail(海外销售专走)#############START
		//modified by Herman.Xi @ 2013-07-20 9:44 走意大利和包含特殊料号的订单保留原GM运输方式，非意大利国家订单不包含特殊料号走中国邮政平邮（只限于cndirect55,futurestar99）
		/*if (in_array($g_account, $SYSTEM_ACCOUNTS['海外销售平台'])){
			//add by Herman.Xi @ 20130725 小语种账号 1 欧元 冲销量 走中国邮政平邮
			if(in_array($g_account, array('cndirect998','easydealhere','tradekoo','allbestforu','easydeal365','enjoytrade99','freemart21cn','ishop2099')) && $ebay_total == 1 && $ebay_currency == 'EUR'){
				$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政平邮' order by value asc ";
			}else
			//add by Herman.Xi @ 2013-07-24 eshoppingstar75,ishoppingclub68 两个账号，已经账号easyshopping095 当国家为美国是走联邦
			if((in_array($g_account, array('eshoppingstar75','ishoppingclub68'))) || (in_array($g_account, array('easyshopping095')) && ($ebay_countryname == 'United States' || $ebay_countryname == 'US'))){
				if($shippment_cptohkpost === true || count($array_intersect_fenmocsku) > 0 || count($array_intersect_BuiltinBattery) > 0 || count($array_intersect_Paste) > 0 ){
					$ss = "select * from ebay_lishicalcfee where name = 'Global Mail' and orderid ='$ebayid' ";
				}else{
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name = 'FedEx' order by value asc ";
				}
			}else
			if ($ebay_countryname!='Italia'){
				if ($shippment_fedex_directly===true){
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name = 'FedEx' order by value asc ";			
				}else
				if(in_array($g_account, array('cndirect55','futurestar99','easydeal365','cndirect998'))){
					if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_BuiltinBattery) > 0 || count($array_intersect_Paste) > 0){
						$ss = "select * from ebay_lishicalcfee where name = 'Global Mail' and orderid ='$ebayid' ";
					}else{
						if($ebay_total > 40 ){
							$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政挂号' order by value asc ";
						}else{
							$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政平邮' order by value asc ";	
						}
					}
				}else{
					$ss = "select * from ebay_lishicalcfee where name = 'Global Mail' and orderid ='$ebayid' ";	
				}
			}else{
				$ss = "select * from ebay_lishicalcfee where name = 'Global Mail' and orderid ='$ebayid' ";
			}
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}*/
		if(in_array($g_account, array('enjoytrade99','allbestforu','freemart21cn','easydealhere')) && in_array($ebay_countryname, array('Deutschland','Frankreich','Spanien','Italien','Allemagne','France','Espagne','Italie','Alemania','Francia','España','Italia','Germania','Francia','Spagna','Italia'))){
			$ss = "select * from ebay_lishicalcfee where name = 'Global Mail' and orderid ='$ebayid' ";
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}else
		##############FedEx(包含单个超重料号或者贵重SKU)#############START
		if ($shippment_fedex_directly===true){
			$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name = 'FedEx' order by value asc ";			
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}else
		/*
		 *(陈小霞)2013-08-31 09:44:11
		 *帮忙设置下这两个账号总金额(价格+运费)超过5的，发往美国和波多黎各的，改为EUB发货：mysoulfor,newcandy789
		 *雷贤容 加上 estore456
		*/
		if(in_array($ebay_countryname, array('United States','US','Puerto Rico')) && $ebay_total >= 5 && in_array($g_account,array('mysoulfor','newcandy789','estore456'))){
			$ss = "select * from ebay_lishicalcfee where name = 'EUB' and orderid ='$ebayid' ";
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}else
		##############EUB(EBAY US 站点)#############START
		if($ff == 1 && ($ebay_countryname == 'United States' ||  $ebay_countryname == 'US')){
			$ss = "select * from ebay_lishicalcfee where name = 'EUB' and orderid ='$ebayid' ";
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}
		else
		/*
		 *(陈小霞)2013-09-06 09:44:11
		 *帮忙设置下这些账号的Russian Federation,Russia,Brazil,Brasil,Argentina三个国家总金额超过8的，设置挂号发货，其他账号或者其他国家仍然跟以前一样，中国邮政超过40挂号，香港小包超过70挂号
		*/
		if(in_array($ebay_countryname, array('Russian Federation','Russia','Brazil','Brasil','Argentina')) && $ebay_total >= 8 && in_array($g_account,array('365digital','digitalzone88','itshotsale77','cndirect998','cndirect55','befdi','easydeal365','enicer','doeon','starangle88','zealdora','360beauty','befashion','charmday88','dresslink','easebon','work4best','eshop2098','happydeal88','easytrade2099','easyshopping678','futurestar99','wellchange','voguebase55')) && in_array($ebay_currency, array('GBP','USD','EUR'))){
			if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_Paste) > 0){
			//if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_BuiltinBattery) > 0 || count($array_intersect_Paste) > 0){ //20130905内置电池不走香港小包
				if($ebay_total >= 70 ){
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='香港小包挂号' order by value asc ";
				}else{
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name = '香港小包平邮' order by value asc ";			
				}
			}else{
				$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政挂号' order by value asc ";	
			}
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}
		/*else
		//modified by Herman.Xi @ 2013-07-08
		if(in_array($g_account,array('betterdeals255','dealinthebox','easytrade2099','bestinthebox','fiveseason88','befdi','enicer','mysoulfor','newcandy789','estore456','eseasky68','swzeagoo','happyzone80','infourseas','emallzone','unicecho','vobeau','blessedness365','niceforu365','365digital','charmday88','choiceroad','easebon'))){
			if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_Paste) > 0){
			//if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_BuiltinBattery) > 0 || count($array_intersect_Paste) > 0){ //20130905内置电池不走香港小包
				if($ebay_total >= 70 ){
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='香港小包挂号' order by value asc ";
				}else{
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name = '香港小包平邮' order by value asc ";			
				}
			}else{
				if($ebay_total > 40 ){
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政挂号' order by value asc ";
				}else{
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政平邮' order by value asc ";	
				}
			}
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}*/
		/*else
		//以下这些国家在同步订单的时候，金额大于10才挂号寄出，不管什么币种都行
		//Japan,Korea, South,Malaysia,Singapore,Portugal,Czech Republic,Italy,Israel,Ireland 
		if(in_array($ebay_countryname, array('Japan','Korea, South','Malaysia','Singapore','Portugal','Czech Republic','Italy','Israel','Ireland')) && $ebay_total > 10){
			if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_Paste) > 0){
			//if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_BuiltinBattery) > 0 || count($array_intersect_Paste) > 0){ //20130905内置电池不走香港小包
				if($ebay_total >= 70 ){
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='香港小包挂号' order by value asc ";
				}else{
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name = '香港小包平邮' order by value asc ";			
				}
			}else{
				$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政挂号' order by value asc ";	
			}
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}*/
		/*else
		//南美洲国家开放走挂号，特殊料号走香港小包挂号
		//modified by Herman.Xi @ 2013.05.28
		//if(in_array($ebay_couny, array('AR','BR','PE','CL','PY','BO','EC','GF','CO','GY','SR','UY','VE','RU'))){
		if(in_array($g_account, array('keyhere','befdimall','Doeon','digitalzone88','enjoy24hours','sunwebhome','befashion','sunwebzone','wellchange','360beauty','itshotsale77','elerose88','cafase88','niceinthebox','starangle88','zealdora','voguebase55','dresslink','happydeal88','easyshopping678','work4best','eshop2098','estore2099')) && in_array($ebay_countryname, $union_countries) && in_array($ebay_currency, array('GBP','USD'))){//指定的这些国家，并且订单币种为美元和英镑 Modified by Herman.Xi @ 2013-07-02 last modified by Herman.Xi @ 2013-07-20 加账号限制
			if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_Paste) > 0){
			//if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_BuiltinBattery) > 0 || count($array_intersect_Paste) > 0){ //20130905内置电池不走香港小包
				if($ebay_total >= 70 ){
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='香港小包挂号' order by value asc ";
				}else{
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name = '香港小包平邮' order by value asc ";			
				}
			}else{
				$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政挂号' order by value asc ";	
			}
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);
		}*/
		else
		{
			if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_Paste) > 0){
			//if($shippment_cptohkpost === true || $shippment_hkpost_directly === true || count($array_intersect_BuiltinBattery) > 0 || count($array_intersect_Paste) > 0){ //20130905内置电池不走香港小包
				if($ebay_total >= 70 ){
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='香港小包挂号' order by value asc ";
				}else{
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name = '香港小包平邮' order by value asc ";			
				}	
			}else{
				if($ebay_total > 40 ){
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政挂号' order by value asc ";
				}else{
					$ss 	= "select * from ebay_lishicalcfee where orderid ='$ebayid' and value != '0' and name ='中国邮政平邮' order by value asc ";	
				}
			}
			$ss		= $dbConn->query($ss);
			$ss		= $dbConn->fetch_array_all($ss);	
		}
		$ssname	= $ss[0]['name'];
		$value	= $ss[0]['value'];
		echo "最终使用:$ssname---$value\n";
		$totalweight	= $ss[0]['totalweight'];
		
		if($totalweight == 0){$ssname = "中国邮政平邮";}//当可能出现总重量为零的情况下将运输方式设置为中国邮政平邮 add by Herman.Xi 2012-11-01
		$data	= array();
		$data[0]	= $ssname;
		$data[1]	= $value;
		$data[2]	= $totalweight;
		return $data;						
	}
	
	/*function calceveryweight($weightarray, $totalfee){
		$feearray = array();
		$totalweight = array_sum($weightarray);
		foreach ($weightarray AS $weight){
			$feearray[] = round(($totalfee*$weight/$totalweight), 2);
		}
		return $feearray;
	}*/
	
	function CheckOrderSN($recordnumber,$account){
		global $dbConn;		
		$sql	= " select ebay_ordersn from ebay_order 
					where recordnumber='$recordnumber' and ebay_account='$account'";
		$sql  	= $dbConn->query($sql);
		$sql  	= $dbConn->fetch_array_all($sql);
		if(count($sql) == 0){
			$status	= "0";	
			echo "未添加 需入库\n";
		}else{
			$status = $sql[0]['ebay_ordersn'];
			echo "已经存在\n";
		}
		return $status;
	}
	/*function CheckeBayOrderIDExists($oSellerOrderID,$ebay_account){
		global $dbConn;		
		$sql	= "select * from om_order_ids where orderid='".$oSellerOrderID."' and account='".$ebay_account."'";
		$sql  	= $dbConn->query($sql);
		$sql  	= $dbConn->fetch_array_all($sql);
		if(count($sql) == 0){
			echo "未添加 需入库\n";
			return false;
		}else{
			echo "已经存在\n";
			//@pop_ebay_orderid_queue($oSellerOrderID,$ebay_account);
			return true;
		}
	}*/
	function get_good_location($sku,$user){
		global $dbConn;
		if(strpos($sku, '*')!==false){
			$skus = explode('*',$sku);
			$sku = $skus[1];
		}
		$ss	= "SELECT goods_location FROM  `ebay_goods` where ebay_user='$user' and goods_sn='$sku'";
		$ss	= $dbConn->query($ss);
		$ss	= $dbConn->fetch_array_all($ss);
		return @$ss[0]['goods_location'];
	}
	function calc_packingweight($sku,$user){
		global $dbConn;
		$ee	= "SELECT ebay_packingmaterial,goods_weight,capacity 
				FROM ebay_goods 
				where goods_sn='$sku' 
				and ebay_user='$user' limit 1";
		echo "$ee\n";
		$ee	= $dbConn->query($ee);
		$ee = $dbConn->fetch_array_all($ee);
		print_r($ee[0]);
		return $ee[0];
	}
	//计算某物品的包装材质及包裹重量
	//返回:包裹类型
	function calc_itemandpacking_weight($sku,$ebay_amount,$user,&$totalweight){
		global $dbConn,$global_packingmaterial_weight;
		/* 开始检查是否是组合产品 */
		$rr	= " select 	goods_sncombine 
				from 	ebay_productscombine 
				where 	ebay_user='$user' 
				and 	goods_sn='$sku'";
		$rr	= $dbConn->query($rr);
		$rr = $dbConn->fetch_array_all($rr);
		if(count($rr) > 0){
			$goods_sncombine	= $rr[0]['goods_sncombine'];
			$goods_sncombine	= explode(',',$goods_sncombine);
			if(count($goods_sncombine) == 1){
				$pline		= explode('*',$goods_sncombine[0]);
				$goods_sn	= $pline[0];
				$goddscount = $pline[1] * $ebay_amount;
				unset($pline);
				
				$packingweight_data=calc_packingweight($goods_sn,$user);
				$ebay_packingmaterial	= $packingweight_data['ebay_packingmaterial'];			
				$goods_weight	= $packingweight_data['goods_weight'];	// 产品重量
				$capacity	= $packingweight_data['capacity'];	//产品容量
				unset($packingweight_data);
				
				$pweight = @$global_packingmaterial_weight[$ebay_packingmaterial];
				/*if($goddscount <= $capacity){
					$totalweight  += $pweight*$goddscount + ($goods_weight * $goddscount);
				}else{
					// 计算多个包材的重量   $ebay_amount 单个sku购买的数量 ebay_packingmaterial 包材的重量
					$totalweight2 += $goods_weight*$ebay_amount + $pweight;
				}	*/
				if($goddscount <= $capacity){
					$totalweight			+= $pweight + ($goods_weight * $goddscount);
				}else{
					// 计算多个包材的重量   $ebay_amount 单个sku购买的数量 ebay_packingmaterial 包材的重量
					$totalweight			+= (1 + ($goddscount-$capacity)/$capacity*0.6)*$pweight + ($goods_weight * $goddscount);
				}
			}else{
				for($e=0;$e<count($goods_sncombine);$e++){
					$pline		= explode('*',$goods_sncombine[$e]);
					$goods_sn	= $pline[0];
					$goddscount = $pline[1] * $ebay_amount;
					unset($pline);
					
					$packingweight_data=calc_packingweight($goods_sn,$user);
					$ebay_packingmaterial	= $packingweight_data['ebay_packingmaterial'];			
					$goods_weight	= $packingweight_data['goods_weight'];	// 产品重量
					$capacity	= $packingweight_data['capacity'];	//产品容量
					unset($packingweight_data);
					
					$pweight = @$global_packingmaterial_weight[$ebay_packingmaterial];
					/*if($goddscount <= $capacity){
						$totalweight  += $pweight*$goddscount + ($goods_weight * $goddscount);
					}else{
						// 计算多个包材的重量   $ebay_amount 单个sku购买的数量 ebay_packingmaterial 包材的重量
						$totalweight2 += $goods_weight*$ebay_amount + $pweight;
					}*/
					$totalweight		+= ($goddscount/$capacity)*0.6*$pweight + ($goods_weight * $goddscount);
				}
			}
			//if($totalweight2>0) $totalweight2	+= 0.6*$pweight ;
		}else{
			$packingweight_data=calc_packingweight($sku,$user);
			$ebay_packingmaterial	= $packingweight_data['ebay_packingmaterial'];			
			$goods_weight	= $packingweight_data['goods_weight'];	// 产品重量
			$capacity	= $packingweight_data['capacity'];	//产品容量
			unset($packingweight_data);
			
			$pweight= @$global_packingmaterial_weight[$ebay_packingmaterial];
			
			/*if($ebay_amount <= $capacity){				
				$totalweight += $pweight + $goods_weight*$ebay_amount;				
			}else{
				// 计算多个包材的重量   $ebay_amount 单个sku购买的数量 ebay_packingmaterial 包材的重量
				$totalweight2	+= $goods_weight*$ebay_amount + $pweight;				
			}*/
			if($ebay_amount <= $capacity){
				$totalweight			+= $pweight + $goods_weight*$ebay_amount;
			}else{
				// 计算多个包材的重量   $ebay_amount 单个sku购买的数量 ebay_packingmaterial 包材的重量
				$totalweight			+= (1 + ($ebay_amount-$capacity)/$capacity*0.6)*$pweight + ($goods_weight * $ebay_amount);
			}
			//if($totalweight2>0) $totalweight2	+= 0.6 * $pweight ;
		}
		echo " sku(重量):$sku($goods_weight) 包装材料(重量):$ebay_packingmaterial($pweight)\n";
		return	$ebay_packingmaterial;
	}
	
	function recalcorderweight($ordersn, &$ebay_packingmaterial){
		/*
		***add by Herman.Xi
		***create date 20121012
		***计算订单总重量***
		一、单料号,数量为1:
			重量=料号重量+包材重量。
		二、单料号,数量为多个:
			当总数小于包材容量时,重量=料号重量*总数+包材重量;
			当总数大于包材容量时,重量=料号重量*总数+1个包材重量+(总数-包材容量)/包材容量*0.6*包材重量。
		三、单料号组合:
			当总数小于包材容量时,重量=料号重量*总数+包材重量;
			当总数大于包材容量时,重量=料号重量*总数+1个包材重量+(总数-包材容量)/包材容量*0.6*包材重量。
		四、多料号组合:
			重量=(料号1总数/包材1容量)*0.6*包材1重量 + (料号1重量 * 料号1总数)
				+(料号2总数/包材2容量)*0.6*包材2重量 + (料号2重量 * 料号2总数) 
				+ ....
		
		注:'/'是除,'*'是乘,'%'是求余。
		*/
		global $dbConn, $user;
			
		/* 计算包装材料和订单总重量 */
		$st	= "select * from ebay_orderdetail where ebay_ordersn='$ordersn'";
		$st = $dbConn->query($st);
		$st	= $dbConn->fetch_array_all($st);
		
		$totalweight = 0;
		if(count($st)  == 1){
			/* 计算订单中单个物品包材的重量 */
			$sku						=  $st[0]['sku'];
			$ebay_amount				=  $st[0]['ebay_amount'];
			
			/* 开始检查是否是组合产品 */
			$rr			= "select * from ebay_productscombine where ebay_user='$user' and goods_sn='$sku'";
			$rr			= $dbConn->query($rr);
			$rr 	 	= $dbConn->fetch_array_all($rr);
			if(count($rr) > 0){
				$goods_sncombine	= $rr[0]['goods_sncombine'];
				$goods_sncombine    = explode(',',$goods_sncombine);
				if(count($goods_sncombine) == 1){
					$pline			= explode('*',$goods_sncombine[0]);
					$goods_sn		= $pline[0];
					$goddscount     = $pline[1] * $ebay_amount;
	
					$ee			= "SELECT * FROM ebay_goods where goods_sn='$goods_sn' and ebay_user='$user'";
					$ee			= $dbConn->query($ee);
					$ee 	 	= $dbConn->fetch_array_all($ee);
					$ebay_packingmaterial		=  $ee[0]['ebay_packingmaterial'];			
					$goods_weight				=  $ee[0]['goods_weight'];					// 产品重量子力学
					$capacity					=  $ee[0]['capacity'];						//产品容量
					
					$ss					= "select * from ebay_packingmaterial where  model='$ebay_packingmaterial' and ebay_user ='$user' ";
					$ss					= $dbConn->query($ss);
					$ss					= $dbConn->fetch_array_all($ss);
					$pweight			= $ss[0]['weight'];
					
					if($goddscount <= $capacity){
						$totalweight			= $pweight + ($goods_weight * $goddscount);
					}else{
						// 计算多个包材的重量   $ebay_amount 单个sku购买的数量 ebay_packingmaterial 包材的重量
						$totalweight			= (1 + ($goddscount-$capacity)/$capacity*0.6)*$pweight + ($goods_weight * $goddscount);
						//$totalweight			= (($goddscount/$capacity) + ((($goddscount%$capacity)/$capacity)*0.6))*$pweight + ($goods_weight * $goddscount);
					}
				}else{
					for($e=0;$e<count($goods_sncombine);$e++){
						$pline			= explode('*',$goods_sncombine[$e]);
						$goods_sn		= $pline[0];
						$goddscount     = $pline[1] * $ebay_amount;
					
						$ee			= "SELECT * FROM ebay_goods where goods_sn='$goods_sn' and ebay_user='$user'";
						$ee			= $dbConn->query($ee);
						$ee 	 	= $dbConn->fetch_array_all($ee);
						$ebay_packingmaterial		=  $ee[0]['ebay_packingmaterial'];			
						$goods_weight				=  $ee[0]['goods_weight'];					// 产品重量子力学
						$capacity					=  $ee[0]['capacity'];						//产品容量
						
						$ss					= "select * from ebay_packingmaterial where  model='$ebay_packingmaterial' and ebay_user ='$user' ";
						$ss					= $dbConn->query($ss);
						$ss					= $dbConn->fetch_array_all($ss);
						$pweight			= isset($ss[0]['weight']) ? $ss[0]['weight'] : 0;
						
						$totalweight		+= ($goddscount/$capacity)*0.6*$pweight + ($goods_weight * $goddscount);
					}
				}
			}else{
				$ss							= "select * from ebay_goods where goods_sn='$sku' and ebay_user ='$user' ";
				$ss							= $dbConn->query($ss);
				$ss							= $dbConn->fetch_array_all($ss);
				$ebay_packingmaterial		=  $ss[0]['ebay_packingmaterial'];			
				$goods_weight				=  $ss[0]['goods_weight'];					// 产品重量子力学
				$capacity					=  $ss[0]['capacity'];						//产品容量
				
				$ss					= "select * from ebay_packingmaterial where  model='$ebay_packingmaterial' and ebay_user ='$user' ";
				$ss					= $dbConn->query($ss);
				$ss					= $dbConn->fetch_array_all($ss);
				$pweight			= isset($ss[0]['weight']) ? $ss[0]['weight'] : 0;
	
				if($ebay_amount <= $capacity){
					$totalweight			= $pweight + ($goods_weight * $ebay_amount);
				}else{
					// 计算多个包材的重量   $ebay_amount 单个sku购买的数量 ebay_packingmaterial 包材的重量
					$totalweight			= (1 + ($ebay_amount-$capacity)/$capacity*0.6)*$pweight + ($goods_weight * $ebay_amount);
					//$totalweight			= (($ebay_amount/$capacity) + ((($ebay_amount%$capacity)/$capacity)*0.6))*$pweight + ($goods_weight * $ebay_amount);
				}
			}
			
		}else{
			
			/* 计算订单中多个物品包材的重量 */
			for($f=0;$f<count($st); $f++){
				$sku						=  $st[$f]['sku'];
				$ebay_amount				=  $st[$f]['ebay_amount'];
				
				/* 开始检查是否是组合产品 */
				$rr			= "select * from ebay_productscombine where ebay_user='$user' and goods_sn='$sku'";
				$rr			= $dbConn->query($rr);
				$rr 	 	= $dbConn->fetch_array_all($rr);
				
				if(count($rr) > 0){
					$goods_sncombine	= $rr[0]['goods_sncombine'];
					$goods_sncombine    = explode(',',$goods_sncombine);
	
					if(count($goods_sncombine) == 1){
						$pline			= explode('*',$goods_sncombine[0]);
						$goods_sn		= $pline[0];
						$goddscount     = $pline[1] * $ebay_amount;
					
						$ee			= "SELECT * FROM ebay_goods where goods_sn='$goods_sn' and ebay_user='$user'";
						$ee			= $dbConn->query($ee);
						$ee 	 	= $dbConn->fetch_array_all($ee);
						$ebay_packingmaterial		=  $ee[0]['ebay_packingmaterial'];			
						$goods_weight				=  $ee[0]['goods_weight'];					// 产品重量子力学
						$capacity					=  $ee[0]['capacity'];						//产品容量
						
						$ss					= "select * from ebay_packingmaterial where  model='$ebay_packingmaterial' and ebay_user ='$user' ";
						$ss					= $dbConn->query($ss);
						$ss					= $dbConn->fetch_array_all($ss);
						$pweight			= $ss[0]['weight'];
						
						if($goddscount <= $capacity){
							$totalweight			+= $pweight + ($goods_weight * $goddscount);
						}else{
							// 计算多个包材的重量   $ebay_amount 单个sku购买的数量 ebay_packingmaterial 包材的重量
							$totalweight			+= (1 + ($goddscount-$capacity)/$capacity*0.6)*$pweight + ($goods_weight * $goddscount);
							//$totalweight			+= (($goddscount/$capacity) + ((($goddscount%$capacity)/$capacity)*0.6))*$pweight + ($goods_weight * $goddscount);
						}
					}else{
						for($e=0;$e<count($goods_sncombine);$e++){
							$pline			= explode('*',$goods_sncombine[$e]);
							$goods_sn		= $pline[0];
							$goddscount     = $pline[1] * $ebay_amount;
						
							$ee			= "SELECT * FROM ebay_goods where goods_sn='$goods_sn' and ebay_user='$user'";
							$ee			= $dbConn->query($ee);
							$ee 	 	= $dbConn->fetch_array_all($ee);
							$ebay_packingmaterial		=  $ee[0]['ebay_packingmaterial'];			
							$goods_weight				=  $ee[0]['goods_weight'];					// 产品重量子力学
							$capacity					=  $ee[0]['capacity'];						//产品容量
							
							$ss					= "select * from ebay_packingmaterial where  model='$ebay_packingmaterial' and ebay_user ='$user' ";
							$ss					= $dbConn->query($ss);
							$ss					= $dbConn->fetch_array_all($ss);
							$pweight			= $ss[0]['weight'];
							
							$totalweight		+= ($goddscount/$capacity)*0.6*$pweight + ($goods_weight * $goddscount);
						}
					}
				}else{
					$ss							= "select * from ebay_goods where  goods_sn='$sku' and ebay_user ='$user' ";
					$ss							= $dbConn->query($ss);
					$ss							= $dbConn->fetch_array_all($ss);
					$ebay_packingmaterial		=  $ss[0]['ebay_packingmaterial'];			
					$goods_weight				=  $ss[0]['goods_weight'];					// 产品重量子力学
					$capacity					=  $ss[0]['capacity'];						//产品容量
	
					$ss					= "select * from ebay_packingmaterial where  model='$ebay_packingmaterial' and ebay_user ='$user' ";
					$ss					= $dbConn->query($ss);
					$ss					= $dbConn->fetch_array_all($ss);
					$pweight			= isset($ss[0]['weight']) ? $ss[0]['weight'] : 0;
	
					if($ebay_amount <= $capacity){
						$totalweight			+= $pweight + $goods_weight*$ebay_amount;
					}else{
						// 计算多个包材的重量   $ebay_amount 单个sku购买的数量 ebay_packingmaterial 包材的重量
						$totalweight			+= (1 + ($ebay_amount-$capacity)/$capacity*0.6)*$pweight + ($goods_weight * $ebay_amount);
						//$totalweight			+= (($ebay_amount/$capacity) + ((($ebay_amount%$capacity)/$capacity)*0.6))*$pweight + ($goods_weight * $ebay_amount);
					}
					//echo "sku = $sku-------goods_weight = $goods_weight-------ebay_amount = $ebay_amount--------ebay_packingmaterial = $ebay_packingmaterial----------capacity = $capacity-------pweight = $pweight---------totalweight = $totalweight"; echo "<br>";
				}
			}
		}
		return $totalweight;
	}
	
	//订单加载函数--交易形式
	function GetSellerTransactions($ebay_starttime,$ebay_endtime,$ebay_account,$type,$id){
		global $api_gst,$oa,$user;
		global $dbConn,$mctime,$defaultstoreid;
	
		$pcount	= 1;
		$errors	= 1;	
		do{
			echo	"抓取....\t";
			$responseXml=$api_gst->request($ebay_starttime,$ebay_endtime,$pcount);
			if(empty($responseXml)){
				echo "Return Empty...Sleep 10 seconds..";
				sleep(10);
				$hasmore=true;
				continue;
			}
			//网络出现代理Proxy error 脚本休眠20秒
			$poxy_error_p='#Proxy\s*Error#i';
			if(preg_match($poxy_error_p,$responseXml)){
				echo "Proxy Error...Sleep 20 seconds..";
				sleep(20);
				$hasmore=true;
				continue;
			}
			echo "\n";
			$data=XML_unserialize($responseXml);
			$responseXml=null;
			unset($responseXml);
		
			$getorder 	= $data['GetSellerTransactionsResponse'];
			$data=null;
			unset($data);
			
			$TotalNumberOfPages	 	= @$getorder['PaginationResult']['TotalNumberOfPages'];
			$TotalNumberOfEntries	= @$getorder['PaginationResult']['TotalNumberOfEntries'];
		
			$hasmore 	= @$getorder['HasMoreTransactions'];
			$strline	= $TotalNumberOfPages.'/'.$TotalNumberOfEntries;
		
			$Ack	 	= @$getorder['Ack'];
		
			echo "正在请求:$pcount/$TotalNumberOfPages \t记录数[ $TotalNumberOfEntries ]\t
				  同步状态: $Ack 还有更多: $hasmore \n";
		
			if($id == '' && $type == '1'){
				if($Ack == '' ){
					$ss	= "insert into errors_ack(ebay_account,starttime,endtime,status,notes) 
							values('$ebay_account','$ebay_starttime','$ebay_endtime','0','Ack False')";
					$dbConn->query($ss);
				}
				if($hasmore == '' || $Ack == '' ){
					$ss	= "insert into errors_ack(ebay_account,starttime,endtime,status,notes) 
							values('$ebay_account','$ebay_starttime','$ebay_endtime','0','Ack False')";
					$dbConn->query($ss);
				}
			}
		
			if($id>0){
				if($Ack == 'Success'){
					$gg	= "update errors_ack set status = 1 where id='$id' ";				
				}else{
					$gg	= "update errors_ack set status = 0 where id='$id' ";
				}
				$dbConn->query($gg);
			}
			/**/
			$log_name	 		= '同步订单';
			$log_operationtime  = $mctime;
			$log_notes	 	    = $ebay_account.":$pcount/$TotalNumberOfPages ,Ack=$Ack";
			
			addlogs($log_name,$log_operationtime,0,$log_notes,$user,
					$ebay_account,$ebay_starttime,$ebay_endtime,$type);
			
			/**/
			$Trans	= @$getorder['TransactionArray']['Transaction'];
			$ReturnedTransactionCountActual = @$getorder['ReturnedTransactionCountActual'];
			
			if($ReturnedTransactionCountActual == 1){
				$Trans	= array();
				$Trans[0] = $getorder['TransactionArray']['Transaction'];
			}	
		
			$getorder=null;
			unset($getorder);
		
			foreach((array)$Trans as $Transaction){
				//每笔记录编号
				$tran_recordnumber	= $Transaction['ShippingDetails']['SellingManagerSalesRecordNumber'];
				//交易状态
				$LastTimeModified 	= strtotime($Transaction['Status']['LastTimeModified']);			
				$eBayPaymentStatus 	= $Transaction['Status']['eBayPaymentStatus'];
				$CompleteStatus 	= $Transaction['Status']['CompleteStatus'];		
				$CheckStatus 		= $Transaction['Status']['CompleteStatus'];
				//其他交易信息比如payapl整合到ebay
				$ptid 				= @$Transaction['ExternalTransaction']['ExternalTransactionID'];
				
				$FeeOrCreditAmount 	= @$Transaction['ExternalTransaction']['FeeOrCreditAmount'];
				$FinalValueFee		= $Transaction['FinalValueFee'];
				
				$tid				= $Transaction['TransactionID'];//ebay 交易号
				$AmountPaid  		= @$Transaction['AmountPaid'];
				$Buyer 				= str_rep(@$Transaction['Buyer']);
				$Email 				= str_rep(@$Buyer['Email']); //email
				$UserID 			= str_rep(@$Buyer['UserID']);//userid
				$BuyerInfo 			= $Buyer['BuyerInfo']['ShippingAddress'];
				$Name 				= str_rep($BuyerInfo['Name']);
				$Name				= mysql_real_escape_string($Name);
				$Street1 			= str_rep($BuyerInfo['Street1']);
				$Street2 			= str_rep(@$BuyerInfo['Street2']);
				$CityName 			= str_rep(@$BuyerInfo['CityName']);
				$StateOrProvince 	= str_rep(@$BuyerInfo['StateOrProvince']);
				$Country 			= str_rep(@$BuyerInfo['Country']);
				$CountryName 		= str_rep(@$BuyerInfo['CountryName']);
				$PostalCode 		= str_rep(@$BuyerInfo['PostalCode']);
				$Phone 				= @$BuyerInfo['Phone'];
				//该交易的物品信息
				$Item 				= $Transaction['Item'];
				$CategoryID 		= $Item['PrimaryCategory']['CategoryID']; //ebay刊登物品的分类ID,备用字段
				$Currency 			= $Item['Currency'];  //货币类型
				$ItemID 			= $Item['ItemID']; //ebay物品id
				$ListingType 		= $Item['ListingType'];
				$Title 				= str_rep($Item['Title']);//ebay物品标题
				$sku 				= str_rep($Item['SKU']);
				$site				= $Item['Site'];
				$CurrentPrice 		= $Item['SellingStatus']['CurrentPrice'];//产品当前价格
				
				$QuantityPurchased 	= $Transaction['QuantityPurchased']; //购买数量
				$PaidTime 			= strtotime($Transaction['PaidTime']); //付款时间
				$CreatedDate 		= strtotime($Transaction['CreatedDate']);               //交易创建时间...********多个产品订单每个产品的创建时间不同判依据
				$ShippedTime    	= strtotime($Transaction['ShippedTime']);				
				$shipingservice		= $Transaction['ShippingServiceSelected']['ShippingService'];
				$shipingfee			= $Transaction['ShippingServiceSelected']['ShippingServiceCost'];
				$containing_order	= @$Transaction['ContainingOrder'];
				$combined_recordnumber	= @$containing_order['ShippingDetails']['SellingManagerSalesRecordNumber']; //合并后的recordnumber
					
				$orderid			= 0;
				if($combined_recordnumber != ''){
					$orderid 	= @$containing_order['OrderID'];
				}else{
					$orderid	= $ItemID.'-'.$tid;
				}
				$BuyerCheckoutMessage	= str_rep($Transaction['BuyerCheckoutMessage']);//顾客购买留言
				$BuyerCheckoutMessage	= str_replace('<![CDATA[','',$BuyerCheckoutMessage);
				$BuyerCheckoutMessage	= str_replace(']]>','',$BuyerCheckoutMessage);
				//店铺收款paypal account
				$PayPalEmailAddress	= $Transaction['PayPalEmailAddress'];    
				
				$addrecordnumber    = $tran_recordnumber;					
				if($combined_recordnumber != ''){	$addrecordnumber	= $combined_recordnumber;	}
								
				if($CompleteStatus  == "Complete" && $eBayPaymentStatus == "NoPaymentFailure" && $PaidTime > 0){
					$orderstatus	= 1;
				}
				if($ShippedTime >0) $orderstatus	= 2;//已经发货
				
				################################
				$RefundAmount	= 0; //表示未垦退款
				if($orderstatus == 1 && $ShippedTime <=0 && $PaidTime >0 ){
					echo "销售编号[$addrecordnumber]有效";
					//检查汇总表该 recordnumber是否已经存在 
					//主要是避免multiple line item 这种情况 造成重复添加 汇总数据
					$check_ordersn=CheckOrderSN($addrecordnumber,$ebay_account);
					
					$new_ebay_id=true;
					if($check_ordersn == "0"){//该交易还无汇总数据	添加订单汇总
						/* 生成一个本地系统订单号 */
						$our_sys_ordersn=date('Y-m-d-His').mt_rand(100,999).$addrecordnumber;
				
						$order_no		= '';//已废弃
						
						$obj_order		= new eBayOrder();
						$obj_order_data=array('ebay_paystatus'=>$CompleteStatus,
											  'ebay_ordersn'=>$our_sys_ordersn,
											  'ebay_tid'=>$tid,
											  'ebay_ptid'=>$ptid,
											  'ebay_orderid'=>$orderid,
											  'ebay_createdtime'=>$CreatedDate,
											  'ebay_paidtime'=>$PaidTime,
											  'ebay_userid'=>$UserID,
											  'ebay_username'=>$Name,
											  'ebay_usermail'=>$Email,
											  'ebay_street'=>$Street1,
											  'ebay_street1'=>$Street2,
											  'ebay_city'=>$CityName,
											  'ebay_state'=>$StateOrProvince,
											  'ebay_couny'=>$Country,
											  'ebay_countryname'=>$CountryName,
											  'ebay_postcode'=>$PostalCode,
											  'ebay_phone'=>$Phone,
											  'ebay_currency'=>$Currency,
											  'ebay_total'=>$AmountPaid,
											  'ebay_status'=>$orderstatus,
											  'ebay_user'=>$user,
											  'ebay_shipfee'=>$shipingfee,
											  'ebay_account'=>$ebay_account,
											  'recordnumber'=>$addrecordnumber,
											  'ebay_addtime'=>$mctime,
											  'ebay_note'=>$BuyerCheckoutMessage,
											  'ebay_site'=>$site,
											  'eBayPaymentStatus'=>$eBayPaymentStatus,
											  'PayPalEmailAddress'=>$PayPalEmailAddress,
											  'ShippedTime'=>$ShippedTime,
											  'RefundAmount'=>$RefundAmount,
											  'ebay_warehouse'=>$defaultstoreid,
											  'order_no'=>$order_no
											  );
						$obj_order->init($obj_order_data);
						$obj_order_data=null;
						unset($obj_order_data);
						
						$new_ebay_id=$oa->addOrder($obj_order);
						$obj_order=null;
						unset($obj_order);
						
						if($new_ebay_id!==false){
							echo "\t订单[$our_sys_ordersn] 汇总数据入库成功=>\n\tUserID:$UserID"." AMT:$AmountPaid recordNO:$addrecordnumber ";
							echo "付款状态:$CompleteStatus 交易ID:$ptid\n";
							$check_ordersn=$our_sys_ordersn;
							
							//检验ebay 订单号 是否在订单号汇总表中
							
							if(check_ebay_orderid_exists_in_statistic_table($orderid,$ebay_account)===false){
								save_ebay_orderid_table($new_ebay_id,$ptid,$orderid,$ebay_account,$CreatedDate);
							}
							
						}else{
							echo "\t订单[$our_sys_ordersn] 入库失败\n";
						}
					}
					if($new_ebay_id!==false){//添加订单明细
						$sql = "select 	ebay_id from ebay_orderdetail 
								where 	ebay_ordersn='$check_ordersn' 
								and 	recordnumber='$tran_recordnumber'";
						$sql = $dbConn->query($sql);
						$sql = $dbConn->fetch_array_all($sql);
						
						if(count($sql) == 0){							
							/* 多属性订单 */
							$Variation	= @$Transaction['Variation']['VariationSpecifics']['NameValueList'];
							$attribute	= '';
							if(	!empty($Variation)	){
								if( (!isset($Variation['Name'])) || (!isset($Variation['Value'])) ){
									foreach($Variation as $variate){
										$aname	= $variate['Name'];
										$avalue	= $variate['Value'];
										$attribute	.= $aname.":".$avalue." ";
									}
								}else{
									$attribute	= $Variation['Name'].":".$Variation['Value'];
								}
								unset($Variation);
							}
							$obj_order_detail=new eBayOrderDetail();
							$obj_order_detail_data=array('ebay_ordersn'=>$check_ordersn,
														 'ebay_itemid'=>$ItemID,
														 'ebay_itemtitle'=>$Title,
														 'ebay_itemprice'=>$CurrentPrice,
														 'ebay_amount'=>$QuantityPurchased,
														 'ebay_createdtime'=>$CreatedDate,
														 'ebay_shiptype'=>$shipingservice,
														 'ebay_user'=>$user,
														 'sku'=>$sku,
														 'shipingfee'=>$shipingfee,
														 'ebay_account'=>$ebay_account,
														 'addtime'=>$mctime,
														 'ebay_itemurl'=>'',
														 'ebay_site'=>$site,
														 'recordnumber'=>$tran_recordnumber,
														 'storeid'=>'',
														 'ListingType'=>$ListingType,
														 'ebay_tid'=>$tid,
														 'FeeOrCreditAmount'=>$FeeOrCreditAmount,
														 'FinalValueFee'=>$FinalValueFee,
														 'attribute'=>$attribute,
														 'notes'=>$BuyerCheckoutMessage,
														 'goods_location'=>@get_good_location($sku,$user)
														 );
							$obj_order_detail->init($obj_order_detail_data);
							$obj_order_detail_data=null;
							unset($obj_order_detail_data);
							
							if(	false!==($oa->addOrderDetail($obj_order_detail)) ){
								echo "\t订单[$check_ordersn] 编号[$tran_recordnumber]明细入库OK!\n";
							}else{
								echo "\t订单[$check_ordersn] 编号[$tran_recordnumber]明细入库Error!\n";
							}
							$obj_order_detail=null;
							unset($obj_order_detail);
						}
						
						$sql = "select ebay_id from ebay_orderdetail 
								where ebay_ordersn='$check_ordersn' 
								and recordnumber='$tran_recordnumber'";
					
						$sql  = $dbConn->query($sql);
						$sql  = $dbConn->fetch_array_all($sql);
						if(count($sql) >=2 && strlen($check_ordersn) >=5){
							$id		= $sql[0]['ebay_id'];
							$ss		= "delete from ebay_orderdetail where ebay_id='$id'";
							$dbConn->query($ss);
						}
						if($ShippedTime >0){
							$ss	= "update ebay_order set ShippedTime='$ShippedTime',
									ebay_status='2',ebay_markettime='$ShippedTime' 
									where ebay_ordersn='$check_ordersn' and ebay_status='1'";
							$dbConn->query($ss);
						}

					}
				}else{
					echo "销售编号[$addrecordnumber]无效 不入库...\n";
				}
			}
		
			if($id == '' && $type == '1'){
				if($Ack == '' || $Ack == 'Failure'){
					$ss	= "insert into errors_ack(ebay_account,starttime,endtime,status,notes,currentpage) 
							values('$ebay_account','$ebay_starttime','$ebay_endtime','0','Ack False','$pcount')";
					$dbConn->query($ss);
				}
			}

			if($pcount>= $TotalNumberOfPages ){			
				echo $hasmore."程序退出了\n";
				break;
			}
			$pcount++;
			$hasmore =(strtolower($hasmore)=='true')?true:false;
		}while($hasmore);
	}
	//订单加载函数--订单形式
	function GetSellerOrders($ebay_starttime,$ebay_endtime,$ebay_account,$type,$id){
		global $api_go,$oa,$user;
		global $dbConn,$mctime,$defaultstoreid;
		$pcount	= 1;
		$errors	= 1;
		do{
			echo	"抓取....\t";
			$responseXml=$api_go->request($ebay_starttime,$ebay_endtime,$pcount);
			if(empty($responseXml)){
				echo "Return Empty...Sleep 10 seconds..";
				sleep(10);
				$hasmore=true;
				continue;
			}
			//网络出现代理Proxy error 脚本休眠20秒
			$poxy_error_p='#Proxy\s*Error#i';
			if(preg_match($poxy_error_p,$responseXml)){
				echo "Proxy Error...Sleep 20 seconds..";
				sleep(20);
				$hasmore=true;
				continue;
			}
			echo "\n";
			$responseDoc = new DomDocument();	
			$responseDoc->loadXML($responseXml);
			
			//保存原始raw数据
			$raw_data_path	=EBAY_RAW_DATA_PATH.$ebay_account.'/date_range_order/'.date('Y-m').'/'.date('d').'/';
			$raw_data_filename	=str_replace(':','-',$ebay_starttime).'--'.str_replace(':','-',$ebay_endtime).'--p'.$pcount.'.xml';
			$raw_data_filename	=$raw_data_path.$raw_data_filename;
			$save_res	=save_ebay_raw_data($raw_data_filename,$responseXml);
			if($save_res!==false){
				echo "save raw data ok...\n";
			}else{
				echo "save raw data fail...\n";
			}
			
			$responseXml=null;unset($responseXml);
			
			$TotalNumberOfPages	 	= $responseDoc->getElementsByTagName('TotalNumberOfPages')->item(0)->nodeValue;
			$TotalNumberOfEntries	= $responseDoc->getElementsByTagName('TotalNumberOfEntries')->item(0)->nodeValue;
			$hasmore 				= $responseDoc->getElementsByTagName('HasMoreOrders')->item(0)->nodeValue;
			$Ack	 				= $responseDoc->getElementsByTagName('Ack')->item(0)->nodeValue;
		
			echo "正在请求:$pcount/$TotalNumberOfPages\t记录数[ $TotalNumberOfEntries ]\t同步状态: $Ack 还有更多:$hasmore\n";
		
			if($id == '' && $type == '1'){
				if($Ack == 'Failure'){
					$ss	= "insert into errors_ack(ebay_account,starttime,endtime,status,notes) 
							values('$ebay_account','$ebay_starttime','$ebay_endtime','0','Ack False')";
					$dbConn->query($ss);
				}
			}
		
			if($id>0){
				if($Ack == 'Success'||$Ack == 'Warning'){
					$gg	= "update errors_ack set status = 1 where id='$id' ";				
				}else{
					$gg	= "update errors_ack set status = 0 where id='$id' ";
				}
				$dbConn->query($gg);
			}
			/**/
			$log_name	 		= '同步订单bygo';
			$log_operationtime  = $mctime;
			$log_notes	 	    = $ebay_account.":$pcount/$TotalNumberOfPages ,Ack=$Ack";
			
			addlogs($log_name,$log_operationtime,0,$log_notes,$user,
					$ebay_account,$ebay_starttime,$ebay_endtime,$type);
			
			/**/
			$SellerOrderArray	= $responseDoc->getElementsByTagName('Order');
			
			//调用订单入库函数
			__handle_ebay_orderxml($SellerOrderArray,$ebay_account);
			$SellerOrderArray=null;unset($SellerOrderArray);
			
			if($id == '' && $type == '1'){
				if($Ack == '' || $Ack == 'Failure'){
					$ss	= "insert into errors_ack(ebay_account,starttime,endtime,status,notes,currentpage) 
							values('$ebay_account','$ebay_starttime','$ebay_endtime','0','Ack False','$pcount')";
					$dbConn->query($ss);
				}
			}

			if($pcount>= $TotalNumberOfPages ){			
				echo $hasmore."程序退出了\n";
				break;
			}
			$pcount++;
			$hasmore =(strtolower($hasmore)=='true')?true:false;
		}while($hasmore);
	}
	
	//标记发货函数
	function just_mark_order_shipped($ebay_orderid,$ebay_ordersn){
		global $api_cs,$user;
		global $dbConn,$mctime,$defaultstoreid;
		
		//获取订单明细
		$order_detail_sql='SELECT 	ebay_itemid,sku,ebay_tid	FROM ebay_orderdetail 
						   WHERE	ebay_ordersn="'.$ebay_ordersn.'" ';
		
		$order_detail	= $dbConn->query($order_detail_sql);
		$order_detail	= $dbConn->fetch_array_all($order_detail);
		
		foreach($order_detail as $od){
			$tran_data=array();
			$tran_data['itemid']	=$od['ebay_itemid'];
			$tran_data['tid']		=$od['ebay_tid'];
			$tran_data['orderid']	=$ebay_orderid;
			
			echo "itemid:".$od['ebay_itemid']."\t tid:".$od['ebay_tid']."\t sku:".$od['sku']."\n";			
			
			$mark_res=$api_cs->just_mark_order_shipped($tran_data);
			
			$responseDoc = new DomDocument();	
			$responseDoc->loadXML($mark_res);
			
			$Ack	 	= $responseDoc->getElementsByTagName('Ack')->item(0)->nodeValue;
			
			if($Ack == "Success"){						
				echo "  已标记发出\n";
				return true;
				$dbConn->query($sb);
			}else{
				return false;
				echo "  标记发出失败 ACK=$Ack \n";
			}
		}
	}
	//更新发货信息(trackno)到ebay
	function update_order_shippingdetail_to_ebay($ebay_orderid,$ebay_ordersn,$ebay_tracknumber,$ebay_carrier){
		global $api_cs,$user;
		global $dbConn,$mctime,$defaultstoreid;
		//获取订单明细
		$order_detail_sql='SELECT 	ebay_itemid,sku,ebay_amount,ebay_tid	FROM ebay_orderdetail 
						   WHERE	ebay_ordersn="'.$ebay_ordersn.'" ';
		
		$order_detail	= $dbConn->query($order_detail_sql);
		$order_detail	= $dbConn->fetch_array_all($order_detail);
		
		foreach($order_detail as $od){
			$tran_data=array();
			$tran_data['itemid']				=$od['ebay_itemid'];
			$tran_data['tid']					=$od['ebay_tid'];
			$tran_data['orderid']				=$ebay_orderid;
			$tran_data['ebay_carrier']			=$ebay_carrier;
			$tran_data['ebay_tracknumber']		=$ebay_tracknumber;
			
			echo "itemid:".$od['ebay_itemid']."\t tid:".$od['ebay_tid']."\t sku:".$od['sku']."\n";
			echo "carrier:".$ebay_carrier."\t trackno:".$ebay_tracknumber."\n";
			
			$mark_res=$api_cs->update_order_shippingdetail_to_ebay($tran_data);
			
			$responseDoc = new DomDocument();	
			$responseDoc->loadXML($mark_res);
			
			$Ack	 	= $responseDoc->getElementsByTagName('Ack')->item(0)->nodeValue;
			
			if($Ack == "Success"){						
				echo "  更新shippingdetail成功\n";
				$sb		= " update ebay_order set ebay_markettime='$mctime',ShippedTime='$mctime' 
							where ebay_ordersn='$ebay_ordersn'";
				$dbConn->query($sb);
				return true;
			}else{
				echo "  更新shippingdetail失败 ACK=$Ack \n";
				return false;
			}
		}
	}
	//更新发货信息订单编号到ebay
	function update_ebayid_shippingdetail_to_ebay($ebay_orderid,$ebay_ordersn,$ebay_tracknumber,$ebay_carrier){
		global $api_cs,$user;
		global $dbConn,$mctime,$defaultstoreid;
		//获取订单明细
		$order_detail_sql='SELECT 	ebay_itemid,sku,ebay_amount,ebay_tid	FROM ebay_orderdetail 
						   WHERE	ebay_ordersn="'.$ebay_ordersn.'" ';
		
		$order_detail	= $dbConn->query($order_detail_sql);
		$order_detail	= $dbConn->fetch_array_all($order_detail);
		
		foreach($order_detail as $od){
			$tran_data=array();
			$tran_data['itemid']				=$od['ebay_itemid'];
			$tran_data['tid']					=$od['ebay_tid'];
			$tran_data['orderid']				=$ebay_orderid;
			$tran_data['ebay_carrier']			=$ebay_carrier;
			$tran_data['ebay_tracknumber']		=$ebay_tracknumber;
			
			echo "itemid:".$od['ebay_itemid']."\t tid:".$od['ebay_tid']."\t sku:".$od['sku']."\n";
			echo "carrier:".$ebay_carrier."\t trackno:".$ebay_tracknumber."\n";
			
			$mark_res=$api_cs->update_order_shippingdetail_to_ebay($tran_data);
			
			$responseDoc = new DomDocument();	
			$responseDoc->loadXML($mark_res);
			
			$Ack	 	= $responseDoc->getElementsByTagName('Ack')->item(0)->nodeValue;
			
			if($Ack == "Success"){						
				echo "  更新shippingdetail成功\n";
				return true;
			}else{
				echo "  更新shippingdetail失败 ACK=$Ack \n";
				return false;
			}
		}
	}
	function save_ebay_raw_data($fname,$raw_data){
		$tmp_dir=dirname($fname);
		if(!is_dir($tmp_dir)){
			mkdirs($tmp_dir);
		}
		
		$f=@fopen($fname,'w');
		$fsize=mb_strlen($raw_data);
		$res=@fwrite($f,$raw_data,$fsize);
		@fclose($f);
		return $res;
	}
	function sql_str2array($content){
		$result = array();
		$array = explode('<br />', nl2br($content));
		foreach($array AS $_v){
			if(preg_match("/(insert|update|replace|delete|select)/i", $_v)){
				array_push($result, $_v);
			}
		}
		return $result;
	}
	function write_lost_sql($file, $data){
		$tmp_dir = dirname($file);
		if(!is_dir($tmp_dir)){
			mkdirs($tmp_dir);
		}
		if (!$handle=fopen($file, 'a')) {
			 return false;
		}
		if(flock($handle, LOCK_EX)) { 
			if (fwrite($handle, $data) === FALSE) {
				return false;
			}
			flock($handle, LOCK_UN);
		}
		fclose($handle);
		return true;
	}
	function read_lost_sql($file){
		if(!is_file($file)){
			return false;
		}
		return file_get_contents($file);;
	}
	
	function read_and_empty_lost_sql($file){
		if(!is_file($file)){
			return false;
		}
		$contents =  file_get_contents($file);
		if (!$handle=fopen($file, 'w')) {
			 return false;
		}
		return $contents;
	}
	function calcglobalmail_backup2($totalweight,$countryname){
	
		global $dbConn;
		
		include WEB_PATH.'/cache/shipfeee/globalmail.php';
		
		$cnum = '';
	
		foreach ($GLOBALMAIL_CONTRY_LIST AS $c=>$country){
			if ($countryname==$country){
				$cnum = $c;
				break;
			}
		}
		foreach ($GLOBALMAIL_WEIGHT_LIST AS $w=>$weight){
			list($start_w, $end_w) = explode('-', $weight);
			if ($start_w<$totalweight&&$totalweight<=$end_w){
				$wnum = $w;
				break;
			}
		}
	
		list($price, $addprice) = explode('_', $GLOBALMAIL_PRICE_LIST[$wnum][$cnum]);
	
		return $price*$totalweight+$addprice;
		
	}
	function calcglobalmail($totalweight,$countryname){
		global $dbConn;
		//add by heminghua @ 20130325
		if($totalweight<=0)
		{
		 return false;
		}else
		{
		 $ss="select * from ebay_globalmail where country = '{$countryname}'";
		 $ss=$dbConn->query($ss);
		 $result=$dbConn->fetch_one($ss);
		 $dbConn->free_result($result);
		 if(empty($result))
		 {
		   return 0;
		 }
		 else
		 {
		   /*运费计算*/
		   $weight_freight=$result['weight_freight'];
		   $weight_freight_arr=explode(',',$weight_freight);
		   foreach($weight_freight_arr as $key1 => $value1)
		   {
			 $value1_arr=explode(':',$value1);
			 $weight_range=explode('-',$value1_arr[0]);
			 if($totalweight>$weight_range[0] && $totalweight<=$weight_range[1])
			 {
			   $shipfee=$value1_arr[1];
			   break;
			 }
			 
		   }
		   $shipfee *= $totalweight;
		   /*油费计算*/
		   $fuelcosts=$result['fuelcosts'];
		   $fuelcosts_arr=explode(',',$fuelcosts);
		   foreach($fuelcosts_arr as $key2 => $value2)
		   {
			 $value2_arr=explode(':',$value2);
			 $weight_range=explode('-',$value2_arr[0]);
			 if($totalweight>$weight_range[0] && $totalweight<=$weight_range[1])
			 {
			   $fuelfee = $value2_arr[1];
			   break;
			 }
		   }
		   
		   $shipfee += $fuelfee;
		 }
	
		}
		return $shipfee;
	}
	function calcfedex($totalweight,$countryname,$orderid){
	
		global $dbConn;
		
		include WEB_PATH.'cache/shipfeee/fedex_1.php';
		
		$cnum = '';
		$sql = "SELECT ebay_postcode FROM ebay_order WHERE ebay_id={$orderid}";
		$sql = $dbConn->query($sql);
		$code = $dbConn->fetch_array_all($sql);
		$postcode = $code[0]['ebay_postcode'];
		foreach ($FEDEX_CONTRY_LIST_1 AS $c=>$country){
			$countrylist = explode(',', $country);
			if (in_array($countryname, $countrylist)){
				if ($countryname=='United States'){
					$postcode_lists = explode('#', $countrylist[1]);
					foreach ($postcode_lists AS $postcode_list){
						list($post_start, $post_end) = explode('-', $postcode_list);
						if ($post_start<$postcode&&$postcode<$post_end){
							$cnum = $c;
							break;
						}
					}
					if ($cnum===''){
						$cnum = $c+1;
					}
				}
				if ($cnum===''){
					$cnum = $c;
				}
				break;
			}
		}
		if ($cnum===''){
			return calcfedexyx($totalweight, $countryname, $postcode);
		}
		foreach ($FEDEX_WEIGHT_LIST_1 AS $w=>$weight){
			list($start_w, $end_w) = explode('-', $weight);
			if ($start_w<$totalweight&&$totalweight<$end_w){
				$wnum = $w;
				break;
			}
		}
		echo "国家区间({$cnum})----重量区间({$wnum})------价格({$FEDEX_PRICE_LIST_1[$wnum][$cnum]})----总价".$FEDEX_PRICE_LIST_1[$wnum][$cnum]*(1+$FEDEX_MYC_FEE_1);
		$shipfee = $totalweight>20.5 ? $totalweight*$FEDEX_PRICE_LIST_1[$wnum][$cnum]*(1+$FEDEX_MYC_FEE_1) : $FEDEX_PRICE_LIST_1[$wnum][$cnum]*(1+$FEDEX_MYC_FEE_1);
		return round($shipfee, 2);
		
	}
	
	function calcfedexyx($totalweight,$countryname,$postcode=0){
		
		include WEB_PATH.'cache/shipfeee/fedex_2.php';
		
		$cnum = '';
		foreach ($FEDEX_CONTRY_LIST_2 AS $c=>$country){
			$countrylist = explode(',', $country);
			if (in_array($countryname, $countrylist)){
				if ($countryname=='United States'){
					$postcode_lists = explode('#', $countrylist[1]);
					foreach ($postcode_lists AS $postcode_list){
						list($post_start, $post_end) = explode('-', $postcode_list);
						if ($post_start<$postcode&&$postcode<$post_end){
							$cnum = $c;
							break;
						}
					}
					if ($cnum===''){
						$cnum = $c+1;
					}
				}
				if ($cnum===''){
					$cnum = $c+1;
				}
				break;
			}
		}
		if ($cnum===''){
			return 0;
		}
		foreach ($FEDEX_WEIGHT_LIST_2 AS $w=>$weight){
			list($start_w, $end_w) = explode('-', $weight);
			if ($start_w<$totalweight&&$totalweight<$end_w){
				$wnum = $w;
				break;
			}
		}
		
		return round($FEDEX_PRICE_LIST_2[$wnum][$cnum]*(1+$FEDEX_MYC_FEE_2), 2);
		//return 0;
	}
	
	function get_account_suffix($ebay_account){
		//获取账号后缀名称
		global $dbConn;
		$sql = "SELECT suffix FROM om_account WHERE account='{$ebay_account}'";
		$sql = $dbConn->query($sql);
		$result = $dbConn->fetch_array($sql);
		return isset($result['suffix']) ? $result['suffix'] : '';
	}
	
	function get_realskuinfo($sku){
		global $dbConn;
		
		$sql = "SELECT goods_sncombine FROM ebay_productscombine WHERE goods_sn ='{$sku}'";
		$sql = $dbConn->query($sql);
		$combinelists = $dbConn->fetch_one($sql);
		
		if (empty($combinelists)){ //modified by Herman.Xi @ 2013-05-22
			$sku = get_conversion_sku($sku);
			return array($sku=>1);
		}
		$results = array();
		if (strpos($combinelists['goods_sncombine'], ',')!==false){
			$skulists = explode(',', $combinelists['goods_sncombine']);
			foreach ($skulists AS $skulist){
				list($_sku, $snum) = strpos($skulist, '*')!==false ? explode('*', $skulist) : array($skulist, 1);
				$_sku = get_conversion_sku($_sku);
				$results[trim($_sku)] = $snum;
			}
		}else if (strpos($combinelists['goods_sncombine'], '*')!==false){
			list($_sku, $snum) = explode('*', $combinelists['goods_sncombine']);
			$_sku = get_conversion_sku($_sku);
			$results[trim($_sku)] = $snum;
		}else{
			$sku = get_conversion_sku($sku);
			$results[trim($sku)] = 1;
		}
		return $results;
	}
	function get_conversion_sku($sku){
		/*add by Herman.Xi @ 2013-06-04
		新旧料号转换问题解决*/
		global $dbConn;
		$sql = "SELECT new_sku FROM purchase_sku_conversion WHERE old_sku ='{$sku}'";
		$sql = $dbConn->query($sql);
		$conversion_sku = $dbConn->fetch_one($sql);
		if(empty($conversion_sku)){
			return	trim($sku);
		}
		return trim($conversion_sku['new_sku']);
	}
	
function generateOrdersn(){
	/**
	*自动获取产品 ordersn 的方法
	*/
	global $dbConn;
	$val = date("Y-m-d-His"). mt_rand(100, 999);
	while(true){
		$sql = "SELECT ebay_id AS num FROM ebay_order WHERE ebay_ordersn='$val'";
		$sql = $dbConn->query($sql);
		$si = $dbConn->num_rows($sql);
		if($si==0){
			break;	
		}
		$val = date("Y-m-d-His"). mt_rand(100, 999);
	}
	return $val;
}

function CheckID($recordnumber,$account){
	global $dbConn;
	$sql		= "select ebay_ordersn from ebay_order where recordnumber='$recordnumber' and ebay_account='$account'";
	$sql  = $dbConn->query($sql);
	$sql  = $dbConn->fetch_array_all($sql);
	if(count($sql) == 0){
		$status			= false;
	}else{
		$status 		= $sql[0]['ebay_ordersn'];
	}
	return $status;
}
function CheckdetailID($recordnumber,$account){
	global $dbConn;
	$sql		= "select ebay_ordersn from ebay_orderdetail where recordnumber='$recordnumber' and ebay_account='$account'";
	$sql  = $dbConn->query($sql);
	$sql  = $dbConn->fetch_array_all($sql);
	if(count($sql) == 0){
		$status			= false;
	}else{
		$status 		= $sql[0]['ebay_ordersn'];
	}
	return $status;
}


function check_blacklist($order){
	global $dbConn;

	$ebay_userid = $order['ebay_userid'];
	$ebay_username = $order['ebay_username'];
	$ebay_usermail = $order['ebay_usermail'];
	$ebay_street = $order['ebay_street'];
	$ebay_phone = $order['ebay_phone'];
	$ebay_account = $order['ebay_account'];
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
	$blackwhere = count($blackcondition)	> 0 ? " where {$bconditon} and ebay_accounts like '%[{$ebay_account}]%' " : 'where 0';
	$sql = $sql.$blackwhere;

	$sql	= $dbConn->query($sql);
	$black_list	= $dbConn->fetch_one($sql);
	if($black_list['totalnum'] > 0){
		$ss = "update ebay_order set ebay_status=684 where ebay_id={$order['ebay_id']}";

		if($dbConn->query($ss)){
			insert_mark_shipping($order['ebay_id']);
			echo "订单id{$order['ebay_id']}进入黑名单文件夹";
		}else{
			echo "订单id{$order['ebay_id']}移动进黑名单文件夹失败";
		}
	}
}
?>
