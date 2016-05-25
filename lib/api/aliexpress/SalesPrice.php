<?php
/**
*类名：SalesPrice
*功能：速卖通产品定价
*作者：冯赛明
*版本：V1
*开发时间：2013-7-18
*修改人：冯赛明
*修改时间：2013-8-5
*/

class SalesPrice
{	
	public static $postage_discounts = 0.73;//邮费折扣，默认为0.77
	
	public function __construct()
	{
		
	}	
	
	public static function __callStatic($foo, $params)
	{
		echo '<br/>你调用的方法'.$foo.'不存在<br/>';
	}
	
	/*
	*功能：零售区间最低起订量下限
	*说明：产品重量< 2KG时，零售区间最低起订量下限为1；产品重量>=2KG时，零售区间下限都为1
	*/
	public static function minimum_quantity_limit($product_weight)
	{
		if($product_weight < 2)
		{
			return 1;
		}
		else
		{
			return 1;
		}
	}
		
	/*
	*功能：计算“零售和批发起订数量上限”
	*说明：参数为：产品重量、该产品的单个包材重量、重量浮动(单位KG)、产品单价(单位:RMB)、产品单价参考数(单位:RMB)、零售或批发标志(1为零售,2为批发)
	*     
	*   计算公式说明：
	*	  零售区间最低起订量下限为1；
	*	  上限数量计算：
	*	  （1）	当2KG/（产品重量+1个该产品的包材重量+0.005KG）>= 50，且产品单价小于10RMB时，上限取50；
	*	  （2）	当2KG/（产品重量+1个该产品包材重量+0.005KG）>=50，且产品单价大于等于10RMB时，上限为（500/产品单价）向下取整；
	*	  （3）	以上两种情况外的上限数量为2KG/（产品重量+该产品的包材重量+0.005KG）向下取整。
	*	  当同一个产品多个子料号产品重量不同时，计算出来的上限数量也不同，此时上限数量按照最大的显示。 
	*起批数量为零售区间上限+1
	*/
	public static function order_quantity_limit($product_weight, $single_packaging_material_weight, $weight_floating=0.005, $product_unit_price, $product_unit_price_parameters=10, $retail_wholesale)
	{	//echo '产品重量'.$product_weight.'+该产品的单个包材重量'.$single_packaging_material_weight.'+产品单价'.$product_unit_price.'+零售或批发标志'.$retail_wholesale.'+';
	//产品重量0.044+0.01+6.500+2
		$limit = 1;//限制的数量
		if($product_weight < 2)
		{
			$data=2 / ($product_weight + $single_packaging_material_weight + $weight_floating);
			if($data >= 50 && $product_unit_price < $product_unit_price_parameters)
			{
				$limit = 50;
			}
			else if($data >= 50 && $product_unit_price >= $product_unit_price_parameters)
			{
				$limit = floor(500 / $product_unit_price);//向下取整(舍去小数取整)
			}
			else
			{
				$limit = floor(2 / ($product_weight + $single_packaging_material_weight + $weight_floating));//向下取整(舍去小数取整)
			}
		}
		else if($product_weight >= 2)
		{
			$limit = 1;
		}
		
		if($retail_wholesale==2)//如果是批发，则起批数量要加1
		{
			$limit = $limit + 1;
		}		
		//echo ' 起订数量上限和下限:'.$limit;
		return $limit;
	}
	
	/*
	*功能：计算"总的包材重量"
	*说明：参数为：起订数量、包材容量、1个包材重量
	*    计算公式：
	*    包材重量=（起订数量/包材容量）向上取整*1个包材重量
	*/
	public static function total_packaging_material_weight($order_quantity, $packaging_capacity, $single_packaging_material_weight)
	{
		$weight = ceil($order_quantity / $packaging_capacity);//向上取整
		$weight = $weight * $single_packaging_material_weight;
		//echo ' 包材重量:'.$weight;
		return $weight;
	}	
	
	/*
	*功能：计算"包裹重量"
	*说明：参数为：产品重量、产品数量、包材重量
	*     计算公式：
	*     包裹重量=产品重量*产品数量+包材重量
	*/
	public static function package_weight($product_weight, $product_number, $packaging_material_weight)
	{
		$weight = $product_weight * $product_number + $packaging_material_weight;
		//echo ' 包裹重量:'.$weight;
		return $weight;
	}	
	
	/*
	*功能：计算"订单处理费用"
	*说明：参数为：包装处理费用、美元对RMB汇率参数、美元对RMB汇率、订单下载成本
	*    计算公式：
	*    订单处理费用=包装处理费用1.62元+0.01*美元对RMB汇率+订单下载成本0.72元=2.4RMB
	*/
	public static function order_processing_costs($packaging_processing_costs = 1.62, $dollar_rmb_exchange_rate_parameter = 0.01, $dollar_rmb_exchange_rate, $order_download_cost = 0.72)
	{
		$order_processing_costs = $packaging_processing_costs + ($dollar_rmb_exchange_rate_parameter *  $dollar_rmb_exchange_rate) + $order_download_cost;
		//echo ' 订单处理费用:'.$order_processing_costs;
		return $order_processing_costs;
	}	
	
	/*
	*功能：计算"中国邮政挂号小包邮费(折扣前邮费)"
	*说明：参数为：资费基准、挂号费、邮费折扣
	*     计算公式：
	*     以到俄罗斯的中国邮政挂号小包资费为计算基准，资费基准96.3元，挂号费8元，邮费折扣0.77
    *     （1）当包裹重量 < 2KG时，CN折扣前邮费=（包裹重量*RU的CN资费基准+挂号费）
    *     （2）当包裹重量 >= 2KG时，CN邮费为0
	*/
	public static function china_post_registered_parcel_postage($package_weight, $benchmark_rates=96.3, $registration_fee=8)
	{
		if($package_weight >= 2)
		{
			//echo '中国邮政挂号小包邮费：0';
			return 0;
		}
		else if($package_weight < 2)
		{
			$costs = ($package_weight * $benchmark_rates + $registration_fee);
			//echo ' 中国邮政挂号小包邮费：'.$costs;
			return $costs;
		}        
	}	
	
	/*
	*功能：计算"香港邮政挂号邮费"
	*说明：参数为：资费基准、挂号费
	*     计算公式：
	*     资费基准95.12元，挂号费10.66元
	*	 （1）当包裹重量 <2KG时，HK邮费=包裹重量*HK资费基准+挂号费
	*	 （2）当包裹重量 >=2KG时，HK邮费为0
	*/
	public static function HK_post_registered_postage($package_weight, $benchmark_rates=95.12, $registration_fee=10.66)
	{
		if($package_weight >= 2)
		{
			return 0;
		}
		else if($package_weight < 2)
		{
			$costs = $package_weight * $benchmark_rates + $registration_fee;
			return $costs;
		}		
	}	
	
	/*
	*功能：计算"零售价格"
	*说明：参数为：产品进货价、零售区间下限、订单处理费用、零售区间利润控制线、USD/CNY汇率、ali收取手续费
	*     计算公式：
	*    （1）零售价格=【（产品进货价*零售区间下限(默认为1)）+订单处理费用+折扣前邮费*邮费折扣】*（1+零售区间利润控制线20%）/（ USD/CNY汇率）/零售区间下限(默认为1)/（1—ali收取手续费）
	*/
	//public static function retail_price()
   public static function retail_price($product_purchase_price, $retail_range=1, $order_processing_costs, $retail_interval_line_of_profit=0.2, $dollar_rmb_exchange_rate, $ali_collection_poundage=0.05, $transport_model="ZH", $package_weight)
	{		
	    switch($transport_model)
		{
			case "ZH"://运输方式：中国邮政		
				$transportFee = self::china_post_registered_parcel_postage($package_weight) * self::$postage_discounts;
				
			break;
			case "HK"://运输方式：香港邮政
				$transportFee = self::HK_post_registered_postage($package_weight);
			break;
			default:
				$transportFee = 0;
			break;
		}
		$retail_price = ($product_purchase_price * $retail_range + $order_processing_costs + $transportFee) * (1 + $retail_interval_line_of_profit) / ($dollar_rmb_exchange_rate * $retail_range * (1 - $ali_collection_poundage));
		
		//echo ' 零售价格：'.$retail_price;
		return $retail_price;
	}	
	
	/*
	*功能：计算"批发价格"
	*说明：参数为：产品进货价、起批数量、订单处理费用、折扣前邮费、邮费折扣、批发利润控制线、USD/CNY汇率、ali收取手续费
	*     计算公式：
	*     批发价格=【（产品进货价*起批数量）+订单处理费用+折扣前邮费*邮费折扣】*（1+批发利润控制线15%）/（ USD/CNY汇率）/起批数量/（1—ali收取手续费）
	*/
	public static function wholesale_price($product_purchase_price, $batch_number, $order_processing_costs, $wholesale_line_of_profit=0.15, $dollar_rmb_exchange_rate, $ali_collection_poundage=0.05, $transport_model="ZH", $package_weight)
	{
		 switch($transport_model)
		{
			case "ZH"://运输方式：中国邮政		
				$transportFee = self::china_post_registered_parcel_postage($package_weight) * self::$postage_discounts;
				
			break;
			case "HK"://运输方式：香港邮政
				$transportFee = self::HK_post_registered_postage($package_weight);
			break;
			default:
				$transportFee = 0;
			break;
		}
		
		$wholesale_price = ($product_purchase_price * $batch_number + $order_processing_costs + $transportFee) * (1 + $wholesale_line_of_profit) / ($dollar_rmb_exchange_rate * $batch_number * (1 - $ali_collection_poundage));
		
		return $wholesale_price;
	}	
	
	/*
	*功能：计算非打包"批发折扣率"
	*说明：参数为:零售价格、批发价格
	*     计算公式：
	*     批发折扣率=（零售价格-批发价格）/零售价格
	*/
	public static function wholesale_discount($retail_price, $wholesale_price)
	{
		$wholesale_discount = ($retail_price - $wholesale_price) / $retail_price;
		//echo '批发折扣率'.$wholesale_discount;
		return $wholesale_discount;
	}	
	
	/*
	*功能：计算非打包"在线减免率"
	*说明：参数为:批发折扣率
	*     计算公式：
	*     如果批发折扣率>=20%，则在线减免率=批发折扣率—10%；
    *     如果批发折扣率<20%，则在线减免率=批发折扣率
	*/
	public static function online_reduction_rate($wholesale_discount)
	{
		if($wholesale_discount < 0.2)
		{
			return $wholesale_discount;
		}
		else
		{
			return ($wholesale_discount - 0.1);
		}
	}		
	
	/*
	*功能：计算非打包"零售下限毛利"
	*说明：参数为:阿里收取的手续费、零售价格、零售下限数量、（USD/CNY汇率）、产品进货价、折扣前邮费、邮费折扣、订单处理费用
	*     计算公式：
	*     零售下限毛利=（1-阿里收取的手续费）*零售价格*零售下限数量*（USD/CNY汇率）—零售下限数量*产品进货价—折扣前邮费*邮费折扣—订单处理费用
	*/
	public static function retail_lower_margin($ali_collection_poundage, $retail_price, $retail_floor_number, $dollar_rmb_exchange_rate, $product_purchase_price, $order_processing_costs, $transport_model="ZH", $package_weight)
	{
		switch($transport_model)//运输方式
		{
			case "ZH"://运输方式：中国邮政		
				$transportFee = self::china_post_registered_parcel_postage($package_weight) * self::$postage_discounts;
				
			break;
			case "HK"://运输方式：香港邮政
				$transportFee = self::HK_post_registered_postage($package_weight);
			break;
			default:
				$transportFee = 0;
			break;
		}
		
		$retail_lower_margin = (1 - $ali_collection_poundage) * $retail_price * $retail_floor_number * $dollar_rmb_exchange_rate - $retail_floor_number * $product_purchase_price - $transportFee - $order_processing_costs;
		//echo '零售下限数量'.$retail_floor_number;
		//echo '零售下限毛利:'.$retail_lower_margin;
		return $retail_lower_margin;
	}		
	
	/*
	*功能：计算非打包"批发毛利"
	*说明：参数为:阿里收取的手续费、批发价格、起批数量、（USD/CNY汇率）、产品进货价、折扣前邮费、邮费折扣、订单处理费用
	*     计算公式：
	*     批发毛利=（1-阿里收取的手续费）*批发价格*起批数量*（USD/CNY汇率）—起批数量*产品进货价—折扣前邮费*邮费折扣—订单处理费用
	*/
	public static function wholesale_gross_profit($ali_collection_poundage, $wholesale_price, $batch_number, $dollar_rmb_exchange_rate, $product_purchase_price, $order_processing_costs,  $transport_model="ZH", $package_weight)
	{
		switch($transport_model)//运输方式
		{
			case "ZH"://运输方式：中国邮政		
				$transportFee = self::china_post_registered_parcel_postage($package_weight) * self::$postage_discounts;
				
			break;
			case "HK"://运输方式：香港邮政
				$transportFee = self::HK_post_registered_postage($package_weight);
			break;
			default:
				$transportFee = 0;
			break;
		}
		
		$wholesale_gross_profit = (1 - $ali_collection_poundage) * $wholesale_price * $batch_number * $dollar_rmb_exchange_rate - $batch_number * $product_purchase_price - $transportFee - $order_processing_costs;
		
		return $wholesale_gross_profit;
	}	
	
	/*
	*功能：计算非打包"零售下限毛利率"
	*说明：参数为:零售下限毛利、零售价格、零售下限数量、（USD/CNY汇率）
	*     计算公式：
	*     零售下限毛利率=零售下限毛利/【零售价格*零售下限数量* （USD/CNY汇率）】
	*/
	public static function retail_lower_limit_gross_margin($retail_lower_margin, $retail_price, $retail_floor_number, $dollar_rmb_exchange_rate)
	{
		$retail_lower_limit_gross_margin = $retail_lower_margin / ($retail_price * $retail_floor_number * $dollar_rmb_exchange_rate);
		
		return $retail_lower_limit_gross_margin;
	}	
	
	/*
	*功能：计算非打包"批发毛利率"
	*说明：参数为:起批毛利、批发价格、起批数量、（USD/CNY汇率）
	*     计算公式：
	*     批发毛利率=起批毛利/【批发价格*起批数量*（USD/CNY汇率）】
	*/
	public static function wholesale_gross_margin($batch_gross_profit, $wholesale_price
, $batch_number, $dollar_rmb_exchange_rate)
	{
		$wholesale_gross_margin = $batch_gross_profit / ($wholesale_price * $batch_number * $dollar_rmb_exchange_rate);
		//echo $wholesale_price.','.$batch_number.','.$dollar_rmb_exchange_rate;
		//echo '<br/>'.$batch_gross_profit;
		return $wholesale_gross_margin;
	}	
	
	/*
	*功能：计算"打包的包材重量"
	*说明：参数为:在线区间数量(起订数量)、每LOT产品件数、包材容量、包材容量参数、1个包材的重量
	*     计算公式：
	*     （1）当（在线区间数量*每LOT产品件数）<=包材容量时，包材重量=1个包材的重量
          （2）否则包材重量=【1+（在线区间数量*每LOT产品件数—包材容量）*60%/包材容量】*1个包材重量
	*/
	public static function lot_packaging_material_weight($online_interval_number, $per_lot_product_quantity, $packaging_capacity, $packaging_capacity_param = 0.6, $one_packaging_material_weight)
	{
		if(($online_interval_number * $per_lot_product_quantity) <= $packaging_capacity)
		{
			$packaging_material_weight = $one_packaging_material_weight;
		}
		else
		{
			$packaging_material_weight = (1 + ($online_interval_number * $per_lot_product_quantity - $packaging_capacity) * $packaging_capacity_param / $packaging_capacity) * $one_packaging_material_weight;
		}
		return $packaging_material_weight;
	}	
	
	/*
	*功能：计算"每LOT重量"
	*说明：参数为:每件产品重量、每LOT产品件数、零售区间下限时的包材重量
	*     计算公式：
	*     每LOT重量(含包材，Kg)：每件产品重量*每LOT产品件数+零售区间下限时的包材重量+0.02
	*/
	public static function per_lot_weight($per_product_weight, $per_lot_product_quantity, $retail_packaging_weight_range_lower_limit)
	{
		$per_lot_weight = $per_product_weight * $per_lot_product_quantity + $retail_packaging_weight_range_lower_limit  + 0.02;
		return $per_lot_weight;
	}	
	
	/*
	*功能：计算"打包包裹重量"
	*说明：参数为:每LOT重量、在线区间数量、零售区间下限时的包材重量
	*     计算公式：
	*     包裹重量=每LOT重量(含包材，Kg)*在线区间数量
	*/
	public static function lot_package_weight($per_lot_weight, $online_interval_number)
	{
		$package_weight = $per_lot_weight * $online_interval_number;
		return $package_weight;
	}	
	
	/*
	*功能：计算"打包零售价格"
	*说明：参数为：产品进货价、每LOT产品件数、零售区间下限、订单处理费用、折扣前邮费、邮费折扣、打包零售区间利润控制线(默认15%)、USD/CNY汇率、ali收取手续费
	*     计算公式：
	*     零售价格=【（产品进货价*每LOT产品件数*零售区间下限）+订单处理费用+折扣前邮费*邮费折扣】*（1+打包零售区间利润控制线15%）/（ USD/CNY汇率）/零售区间下限/（1—ali收取手续费）
	*/
	public static function lot_retail_price($product_purchase_price, $per_lot_product_quantity, $retail_range
=1, $order_processing_costs, $lot_retail_interval_line_of_profit=0.15, $dollar_rmb_exchange_rate, $ali_collection_poundage=0.05, $transport_model="ZH", $lot_package_weight)
	{		
	    switch($transport_model)//运输方式
		{
			case "ZH"://运输方式：中国邮政		
				$transportFee = self::china_post_registered_parcel_postage($lot_package_weight) * self::$postage_discounts;
				
			break;
			case "HK"://运输方式：香港邮政
				$transportFee = self::HK_post_registered_postage($lot_package_weight);
			break;
			default:
				$transportFee = 0;
			break;
		}
		$retail_price = (($product_purchase_price * $per_lot_product_quantity * $retail_range) + $order_processing_costs + $transportFee) * (1 + $lot_retail_interval_line_of_profit) / ($dollar_rmb_exchange_rate * $retail_range * (1 - $ali_collection_poundage));
		
		return $retail_price;
	}		
	
	/*
	*功能：计算"打包批发价格"
	*说明：参数为：产品进货价、每LOT产品件数、起批数量、订单处理费用、折扣前邮费、邮费折扣、打包批发利润控制线、USD/CNY汇率、ali收取手续费、运输方式、打包包裹重量
	*     计算公式：
	*     批发价格=【（产品进货价*每LOT产品件数*起批数量）+订单处理费用+折扣前邮费*邮费折扣】*（1+打包批发利润控制线12%）/（ USD/CNY汇率）/起批数量/（1—ali收取手续费）
	*/
	public static function lot_wholesale_price($product_purchase_price, $per_lot_product_number, $batch_number, $order_processing_costs, $lot_wholesale_line_of_profit=0.12, $dollar_rmb_exchange_rate, $ali_collection_poundage=0.05, $transport_model="ZH", $lot_package_weight)
	{
		 switch($transport_model)//运输方式
		{
			case "ZH"://运输方式：中国邮政		
				$transportFee = self::china_post_registered_parcel_postage($lot_package_weight) * self::$postage_discounts;
				
			break;
			case "HK"://运输方式：香港邮政
				$transportFee = self::HK_post_registered_postage($lot_package_weight);
			break;
			default:
				$transportFee = 0;
			break;
		}
		//echo '打包运费'.$transportFee;
		$lot_wholesale_price = ($product_purchase_price * $per_lot_product_number * $batch_number + $order_processing_costs + $transportFee) * (1 + $lot_wholesale_line_of_profit) / ($dollar_rmb_exchange_rate * $batch_number * (1 - $ali_collection_poundage));
		
		return $lot_wholesale_price;
	}	
	
	/*
	*功能：计算"打包批发折扣率"
	*说明：参数为:零售价格、批发价格
	*     计算公式：
	*     批发折扣率=（零售价格-批发价格）/零售价格
	*/
	public static function lot_wholesale_discount($retail_price, $wholesale_price)
	{
		$lot_wholesale_discount = ($retail_price - $wholesale_price) / $retail_price;
		
		return $lot_wholesale_discount;
	}	
	
	/*
	*功能：计算"打包在线减免率"
	*说明：参数为:批发折扣率
	*     计算公式：
	*     如果批发折扣率>=10%，则在线减免率=批发折扣率—5%；
	*	  如果批发折扣率<10%，则在线减免率=批发折扣率
	*/
	public static function lot_online_reduction_rate($wholesale_discount)
	{
		if($wholesale_discount < 0.1)
		{
			return $wholesale_discount;
		}
		else
		{
			return ($wholesale_discount - 0.05);
		}
	}		
	
	/*
	*功能：计算"打包零售下限毛利"
	*说明：参数为:阿里收取的手续费、打包零售价格、打包零售下限数量、（USD/CNY汇率）、每LOT产品数、产品进货价、折扣前邮费、邮费折扣、订单处理费用
	*     计算公式：
	*     打包零售下限毛利=（1-阿里收取的手续费）*打包零售价格*打包零售下限数量*（USD/CNY汇率）—打包零售下限数量*每LOT产品数*产品进货价—折扣前邮费*邮费折扣—订单处理费用
	*/
	public static function lot_retail_lower_margin($ali_collection_poundage, $lot_retail_price, $lot_retail_floor_number=1, $dollar_rmb_exchange_rate, $per_lot_product_number, $product_purchase_price, $order_processing_costs, $transport_model="ZH", $lot_package_weight)
	{
		 switch($transport_model)//运输方式
		{
			case "ZH"://运输方式：中国邮政		
				$transportFee = self::china_post_registered_parcel_postage($lot_package_weight) * self::$postage_discounts;
				
			break;
			case "HK"://运输方式：香港邮政
				$transportFee = self::HK_post_registered_postage($lot_package_weight);
			break;
			default:
				$transportFee = 0;
			break;
		}
		$lot_retail_lower_margin = (1 - $ali_collection_poundage) * $lot_retail_price * $lot_retail_floor_number * $dollar_rmb_exchange_rate - $lot_retail_floor_number * $per_lot_product_number * $product_purchase_price - $transportFee - $order_processing_costs;
		
		return $lot_retail_lower_margin;
	}	
	
	/*
	*功能：计算"打包起批毛利"
	*说明：参数为:阿里收取的手续费、打包批发价格、打包起批数量、（USD/CNY汇率）、每LOT产品数、产品进货价、折扣前邮费、邮费折扣、订单处理费用
	*     计算公式：
	*     打包起批毛利=（1-阿里收取的手续费）*打包批发价格*打包起批数量*（USD/CNY汇率）—打包起批数量*每LOT产品数*产品进货价—折扣前邮费*邮费折扣—订单处理费用
	*/
	public static function packaging_batch_of_gross_margin($ali_collection_poundage, $lot_wholesale_price, $batch_number, $dollar_rmb_exchange_rate, $per_lot_product_number, $product_purchase_price, $order_processing_costs, $transport_model="ZH", $lot_package_weight)
	{
		 switch($transport_model)//运输方式
		{
			case "ZH"://运输方式：中国邮政		
				$transportFee = self::china_post_registered_parcel_postage($lot_package_weight) * self::$postage_discounts;
				
			break;
			case "HK"://运输方式：香港邮政
				$transportFee = self::HK_post_registered_postage($lot_package_weight);
			break;
			default:
				$transportFee = 0;
			break;
		}
		$packaging_batch_of_gross_margin = (1 - $ali_collection_poundage) * $lot_wholesale_price * $batch_number * $dollar_rmb_exchange_rate - $batch_number * $per_lot_product_number * $product_purchase_price - $transportFee - $order_processing_costs;
		
		return $packaging_batch_of_gross_margin;
	}		
	
	/*
	*功能：计算"打包零售下限毛利率"
	*说明：参数为:
	*     计算公式：打包零售下限毛利率=打包零售下限毛利/【打包零售价格*零售下限数量* （USD/CNY汇率）】
	*     
	*/
	public static function lot_lower_limit_packaged_retail_gross_margin($packaging_and_retail_minimum_margin, $lot_retail_price, $retail_floor_number=1, $dollar_rmb_exchange_rate)
	{		
		$lot_lower_limit_packaged_retail_gross_margin = $packaging_and_retail_minimum_margin / ($lot_retail_price * $retail_floor_number * $dollar_rmb_exchange_rate);
		return $lot_lower_limit_packaged_retail_gross_margin;
	}
	
	/*
	*功能：计算"打包批发毛利率"
	*说明：参数为:
	*     计算公式：打包批发毛利率=打包起批毛利/【打包批发价格*打包起批数量*（USD/CNY汇率）】    
	*/
	public static function lot_wholesale_packaging_gross_margin($packaging_batch_of_gross_margin, $lot_wholesale_price, $batch_number, $dollar_rmb_exchange_rate)
	{		
		$lot_wholesale_packaging_gross_margin = $packaging_batch_of_gross_margin / ($lot_wholesale_price * $batch_number * $dollar_rmb_exchange_rate);
		
		return $lot_wholesale_packaging_gross_margin;
	}		
}
?>