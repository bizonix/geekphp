<?php
/*****************************************
 *	获取发货方式
 *	@param float $totalweight 重量(kg)
 *	@param string $site (UK/US.....)
 */
function get_carrier($totalweight, $ebay_countryname){
	//global $flip_transportList;
	if(in_array($ebay_countryname, array("United States", "Puerto Rico"))){
		$ebay_carrier = 'EUB';
		if($totalweight > 2){
			$ebay_carrier = 'FedEx';
		}
	}else if($ebay_countryname == "Canada"){
		$ebay_carrier = '中国邮政挂号';
	}else if($ebay_countryname == "United Kingdom"){
		$ebay_carrier = '德国邮政';
		if($totalweight > 0.74){
			$ebay_carrier = 'FedEx';
		}
	}else if($ebay_countryname == ''){
		return '';
	}else{
		$ebay_carrier = '德国邮政';
	}
	//var_dump($flip_transportList,$ebay_carrier);
	return $ebay_carrier;
}
?>