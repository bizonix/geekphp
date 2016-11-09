<?php
/**
 * 类名：OrderSalesModel
 * 功能：sku销量统计
 * 版本：V1.0
 * 作者：wcx
 * 时间：2015-05-05
 */
class OrderSalesModel extends CommonModel{
	public function __construct(){
		parent::__construct();
	}

	//解析订单数据
	public function insertSkuSalesDatas($data){
		if(empty($data['simple_detail'])){
			self::$errMsg[10007]   =   get_promptmsg(10007,"参数simple_detail");
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
		if(empty($data['delivery_from'])){
			self::$errMsg[10007]   =   get_promptmsg(10007,"参数create_time");
			return false;
		}


		$retData 	= array();
		//skulist

		//获取订单费用信息
		$simpleData = empty($data['simple_detail']) ? '' : json_decode($data['simple_detail'],true);
		$actualFee = $simpleData['orderFee']['actualInfo'];
		$item_total_pay = $simpleData['item_total_pay'] * (1-C('ORDER_FEE')['platfrom_handle_rate'][$data['source_platform']]) * C('ORDER_FEE')['exchange_rate'];

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
			'to_company'    => $data['delivery_from'],
		    'come_from'     => $data['come_from'],
			'sales_amount'  => sprintf('%.3f',$item_total_pay),
			'order_cost'	=> sprintf('%.3f',$actualFee['total_fee']),
			'cost_details'  => json_encode($actualFee),
			'currency_code' => $simpleData['item_currency_code'],
			'exchange_rate'	=> C('ORDER_FEE')['exchange_rate'],
			'date'			=> strtotime(date('Y-m-d',$data['create_time'])),
			'month'			=> strtotime(date('Y-m',$data['create_time'])),
			'update_time'	=> time()
		);

		$this->setTablePrefix('_'.date('Y',$data["create_time"]));
		$exist = $this->getSingleData('*',$whereData);
		if(empty($exist)){
			$res = $this->insertData($tData);
			$retData['order_fee'] = $this->getErrorMsg();
		}else{
			$res = $this->updateData($exist['id'],$tData);
			$retData['order_fee'] = $this->getErrorMsg();
		}

		return $retData;
	}

    
    
}
?>