<?php
/**
 * 类名：SkuSalesModel
 * 功能：sku销量统计
 * 版本：V1.0
 * 作者：zjr
 * 时间：2015-05-05
 */
class SkuSalesModel extends CommonModel{
	public function __construct(){
		parent::__construct();
	}

	//解析订单数据
	public function insertSkuSalesDatas($data){
		if(empty($data['simple_detail'])){
			self::$errMsg[10007]   =   get_promptmsg(10007,"参数simple_detail");
			return false;
		}
		if(empty($data['childOrderList'])){
			self::$errMsg[10007]   =   get_promptmsg(10007,"参数childOrderList");
			return false;
		}
		if(empty($data['order_id'])){
			self::$errMsg[10007]   =   get_promptmsg(10007,"参数order_id");
			return false;
		}
		if(empty($data['company_id'])){
			self::$errMsg[10007]   =   get_promptmsg(10007,"参数company_id");
			return false;
		}
		/* if(empty($data['shop_id'])){
			self::$errMsg[10007]   =   get_promptmsg(10007,"参数shop_id");
			return false;
		} */
		if(empty($data['source_platform'])){
			self::$errMsg[10007]   =   get_promptmsg(10007,"参数source_platform");
			return false;
		}
		if(empty($data['create_time'])){
			self::$errMsg[10007]   =   get_promptmsg(10007,"参数create_time");
			return false;
		}


		$retData 	= array();
		//skulist
		$skuList = array();
		$childOrderList = is_array($data['childOrderList']) ? $data['childOrderList'] : json_decode($data['childOrderList'],true);
		if(!empty($childOrderList)){
			foreach ($childOrderList as $value) {
				if(!empty($value['productAttributes']['sku'])){
					@$skuList[$value['productAttributes']['sku']]['lotNums'] += empty($value['lotNum']) ? 0 : $value['lotNum'];
					@$skuList[$value['productAttributes']['sku']]['itemPrice'] = (empty($value['productAttributes']['itemPrice']) ? 0 : $value['productAttributes']['itemPrice'])*C('ORDER_FEE')['exchange_rate'];
				}
			}
		}

		//获取订单费用信息
		$simpleData = empty($data['simple_detail']) ? '' : json_decode($data['simple_detail'],true);
		if(!empty($simpleData)){
			foreach ($simpleData['orderFee']['skuNumPrice'] as $value) {
				if(!empty($value['sku'])){
					@$skuList[$value['sku']]['package_fee'] = empty($value['packageFee']) ? 0.00 : $value['packageFee'];
					@$skuList[$value['sku']]['handle_fee'] = C('ORDER_FEE')['handle_fee']['start'];
					@$skuList[$value['sku']]['sales_price'] = $skuList[$value['sku']]['lotNums']*$skuList[$value['sku']]['itemPrice'];
					@$skuList[$value['sku']]['shipping_fee'] = empty($value['sku_shippint_fee']) ? 0.00 : $value['sku_shippint_fee'];
					@$skuList[$value['sku']]['sku_nums'] = $skuList[$value['sku']]['lotNums'];
				}
			}
		}

		$whereData	= array(
			'order_id' 		=> $data['order_id'],
			'company_id' 	=> $data['company_id'],
			'shop_id' 		=> $data['shop_id'],
			'platform' 		=> $data['source_platform'],
		);

		$tData = array(
			'order_id' 		=> $data['order_id'],
			'company_id' 	=> $data['company_id'],
			'shop_id' 		=> $data['shop_id'],
			'platform' 		=> $data['source_platform'],
			'handle_status' => $data['handle_status'],
			'package_fee'	=> 0.00,
			'handle_fee'	=> 0.00,
			'shipping_fee'	=> 0.00,
			'sales_price'	=> 0.00,
			'sku_nums'		=> 1,
			'date'			=> strtotime(date('Y-m-d',$data['create_time'])),
			'month'			=> strtotime(date('Y-m',$data['create_time'])),
			'update_time'	=> time()
		);

		$this->setTablePrefix('_'.date('Y',$data["create_time"]));
		foreach ($skuList as $sku => $value) {
			@$whereData['sku'] 		= $sku;
			@$tData['sku'] 			= $sku;
			@$tData['package_fee'] 	= sprintf('%.3f',$value['package_fee']);
			@$tData['handle_fee']  	= sprintf('%.3f',$value['handle_fee']);
			@$tData['sales_price'] 	= sprintf('%.3f',$value['sales_price']);
			@$tData['shipping_fee'] = sprintf('%.3f',$value['shipping_fee']);
			@$tData['sku_nums'] 	= $value['sku_nums'];
			//判断是否存在
			$exist = $this->getSingleData('*',$whereData);
			if(empty($exist)){
				$res = $this->insertData($tData);
				$retData[$sku] = $this->getErrorMsg();
			}else{
				$res = $this->updateData($exist['id'],$tData);
				$retData[$sku] = $this->getErrorMsg();
			}
		}

		return $retData;
	}

    
    
}
?>