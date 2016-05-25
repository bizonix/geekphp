<?php
	/* 计算香港小包平邮的实际运费 */
	function calchkpost($totalweight,$countryname){
		
		global $dbConn;
		$ss		= "select * from ebay_hkpostcalcfee where countrys like '%$countryname%'";
		$ss		= $dbConn->execute($ss);
		$ss		= $dbConn->getResultArray($ss);
		
		$rate			= $ss[0]['discount']?$ss[0]['discount']:1;
		$kg				= $ss[0]['firstweight'];
		$handlefee		= $ss[0]['handlefee'];
		
		
		$shipfee		= $kg * $totalweight + $handlefee;
		if($rate > 0) $shipfee		= $shipfee * $rate;
		return $shipfee;
						
	
	
	}
	
	/* 计算香港小包挂号的费用 */
	function calchkghpost($totalweight,$countryname){
	
		global $dbConn;
		$ss		= "select * from ebay_hkpostghcalcfee where countrys like '%$countryname%'";
		$ss		= $dbConn->execute($ss);
		$ss		= $dbConn->getResultArray($ss);
		
		$rate			= $ss[0]['discount']?$ss[0]['discount']:1;
		$kg				= $ss[0]['firstweight'];
		$handlefee		= $ss[0]['handlefee'];
		$shipfee		= $kg * $totalweight + $handlefee;
		if($rate > 0) $shipfee		= $shipfee * $rate;
		return $shipfee;
	
	}	
	
	function calcems($totalweight,$ebay_countryname){
		//EMS运费计算方式
		global $dbConn;
		$dd		= "SELECT * FROM  `ebay_emscalcfee` where countrys like '%$ebay_countryname%' ";
		$dd		= $dbConn->execute($dd);
		$dd		= $dbConn->getResultArray($dd);
		$firstweight	= $dd[0]['firstweight'];
		$nextweight		= $dd[0]['nextweight'];
		$handlefee		= $dd[0]['handlefee'];
		$discount		= $dd[0]['discount'];
		$firstweight0	= $dd[0]['firstweight0'];
		$files			= $dd[0]['files'];
									
		if($files == '1' && $totalweight <= 0.5){
										
		$firstweight	= $firstweight0;
		}
									
		if($totalweight <= 0.5){
							
		$shipfee	= $firstweight;
						
		}else{
								
		$shipfee	= ceil((($totalweight*1000)/500))*$nextweight + $firstweight + $handlefee;
		}
		
		$shipfee	= $shipfee *$discount;
		return $shipfee;							
	
	}
	
	
	function calceub($totalweight,$countryname, $isdiscount=true){
		//EUB 运费计算方式
		global $dbConn,$user;
		/**
		 * 单件邮件不超过65克（含65g）：7.8元
			单件邮件66-250克：每克0.12元
			单件邮件251-300克：30元
			单件邮件301-2000克：每克0.1元
		 */
		$ss		= "select * from ebay_carrier where ebay_user ='$user' and name ='EUB' ";
		$ss		= $dbConn->execute($ss);
		$ss		= $dbConn->getResultArray($ss);
		
		$handlefee = floatval($ss[0]['handlefee']);
		$discount = empty($ss[0]['discount']) ? 1 : $ss[0]['discount'];
		if($totalweight <= 0.065){
			$shipfee	= $ss[0]['kg'];
		}else if (0.065<$totalweight&&$totalweight<=0.25){
			$shipfee	= $totalweight * $ss[0]['handlefee'];
		}else if (0.25<$totalweight&&$totalweight<=0.3){
			$shipfee = $ss[0]['kg2'];
		}else if (0.3<$totalweight&&$totalweight<=2){
			$shipfee	= $totalweight * $ss[0]['handlefee4'];
		}
		if (!$isdiscount){
			return $shipfee;
		}
		return round($shipfee * $discount, 2);
	}
	
	
	/*function calcchinapostgh($totalweight,$ebay_countryname){
	
			global $dbConn;
			
			$dd		= "SELECT * FROM  `ebay_cpghcalcfee` where countrys like '%$ebay_countryname%' ";
			$dd		= $dbConn->execute($dd);
			$dd		= $dbConn->getResultArray($dd);
			if(count($dd)>=1){
				$firstweight	= $dd[0]['firstweight'];
				$nextweight		= $dd[0]['nextweight'];
				$handlefee		= $dd[0]['handlefee'];
				$discount		= $dd[0]['discount']?$dd[0]['discount']:1;
				$xx0			= $dd[0]['xx0'];
				$xx1			= $dd[0]['xx1'];
			    if($totalweight <= ($xx0/1000)){
				$shipfee	= $firstweight + $handlefee;
				}else{
				$shipfee	= ceil(((($totalweight*1000) -$xx0)/$xx1))*$nextweight + $firstweight + $handlefee;
				}
			}
			
			
			return $shipfee;
			
	}*/
	
	//中国邮政挂号
	function calcchinapostgh($totalweight,$countryname, $discount=true){
	
		global $dbConn;
		if(in_array($countryname,array("Russian Federation","Russia"))){
		   $shipfee = 96.3*$totalweight+8;
		   return $shipfee;
		}
		$dd		= "SELECT * FROM  `ebay_cpghcalcfee` where countrys like '%$countryname%' ";
		$dd		= $dbConn->execute($dd);
		$dd		= $dbConn->getResultArray($dd);
		if(count($dd)>=1){
			$rate			= $dd[0]['discount']?$dd[0]['discount']:1;
			$kg				= $dd[0]['firstweight'];
			$handlefee		= $dd[0]['handlefee'];
			$shipfee		= $kg * $totalweight + $handlefee;
			if($rate > 0) $shipfee = $shipfee * $rate;
			return $shipfee;
		}else{
			return false;
		}
	}
	
	function calchkpypost($totalweight,$ebay_countryname){
	
			global $dbConn;
			
			$dd		= "SELECT * FROM  `ebay_cppycalcfee` where countrys like '%$ebay_countryname%' ";
			$dd		= $dbConn->execute($dd);
			$dd		= $dbConn->getResultArray($dd);
			if(count($dd)>=1){
				$firstweight	= $dd[0]['firstweight'];
				$nextweight		= $dd[0]['nextweight'];
				$handlefee		= $dd[0]['handlefee'];
				$discount		= $dd[0]['discount']?$dd[0]['discount']:1;
				$xx0			= $dd[0]['xx0'];
				$xx1			= $dd[0]['xx1'];
			    if($totalweight <= ($xx0/1000)){
				$shipfee	= $firstweight + $handlefee;
				}else{
				$shipfee	= ceil(((($totalweight*1000) -$xx0)/$xx1))*$nextweight + $firstweight + $handlefee;
				}
			}
			
			
			return $shipfee;
			
	}
	
	//中国邮政平邮
	function calcchinapostpy($totalweight, $countryname, $discount=true){
		global $dbConn;
		if(in_array($countryname,array("Russian Federation","Russia"))){
		   $shipfee = 97.5*$totalweight;
		   return $shipfee; 
		}
		$dd		= "SELECT * FROM  `ebay_cppycalcfee` where countrys like '%$countryname%' ";
		$dd		= $dbConn->execute($dd);
		$dd		= $dbConn->getResultArray($dd);
		if(count($dd)>=1){
			$rate			= $dd[0]['discount']?$dd[0]['discount']:1;
			$kg				= $dd[0]['firstweight'];
			
			$shipfee		= $kg * $totalweight;
			if (!$discount){
				return $shipfee;
			}
			if($rate > 0) $shipfee = $shipfee * $rate;
			return $shipfee;
		}else{
			return false;	
		}
	}
	function calcdhlshippingfee($totalweight,$countryname){
		//计算DHL的运费,包含重量大于20kg时的算法和重量小于等于20kg时的算法
		//add by Herman.Xi @2013-01-14
		global $dbConn;
		if($totalweight <= 0) return false;
		$shipfee = 0;
		if($totalweight <= 20){
			$mode = 1;
		}else{
			$mode = 2;
		}
		$sql = "SELECT * FROM ebay_dhlcalcfee WHERE country like '%[$countryname]%' and mode = '{$mode}' ";
		$result = $dbConn->execute($sql);
		$dhlcalcfee = $dbConn->fetch_one($result);
		$dbConn->free_result($result);
		if(empty($dhlcalcfee)) return 0; //没有该国家DHL设置信息
		$weight_freight = $dhlcalcfee['weight_freight'];
		$weight_freight_arr = explode(',', $weight_freight);
		foreach($weight_freight_arr as $wf_value){
			$wf_value_arr = explode(':', $wf_value);
			$w_range = explode('-', $wf_value_arr[0]);
			if($mode == 1){
				if($totalweight > $w_range[0] && $totalweight <= $w_range[1]){
					$shipfee = $wf_value_arr[1];
					break;
				}
			}else if($mode == 2){
				if(empty($w_range[1])){
					if($totalweight > $w_range[0]){
						$shipfee = $totalweight * $wf_value_arr[1];
					}
				}else{
					if($totalweight > $w_range[0] && $totalweight <= $w_range[1]){
						$shipfee = $totalweight * $wf_value_arr[1];
					}
		
				}
			}
		}
		$shipfee = $shipfee * (1 + $dhlcalcfee['fuelcosts']);
		return round($shipfee, 2);
		
	}
	function calctrueshippingfee($carrier, $totalweight, $countryname, $orderid){

		switch ($carrier){
			case '香港小包平邮' : $ordershipfee = calchkpost($totalweight,$countryname);
				break;
			case '香港小包挂号' : $ordershipfee = calchkghpost($totalweight,$countryname);
				break;
			case '中国邮政平邮' : $ordershipfee = calcchinapostpy($totalweight,$countryname,false);
				break;
			case '中国邮政挂号' : $ordershipfee = calcchinapostgh($totalweight,$countryname,false);
				break;
			case 'EUB' : $ordershipfee = calceub($totalweight,$countryname,false);
				break;
			case 'EMS' : $ordershipfee = calcems($totalweight,$countryname);
				break;
			case 'FedEx' : $ordershipfee = calcfedex($totalweight,$countryname,$orderid);
				break;
			case 'Global Mail' : $ordershipfee = calcglobalmail($totalweight,$countryname);
				break;
			case 'DHL' : $ordershipfee = calcdhlshippingfee($totalweight,$countryname);
				break;
			default : $ordershipfee = 0;
		}
	
		return $ordershipfee;
	}

function calctrueshippingfee2($carrier, $totalweight, $countryname, $orderid){
	//根据运输方式，订单总重量，运去的国家，和订单ID，计算打折后的运费 (中国邮政平邮,中国邮政挂号,EUB,EMS)
	//add by Herman.Xi 2012-09-14
	switch ($carrier){
		case '中国邮政平邮' : $ordershipfee = calcchinapostpy($totalweight,$countryname);
			break;
		case '中国邮政挂号' : $ordershipfee = calcchinapostgh($totalweight,$countryname);
			break;
		case 'EUB' : $ordershipfee = calceub($totalweight,$countryname);
			break;
		case 'EMS' : $ordershipfee = calcems($totalweight,$countryname);
			break;
		case '香港小包平邮' : 
		case '香港小包挂号' : 
		case 'FedEx' : 
		case 'Global Mail' : 
		case 'DHL' : $ordershipfee = calctrueshippingfee($carrier, $totalweight, $countryname, $orderid);
			break;
		default : $ordershipfee = 0;
	}

	return round($ordershipfee, 3);
}
?>